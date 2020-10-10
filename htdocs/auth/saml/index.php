<?php
/**
 *
 * @package    mahara
 * @subpackage auth-saml
 * @author     Piers Harding <piers@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 * @copyright  For copyright information on Mahara, please see the README file distributed with this software.
 *
 * This file incorporates work covered by the following copyright and
 * permission notice:
 *
 *    Moodle - Modular Object-Oriented Dynamic Learning Environment
 *             http://moodle.com
 *
 *    Copyright (C) 2001-3001 Martin Dougiamas        http://dougiamas.com
 *
 *    This program is free software; you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation; either version 2 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details:
 *
 *             http://www.gnu.org/copyleft/gpl.html
 */

define('INTERNAL', 1);
define('PUBLIC', 1);
global $CFG, $USER, $SESSION, $idp_entityid;
require(dirname(dirname(dirname(__FILE__))) . '/init.php');
require_once(get_config('docroot') .'auth/saml/lib.php');
require_once(get_config('libroot') .'institution.php');

// check that the plugin is active
if (get_field('auth_installed', 'active', 'name', 'saml') != 1) {
    redirect();
}

$sp = 'default-sp';

PluginAuthSaml::init_simplesamlphp();

// Check the SimpleSAMLphp config is compatible
$saml_config = SimpleSAML\Configuration::getInstance();
$session_handler = $saml_config->getString('session.handler', false);
$store_type = $saml_config->getString('store.type', false);
if ($store_type == 'phpsession' || $session_handler == 'phpsession' || (empty($store_type) && empty($session_handler))) {
    throw new AuthInstanceException(get_string('errorbadssphp', 'auth.saml'));
}

// do we have a logout request?
if (param_variable("logout", false)) {
    // logout the saml session
    $as = new SimpleSAML\Auth\Simple($sp);
    $as->logout($CFG->wwwroot);
}

// what is the session like?
$saml_session = SimpleSAML\Session::getSession();
$valid_saml_session = $saml_session ? $saml_session->isValid($sp) : false;

// figure out what the returnto URL should be
$wantsurl = param_variable("wantsurl", false);
if (!$wantsurl) {
    if (isset($_SESSION['wantsurl'])) {
        $wantsurl = $_SESSION['wantsurl'];
    }
    else if (! $saml_session || ! $valid_saml_session) {
        $wantsurl = array_key_exists('HTTP_REFERER',$_SERVER) ? $_SERVER['HTTP_REFERER'] : $CFG->wwwroot;
    }
    else {
        $wantsurl = $CFG->wwwroot;
    }
}
// taken from Moodle clean_param - make sure the wantsurl is correctly formed
include_once('validateurlsyntax.php');
if (!validateUrlSyntax($wantsurl, 's?H?S?F?E?u-P-a?I?p?f?q?r?')) {
    $wantsurl = $CFG->wwwroot;
}

$migratecheck = param_variable("migratecheck", false);
if ($migratecheck) {
    $SESSION->set('migratecheck', $migratecheck);
}

// trim off any reference to login and stash
$SESSION->wantsurl = preg_replace('/\&login$/', '', $wantsurl);

$as = new SimpleSAML\Auth\Simple($sp);
$idp_entityid = null;
if (($USER->is_logged_in() && $migratecheck) || !$as->isAuthenticated()) {
    if (param_variable("idpentityid", false)) {
        $idp_entityid = param_variable("idpentityid", false);
    }
    else {
        if (class_exists('PluginAuthSaml_IdPDisco')) {
            $discoHandler = new PluginAuthSaml_IdPDisco(array('saml20-idp-remote', 'shib13-idp-remote'), 'saml');
            $disco = $discoHandler->getTheIdPs();
            if (count($disco['list']) == 0) {
                throw new AuthInstanceException(get_string('errorbadssphpmetadata', 'auth.saml'));
            }
            else if (count($disco['list']) == 1) {
                $idp_entityid = array_shift($disco['list']);
                $idp_entityid = $idp_entityid["entityid"];
            }
            else {
                auth_saml_disco_screen($disco['list'], $disco['preferred']);
            }
        }
        else {
            throw new AuthInstanceException(get_string('errorbadssphpmetadata', 'auth.saml'));
        }
    }
}

// reinitialise config to pickup idp entityID
SimpleSAML\Configuration::init(get_config('docroot') . 'auth/saml/config');
$as = new SimpleSAML\Auth\Simple('default-sp');
if ($migratecheck) {
    $as->login(array('ReturnTo' => get_config('wwwroot') . "auth/saml/index.php", 'KeepPost' => FALSE));
}
else {
    $as->requireAuth(array('ReturnTo' => get_config('wwwroot') . "auth/saml/index.php", 'KeepPost' => FALSE));
}

// ensure that $_SESSION is cleared for simplesamlphp
if (isset($_SESSION['wantsurl'])) {
    unset($SESSION->wantsurl);
}

$saml_attributes = $as->getAttributes();

// now - let's continue with the session handling that would normally be done
// by Maharas init.php
// the main thin is that it sets the session cookie name back to what it should be
// session_name(get_config('cookieprefix') . 'mahara');
// and starts the session again

// ***********************************************************************
// copied from original init.php
// ***********************************************************************
// Only do authentication once we know the page theme, so that the login form
// can have the correct theming.
require_once(dirname(dirname(dirname(__FILE__))) . '/auth/lib.php');
$SESSION = Session::singleton();
$USER    = new LiveUser();
$THEME   = new Theme($USER);
// ***********************************************************************
// END of copied stuff from original init.php
// ***********************************************************************

// now start the hunt for the associated authinstance for the organisation attached to the saml_attributes
global $instance;
$instance = auth_saml_find_authinstance($saml_attributes);

// if we don't have an auth instance then this is a serious failure
if (!$instance) {
    throw new SamlUserNotFoundException(get_string('errorbadinstitution', 'auth.saml'));
}

if ($SESSION->get('migratecheck')) {
    $has_access = auth_saml_migrate_check($instance, $saml_attributes);
    $SESSION->set('migrateidp', null);
    $SESSION->set('migrateidpkey', null);
    $SESSION->set('migratecheck', null);
    $SESSION->set('migrateresponse', $has_access);
    redirect(get_config('wwwroot') . 'account/migrateinstitution.php');
}

// stash the existing logged in user - if we have one
$current_user = $USER;
$is_loggedin = $USER->is_logged_in();

// check the instance and do a test login
$can_login = false;
try {
    $auth = new AuthSaml($instance->id);
    $can_login = $auth->request_user_authorise($saml_attributes);
}
catch (AccessDeniedException $e) {
    throw new SamlUserNotFoundException(get_string('errnosamluser', 'auth.saml'));
}
catch (XmlrpcClientException $e) {
    throw new AccessDeniedException($e->getMessage());
}
catch (AuthInstanceException $e) {
    throw new AccessDeniedException(get_string('errormissinguserattributes1', 'auth.saml', get_config('sitename')));
}

// if we can login with SAML - then let them go
if ($can_login) {
    // they are logged in, so they dont need to be here
    if ($SESSION->get('wantsurl')) {
        $wantsurl = $SESSION->get('wantsurl');
        $SESSION->set('wantsurl', null);
    }
    // sanity check the redirect - we don't want to loop
    if (preg_match('/\/auth\/saml\//', $wantsurl)) {
        $wantsurl = $CFG->wwwroot;
    }

    // Schema present then it must be within this domain
    if (preg_match('/\:\/\//', $wantsurl) && !preg_match('/' . $_SERVER['HTTP_HOST'] . '/', $wantsurl)) {
        $wantsurl = $CFG->wwwroot;
    }

    // If relative path then add wwwroot
    if (!preg_match('/\:\/\//', $wantsurl) && preg_match('/\//', $wantsurl)) {
        $wantsurl = preg_replace('/^\//', '', $wantsurl); // remove leading /
        $wantsurl = $CFG->wwwroot . $wantsurl;
    }

    // if redirecting to using homepage but using custom landing page
    $homepageredirecturl = get_config('homepageredirecturl');
    if ($wantsurl === $CFG->wwwroot && get_config('homepageredirect') && !empty($homepageredirecturl)) {
        $wantsurl = $homepageredirecturl;
    }
    @session_write_close();
    redirect($wantsurl);
}

// are we configured to allow testing of local login and linking?
$loginlink = get_field('auth_instance_config', 'value', 'field', 'loginlink', 'instance', $instance->id);
if (empty($loginlink)) {
    throw new SamlUserNotFoundException(get_string('errnosamluser', 'auth.saml'));
}

// used in the submit callback for auth_saml_loginlink_screen()
global $remoteuser;
$user_attribute = get_field('auth_instance_config', 'value', 'field', 'user_attribute', 'instance', $instance->id);
$remoteuser = $saml_attributes[$user_attribute][0];

// is the local account already logged in or can the SAML auth succeed - if not try to get
// them to log in local/manual
if (!$is_loggedin) {
    // cannot match user account - so offer them the login-link/register page
    // if we can't login locally, and cant login via SAML then we should offer to register - but this should probably appear on the local login page anyway
    auth_saml_login_screen($remoteuser);
}
else {
    // if we can login locally, but can't login with SAML then we offer to link the accounts SAML -> local one
    auth_saml_loginlink_screen($remoteuser, $current_user->username);
}

exit(0);


/**
 * callback for linking local account with remote SAML account
 *
 * @param Pieform $form
 * @param array $values
 */
function auth_saml_loginlink_submit(Pieform $form, $values) {
    global $USER, $instance, $remoteuser;

    // create the new account linking
    db_begin();
    delete_records('auth_remote_user', 'authinstance', $instance->id, 'localusr', $USER->id);
    insert_record('auth_remote_user', (object) array(
        'authinstance'   => $instance->id,
        'remoteusername' => $remoteuser,
        'localusr'       => $USER->id,
    ));
    db_commit();
    @session_write_close();
    redirect('/auth/saml/index.php');
}


/**
 * Find the connected authinstance for the organisation attached to this SAML account
 *
 * @param array $saml_attributes
 *
 * @return object authinstance record
 */
function auth_saml_find_authinstance($saml_attributes) {
    // find the one (it should be only one) that has the right field, and the right field value for institution
    $instance = false;
    $institutions = array();

    if (get_config('saml_create_institution') && get_config('saml_create_institution_default')) {
        // We can try and make the institution if it doesn't exist
        // First check if there is another institution that we can get default saml metadata from
        // This institution will be defined in the config
        $configs = false;
        foreach ($defaults = explode(',', get_config('saml_create_institution_default')) as $default) {
            if (!record_exists('institution', 'name', $default)) {
                // institution does not exist
                continue;
            }
            $auths = get_column_sql("SELECT ai.id FROM {auth_instance_config} aic
                                     JOIN {auth_instance} ai ON ai.id = aic.instance
                                     WHERE ai.authname = ? AND ai.active = 1
                                     AND aic.field = 'institutionidpentityid' AND aic.value IS NOT NULL
                                     AND ai.institution = ?", array('saml', $default));
            if ($auths) {
                foreach ($auths as $auth) {
                    // Select 'field' first so it is used as the array key
                    if ($configs = get_records_sql_assoc("SELECT field, value, instance FROM {auth_instance_config}
                                                          WHERE instance = ?", array($auth))) {
                        if (isset($saml_attributes[$configs['institutionattribute']->value]) &&
                            isset($saml_attributes[$configs['user_attribute']->value])) {
                            // Institution default SAML found
                            break;
                        }
                        else {
                            $configs = false;
                            continue;
                        }
                    }
                }
            }
        }
        // now we have the default configs
        if ($configs) {
            // Check there is a roleprefix set to see if they are allowed to try and login
            // We do this here to avoid making an institution for a user that can't login
            if (isset($configs['roleprefix']->value)) {
                $roleallowed = false;
                foreach ($saml_attributes[$configs['role']->value] as $index => $role) {
                    if (preg_match('/^' . $configs['roleprefix']->value . '/', $role)) {
                        $roleallowed = true;
                    }
                }
                if (!$roleallowed) {
                    log_debug('User authorisation request from SAML failed - no roles prefixed with "' . $configs['roleprefix']->value . '"');
                    return false;
                }
            }

            foreach ($saml_attributes[$configs['institutionattribute']->value] as $index => $attr) {
                // does this institution use a regex match against the institution check value?
                if ($configvalue = $configs['institutionregex']->value) {
                    $is_regex = (boolean) $configvalue;
                }
                else {
                    $is_regex = false;
                }
                $instmatch = $is_regex ? "aic.value LIKE '%' || ? || '%'" : "aic.value = ?";
                // If the institution exists and has active saml auth enabled
                if ($inst = get_records_sql_array("SELECT ai.institution FROM {auth_instance} ai
                                                   JOIN {auth_instance_config} aic ON aic.instance = ai.id
                                                   WHERE aic.field = 'institutionvalue'
                                                   AND " . $instmatch . "
                                                   AND ai.authname = 'saml'
                                                   AND ai.active = 1", array($attr))) {
                    // All good so continue
                    log_debug('institution ' . $inst[0]->institution . ' is all ready');
                    continue;
                }
                else if ($inst = get_records_sql_array("SELECT ai.institution FROM {auth_instance} ai
                                                        JOIN {auth_instance_config} aic ON aic.instance = ai.id
                                                        WHERE aic.field = 'institutionvalue'
                                                        AND " . $instmatch . "
                                                        AND ai.authname = 'saml'", array($attr))) {
                    // Institution exists but SAML auth not active - so make it active
                    log_debug('institution ' . $inst[0]->institution . ' SAML auth is now active');
                    set_field('auth_instance', 'active', 1, 'institution', $inst[0]->institution, 'authname', 'saml');
                }
                // Because we can't find a SAML mapping of IdP institution to Mahara institution we will try and see if there is a Mahara institution with same name as IdP institution value
                else if ($institution = get_record('institution', 'name', $attr)) {
                    // Institution exists but no SAML auth instance - so create one
                    add_institution_saml_auth($attr, $configs);
                    log_debug('institution ' . $attr . ' SAML auth is added');
                }
                else {
                    // Institution does not exist - create the institution and the SAML instance
                    $institution = institution_generate_name($attr);
                    $displayname = isset($configs['organisationname']) && !empty($saml_attributes[$configs['organisationname']->value][$index]) ? $saml_attributes[$configs['organisationname']->value][$index] : $attr;
                    $newinstitution = new Institution();
                    $newinstitution->initialise($institution, $displayname);
                    $newinstitution->commit();
                    add_institution_saml_auth($institution, $configs);
                    log_debug('institution ' . $attr . ' is now created and SAML auth is added');
                }
            }
        }
    }
    // find all the possible institutions/auth instances of type saml
    $instances = recordset_to_array(get_recordset_sql("SELECT * FROM {auth_instance_config} aic, {auth_instance} ai WHERE ai.id = aic.instance AND ai.authname = 'saml' AND ai.active = 1 AND aic.field = 'institutionattribute'"));
    if ($instances) {
        foreach ($instances as $row) {
            $institutions[]= $row->instance . ':' . $row->institution . ':' . $row->value;
            if (isset($saml_attributes[$row->value])) {
                // does this institution use a regex match against the institution check value?
                if ($configvalue = get_record('auth_instance_config', 'instance', $row->instance, 'field', 'institutionregex')) {
                    $is_regex = (boolean) $configvalue->value;
                }
                else {
                    $is_regex = false;
                }
                if ($configvalue = get_record('auth_instance_config', 'instance', $row->instance, 'field', 'institutionvalue')) {
                    $institution_value = $configvalue->value;
                }
                else {
                    $institution_value = $row->institution;
                }

                if ($is_regex) {
                    foreach ($saml_attributes[$row->value] as $attr) {
                        if (preg_match('/' . trim($institution_value) . '/', $attr)) {
                            $instance = $row;
                            break;
                        }
                    }
                }
                else {
                    foreach ($saml_attributes[$row->value] as $attr) {
                        if ($attr == $institution_value) {
                            $instance = $row;
                            break;
                        }
                    }
                }
            }
        }
    }
    return $instance;
}

function add_institution_saml_auth($institution, $configs=array()) {
    $maxpriority = get_field_sql("SELECT MAX(priority) FROM {auth_instance} WHERE institution = ?", array($institution));
    $priority = is_null($maxpriority) ? 0 : $maxpriority + 1;
    $fordb = new stdClass();
    $fordb->instancename = 'saml';
    $fordb->priority = $priority;
    $fordb->institution = $institution;
    $fordb->authname = 'saml';
    $fordb->active = 1;
    $instanceid = insert_record('auth_instance', $fordb, 'id', true);
    // Add in the configs for new institution saml auth
    foreach ($configs as $k => $v) {
        if ($k == 'institutionvalue') {
            $v->value = $institution;
        }
        execute_sql("INSERT INTO {auth_instance_config} (instance, field, value) VALUES (?, ?, ?)", array($instanceid, $k, $v->value));
    }
    return $instanceid;
}

/**
 * present the IdP discovery screen if there are more than one
 * available - user selects ...
 *
 * @param string $list
 * @param string $preferred
*/
function auth_saml_disco_screen($list, $preferred) {
    // Find the metadata that is actually being used by an institution
    $activeauths = get_column_sql("SELECT aic.value FROM {auth_instance_config} aic
                                   JOIN {auth_instance} ai ON ai.id = aic.instance
                                   WHERE ai.authname = ? AND ai.active = 1
                                   AND aic.field = 'institutionidpentityid' AND aic.value IS NOT NULL",
                                  array('saml'));
    if (empty($activeauths)) {
        $activeauths = array();
    }

    foreach ($list as $key => $idp) {
        if (!in_array($key, $activeauths)) {
            unset($list[$key]);
        }
    }

    list ($cols, $html) = PluginAuthSaml::idptable($list, $preferred);
    $smarty = smarty(array(), array(), array(), array('pagehelp' => false, 'sidebars' => false));
    $smarty->assign('columns', $cols);
    $smarty->assign('idps', $html);
    $smarty->assign('preferred', $preferred);
    $smarty->assign('PAGEHEADING', get_string('disco', 'auth.saml'));
    $smarty->display('auth:saml:disco.tpl');
    exit;
}


/**
 * present the login-link screen where users are asked if they want to link
 * the current loggedin local account to the remote saml one
 *
 * @param string $remoteuser
 * @param string $currentuser
 */
function auth_saml_loginlink_screen($remoteuser, $currentuser) {
    $form = array(
        'name'           => 'loginlink',
        'renderer'       => 'div',
        'successcallback'  => 'auth_saml_loginlink_submit',
        'method'         => 'post',
        'plugintype'     => 'auth',
        'pluginname'     => 'saml',
        'elements'       => array(
                    'linklogins' => array(
                        'value' => '<div><strong>' . get_string('linkaccounts', 'auth.saml', $remoteuser, $currentuser) . '</strong></div><br/>'
                    ),
                    'submit' => array(
                        'type'  => 'submitcancel',
                        'value' => array(get_string('link','auth.saml'), get_string('cancel')),
                        'goto'  => get_config('wwwroot'),
                    ),
                    'link_submitted' => array(
                        'type'  => 'hidden',
                        'value' => 1
                    ),
                ),
        'dieaftersubmit' => false,
        'iscancellable'  => true
    );
    $form = pieform_instance($form);
    define('TITLE', get_string('link', 'auth.saml'));
    $smarty = smarty(array(), array(), array(), array('pagehelp' => false, 'sidebars' => false));
    $smarty->assign('form', $form->build());
    $smarty->display('form.tpl');
    exit;
}


/**
 * present the login screen for login-linking
 *
 * @param string $remoteuser
 */
function auth_saml_login_screen($remoteuser) {
    define('TITLE', get_string('logintolink', 'auth.saml', get_config('sitename')));
    $smarty = smarty(array(), array(), array(), array('pagehelp' => false, 'sidebars' => false));
    $smarty->assign('pagedescriptionhtml', get_string('logintolinkdesc', 'auth.saml', $remoteuser, get_config('sitename')));
    $smarty->assign('form', '<div id="loginform_container"><noscript><p>{str tag="javascriptnotenabled"}</p></noscript>' . saml_auth_generate_login_form());
    $smarty->assign('LOGINPAGE', true);
    $smarty->display('form.tpl');
    exit;
}


/**
 * Generates the login form specifically independent of the core Mahara one
 * we want a custom submit callback here - which PHP doesn't let you do via overloading (sigh)
 * so - the only thing that is different here is the form name and the successcallback, and submit = true
 *
 */
function saml_auth_generate_login_form() {
    if (!get_config('installed')) {
        return;
    }
    if (count_records('institution', 'registerallowed', 1, 'suspended', 0)) {
        $registerlink = '<a class="btn btn-primary btn-sm" href="' . get_config('wwwroot') . 'register.php">' . get_string('register') . '</a>';
    }
    else {
        $registerlink = '';
    }
    $loginform = get_login_form_js(pieform(array(
        'name'       => 'auth_saml_login',
        'renderer'   => 'div',
        'submit'     => true,
        'successcallback'  => 'auth_saml_login_submit',
        'plugintype' => 'auth',
        'pluginname' => 'internal',
        'autofocus'  => false,
        'elements'   => array(
            'login_username' => array(
                'type'        => 'text',
                'title'       => get_string('username') . ':',
                'description' => get_string('usernamedescription'),
                'defaultvalue' => param_exists('login_username') ? param_variable('login_username') : '',
                'rules' => array(
                    'required'    => true
                )
            ),
            'login_password' => array(
                'type'        => 'password',
                'title'       => get_string('password') . ':',
                'description' => get_string('passworddescription'),
                'defaultvalue'       => '',
                'rules' => array(
                    'required'    => true
                )
            ),
            'submit' => array(
                'class' => 'btn-primary btn-block',
                'type'  => 'submit',
                'value' => get_string('login')
            ),
            'register' => array(
                'value' => '<div id="login-helplinks" class="card-footer"><small>' . $registerlink
                    . '<a href="' . get_config('wwwroot') . 'forgotpass.php">' . get_string('lostusernamepassword') . '</a></small></div>'
            ),
            'loginsaml' => array(
                'value' => ((count_records('auth_instance', 'authname', 'saml') == 0) ? '' : '<a href="' . get_config('wwwroot') . 'auth/saml/index.php">' . get_string('login', 'auth.saml') . '</a>')
            ),
        )
    )));

    return $loginform;
}


/**
 * Take a username and password and try to authenticate the
 * user
 *
 * Copied and modified from core LiveUser->login()
 *
 * @param  string $username
 * @param  string $password
 * @return bool
 */
function login_test_all_user_authinstance($username, $password) {
    global $USER;

    // do the normal user lookup
    $sql = 'SELECT
                *,
                ' . db_format_tsfield('expiry') . ',
                ' . db_format_tsfield('lastlogin') . ',
                ' . db_format_tsfield('lastlastlogin') . ',
                ' . db_format_tsfield('lastaccess') . ',
                ' . db_format_tsfield('suspendedctime') . ',
                ' . db_format_tsfield('ctime') . '
            FROM
                {usr}
            WHERE
                LOWER(username) = ?';
    $user = get_record_sql($sql, array(strtolower($username)));

    // throw out unknown users
    if ($user == false) {
        throw new AuthUnknownUserException("\"$username\" is not known");
    }

    // stop right here if the site is closed for any reason
    if (get_config('siteclosedforupgrade')) {
        global $SESSION;
        $SESSION->add_error_msg(get_string('siteclosedlogindisabled', 'mahara', get_config('wwwroot') . 'admin/upgrade.php'), false);
        return false;
    }
    if (get_config('siteclosedbyadmin')) {
        global $SESSION;
        $SESSION->add_error_msg(get_string('siteclosed'));
        return false;
    }

    // Build up a list of authinstance that can be tried for this user - typically
    // internal, or ldap - definitely NOT none, saml, or xmlrpc
    $instances = array();

    // all other candidate auth_instances
    $sql = 'SELECT ai.* from {auth_instance} ai INNER JOIN {auth_remote_user} aru
                ON ai.id = aru.authinstance
                WHERE ai.active = 1 AND ai.authname NOT IN(\'saml\', \'xmlrpc\', \'none\') AND aru.localusr = ?';
    $authinstances = get_records_sql_array($sql, array($user->id));
    if ($authinstances) {
        foreach ($authinstances as $authinstance) {
            $instances[]= $authinstance->id;
        }
    }

    // determine the internal authinstance ID associated with the base 'mahara'
    // 'no institution' - use this is a default fallback login attempt
    $authinstance = get_record('auth_instance', 'institution', 'mahara', 'authname', 'internal', 'active', 1);
    $instances[]= $authinstance->id;

    // test each auth_instance candidate associated with this user
    foreach ($instances as $authinstanceid) {
        $auth = AuthFactory::create($authinstanceid);
        // catch the AuthInstanceException that allows authentication plugins to
        // fail but pass onto the next possible plugin
        try {
            if ($auth->authenticate_user_account($user, $password)) {
                $USER->reanimate($user->id, $auth->instanceid);
                // Check for a suspended institution - should never be for 'mahara'
                $authinstance = get_record_sql('
                    SELECT i.suspended, i.displayname
                    FROM {institution} i JOIN {auth_instance} a ON a.institution = i.name
                    WHERE a.id = ?', array($authinstanceid));
                if ($authinstance->suspended) {
                    continue;
                }
                // we havea winner
                return true;
            }
        }
        catch (AuthInstanceException $e) {
            // auth fail - try the next one
            continue;
        }
    }
    // all fail
    return false;
}


/**
 * Called when the auth_saml_login form is submitted. Validates the user and password, and
 * if they are valid, starts a new session for the user.
 *
 * Copied and modified from core login_submit
 *
 * @param object $form   The Pieform form object
 * @param array  $values The submitted values
 */
function auth_saml_login_submit(Pieform $form, $values) {
    global $SESSION, $USER;

    $username      = trim($values['login_username']);
    $password      = $values['login_password'];
    $authenticated = false;
    $oldlastlogin  = 0;

    try {
        $authenticated = login_test_all_user_authinstance($username, $password);
        if (empty($authenticated)) {
            $SESSION->add_error_msg(get_string('loginfailed'));
            redirect('/auth/saml/index.php');
        }

    }
    catch (AuthUnknownUserException $e) {
        $SESSION->add_error_msg(get_string('loginfailed'));
        redirect('/auth/saml/index.php');
    }

    auth_check_admin_section();

    // Check if the user's account has been deleted
    if ($USER->deleted) {
        $USER->logout();
        die_info(get_string('accountdeleted'));
    }

    // Check if the user's account has expired
    if ($USER->expiry > 0 && time() > $USER->expiry) {
        $USER->logout();
        die_info(get_string('accountexpired'));
    }

    // Check if the user's account has become inactive
    $inactivetime = get_config('defaultaccountinactiveexpire');
    if ($inactivetime && $oldlastlogin > 0
        && $oldlastlogin + $inactivetime < time()) {
        $USER->logout();
        die_info(get_string('accountinactive'));
    }

    // Check if the user's account has been suspended
    if ($USER->suspendedcusr) {
        $suspendedctime  = strftime(get_string('strftimedaydate'), $USER->suspendedctime);
        $suspendedreason = $USER->suspendedreason;
        $USER->logout();
        die_info(get_string('accountsuspended', 'mahara', $suspendedctime, $suspendedreason));
    }

    // User is allowed to log in
    auth_check_required_fields();

    // all happy - carry on now
    redirect('/auth/saml/index.php');
}

function auth_saml_migrate_check($instance, $saml_attributes) {
    global $USER, $SESSION;

    $idp = $SESSION->get('migrateidp');
    $idpattribute = $SESSION->get('migrateidpkey');
    if (isset($saml_attributes[$idpattribute]) && $saml_attributes[$idpattribute][0] == $idp) {
        // successfully proved they can log into the IdP so return the saml_attribure array
        return array('instance' => $instance, 'saml_attributes' => $saml_attributes);
    }
    return false;
}
