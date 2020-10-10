<?php
/**
 *
 * @package    mahara
 * @subpackage admin
 * @author     Catalyst IT Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 * @copyright  For copyright information on Mahara, please see the README file distributed with this software.
 *
 */
define('INTERNAL', 1);
define('INSTITUTIONALADMIN', 1);
require(dirname(dirname(dirname(__FILE__))) . '/init.php');
define('TITLE', get_string('Institutions', 'admin'));
define('SECTION_PLUGINTYPE', 'core');
define('SECTION_PLUGINNAME', 'admin');

require_once('license.php');
define('MENUITEM', 'manageinstitutions/institutions');

$institution = param_variable('i', '');
$add         = param_boolean('add');
$edit        = param_boolean('edit');
$delete      = param_boolean('delete');
define('SUBSECTIONHEADING', get_field('institution', 'displayname', 'name', $institution));

$query = param_variable('query', '');
$offset = param_integer('offset', 0);
$limit = param_integer('limit', 0);
$limit = user_preferred_limit($limit, 'itemsperpage');

$customthemedefaults = array(
    'background'   => array('type' => 'color', 'value' => '#530E53'),
    'backgroundfg' => array('type' => 'color', 'value' => '#FFFFFF'),
    'link'         => array('type' => 'color', 'value' => '#255ECC'),
    'headings'     => array('type' => 'color', 'value' => '#530E53'),
    'navbg'        => array('type' => 'color', 'value' => '#8A458A'),
    'navfg'        => array('type' => 'color', 'value' => '#FFFFFF'),
);

if (!$USER->get('admin')) {
    // Institutional admins with only 1 institution go straight to the edit page for that institution
    // They cannot add or delete institutions, or edit an institution they don't administer
    $add = false;
    $delete = false;
    if (!empty($institution) && !$USER->is_institutional_admin($institution)) {
        $institution = '';
        $edit = false;
    }
    if (empty($institution) && count($USER->get('admininstitutions')) == 1) {
        redirect(get_config('wwwroot') . 'admin/users/institutions.php?i='
                 . key($USER->get('admininstitutions')));
    }
}

if ($institution || $add) {
    define('SECTION_PAGE', 'institutionedit');
    $authinstances = auth_get_auth_instances_for_institution($institution);
    if (false == $authinstances) {
        $authinstances = array();
    }

    if ($delete) {
        function delete_validate(Pieform $form, $values) {
            // Ensure the institution has no members left
            if ($members = get_field('usr_institution', 'COUNT(*)', 'institution', $values['i'])) {
                $form->set_error('submit', get_string('institutionstillhas', 'admin', get_string('nmembers', 'group', $members)));
            }

            // If some users are still using one of this institution's authinstances, it's okay if
            // we can find a default authinstance for those users, otherwise it's an error.
            if ($authinstanceids = get_column('auth_instance', 'id', 'institution', $values['i'])) {
                $badusers = count_records_select(
                    'usr',
                    'authinstance IN (' . join(',', array_fill(0, count($authinstanceids), '?')) . ')',
                    $authinstanceids
                );
                if ($badusers) {
                    $defaultauth = record_exists('auth_instance', 'institution', 'mahara', 'authname', 'internal', 'active', 1);
                    if ($values['i'] == 'mahara' || !$defaultauth) {
                        $form->set_error(
                            'submit',
                            get_string('institutionauthinuseby', 'admin', get_string('nusers', 'mahara', $badusers))
                        );
                    }
                }
            }
        }

        function delete_cancel_submit() {
            redirect('/admin/users/institutions.php');
        }

        function delete_submit(Pieform $form, $values) {
            global $SESSION, $USER;

            $authinstanceids = get_column('auth_instance', 'id', 'institution', $values['i']);
            $collectionids = get_column('collection', 'id', 'institution', $values['i']);
            $viewids = get_column('view', 'id', 'institution', $values['i']);
            $artefactids = get_column('artefact', 'id', 'institution', $values['i']);
            $regdataids = get_column('institution_registration', 'id', 'institution', $values['i']);
            $host = get_field('host', 'wwwroot', 'institution', $values['i']);

            db_begin();
            require_once(get_config('libroot') . 'collection.php');
            if ($submittedcolids = get_column('collection', 'id', 'submittedhost', $host)) {
                foreach ($submittedcolids as $id) {
                    $collection = new Collection($id);
                    $collection->release($USER);
                }
            }
            if ($collectionids) {
                foreach ($collectionids as $collectionid) {
                    $collection = new Collection($collectionid);
                    $collection->delete();
                }
            }
            require_once(get_config('libroot') . 'view.php');
            if ($submittedviewids = get_column('view', 'id', 'submittedhost', $host)) {
                foreach ($submittedviewids as $id) {
                    $view = new View($id);
                    $view->release($USER);
                }
            }
            if ($viewids) {
                foreach ($viewids as $viewid) {
                    $view = new View($viewid);
                    $view->delete();
                }
            }
            if ($artefactids) {
                require_once(get_config('docroot') . 'artefact/lib.php');
                foreach ($artefactids as $artefactid) {
                    try {
                        $a = artefact_instance_from_id($artefactid);
                        $a->delete();
                    }
                    catch (ArtefactNotFoundException $e) {
                        // Awesome, it's already gone.
                    }
                }
            }

            // If any users are still using this institution's authinstances, change them now.
            if ($authinstanceids) {
                execute_sql("
                    UPDATE {usr}
                    SET authinstance = (
                        SELECT MIN(id) FROM {auth_instance} WHERE institution = 'mahara' AND authname = 'internal' AND active = 1
                    )
                    WHERE authinstance IN (" . join(',', array_fill(0, count($authinstanceids), '?')) . ')',
                    $authinstanceids
                );
            }

            foreach ($authinstanceids as $id) {
                // Check if authinstance is SAML and this is the only institution using the related idp metadata
                if ($idps = get_records_sql_array("SELECT aic.value FROM {auth_instance} ai
                                                  JOIN {auth_instance_config} aic ON aic.instance = ai.id
                                                  WHERE aic.field = 'institutionidpentityid'
                                                  AND ai.authname = 'saml' AND ai.id = ?", array($id))) {
                    foreach ($idps as $idp) {
                        if (!count_records_sql("SELECT COUNT(*) FROM {auth_instance_config} aic
                                                WHERE value = ? AND instance != ?", array($idp->value, $id))) {
                            safe_require('auth', 'saml');
                            $idpfile = AuthSaml::prepare_metadata_path($idp->value);
                            if (file_exists($idpfile)) {
                                unlink($idpfile);
                            }
                        }
                    }
                }
                delete_records('auth_instance_config', 'instance', $id);
                delete_records('auth_remote_user', 'authinstance', $id);
            }

            foreach ($regdataids as $id) {
                delete_records('institution_registration_data', 'registration_id', $id);
            }

            // The institution should have been removed from favourites lists when the members were removed,
            // but make sure it's gone.
            execute_sql('DELETE FROM {favorite_usr} WHERE favorite IN (SELECT id FROM {favorite} WHERE institution = ?)', array($values['i']));
            delete_records('favorite', 'institution', $values['i']);

            execute_sql("UPDATE {group} SET institution = 'mahara' WHERE institution = ?", array($values['i']));
            delete_records('auth_instance', 'institution', $values['i']);

            delete_records('host', 'institution', $values['i']);
            delete_records('institution_locked_profile_field', 'name', $values['i']);
            delete_records('usr_institution_request', 'institution', $values['i']);
            delete_records('view_access', 'institution', $values['i']);
            delete_records('institution_data', 'institution', $values['i']);
            delete_records('institution_registration', 'institution', $values['i']);
            delete_records('site_content', 'institution', $values['i']);
            delete_records('institution_config', 'institution', $values['i']);
            if (db_table_exists('usr_custom_layout')) {
                delete_records('usr_custom_layout', 'institution', $values['i']);
            }
            delete_records('usr_registration', 'institution', $values['i']);
            if ($versions = get_records_assoc('site_content_version', 'institution', $values['i'])) {
                foreach($versions as $version) {
                    delete_records('usr_agreement', 'sitecontentid', $version->id);
                }
            }
            delete_records('site_content_version', 'institution', $values['i']);
            delete_records('oauth_server_registry', 'institution', $values['i']);
            delete_records('institution', 'name', $values['i']);
            db_commit();
            clear_menu_cache();
            $SESSION->add_ok_msg(get_string('institutiondeletedsuccessfully', 'admin'));
            redirect('/admin/users/institutions.php');
        }
        $form = array(
            'name' => 'delete',
            'elements' => array(
                'i' => array(
                    'type' => 'hidden',
                    'value' => $institution
                ),
                'delete' => array(
                    'type' => 'hidden',
                    'value' => 1
                ),
                'submit' => array(
                    'type' => 'submitcancel',
                    'subclass' => array('btn-danger'),
                    'value' => array(get_string('yes'), get_string('no'))
                )
            )
        );
        $deleteform = pieform($form);
        $smarty = smarty();
        $smarty->assign('delete_form', $deleteform);
        $smarty->assign('institutionname', get_field('institution', 'displayname', 'name', $institution));
        $smarty->display('admin/users/institutions.tpl');
        exit;
    }

    $instancearray = array();
    $instancestring = '';
    $c = count($authinstances);
    $inuse = '';

    $sitelockedfields = (array) get_column('institution_locked_profile_field', 'profilefield', 'name', 'mahara');

    if (!$add) {
        $data = get_record('institution', 'name', $institution);
        $data->commentsortorder = get_config_institution($institution, 'commentsortorder');
        $data->commentthreaded = get_config_institution($institution, 'commentthreaded');
        $data->allowinstitutionsmartevidence = get_config_institution($institution, 'allowinstitutionsmartevidence');
        $data->reviewselfdeletion = get_config_institution($institution, 'reviewselfdeletion');
        $data->progresscompletion = get_config_institution($institution, 'progresscompletion');
        $data->showonlineusers = (is_isolated() && $data->showonlineusers == 2 ? 1 : $data->showonlineusers);
        $lockedprofilefields = (array) get_column('institution_locked_profile_field', 'profilefield', 'name', $institution);

        // TODO: Find a better way to work around Smarty's minimal looping logic
        if (!empty($authinstances)) {
            foreach($authinstances as $key => $val) {
                $authinstances[$key]->index = $key;
                $authinstances[$key]->total = $c;
                $instancearray[] = (int)$val->id;
            }

            $instancestring = implode(',',$instancearray);
            $inuserecords = array();
            if ($records = get_records_sql_assoc('select authinstance, count(id) from {usr} where authinstance in ('.$instancestring.') group by authinstance', array())) {
                foreach ($records as $record) {
                    $inuserecords[] = $record->authinstance;
                }
            }
            $inuse = implode(',',$inuserecords);
        }
    }
    else {
        $data = new stdClass();
        $data->displayname = '';
        $data->expiry = null;
        if (!get_config('usersuniquebyusername')) {
            $data->registerallowed = 0;
            $data->registerconfirm = 1;
        }
        $data->theme = 'sitedefault';
        $data->defaultmembershipperiod = null;
        $data->showonlineusers = is_isolated() ? 1 : 2;
        $data->allowinstitutionpublicviews = get_config('allowpublicviews') ? 1 : 0;
        $data->allowinstitutionsmartevidence = 0;
        $data->progresscompletion = 0;
        $data->tags = 0;
        $data->licensemandatory = 0;
        $data->licensedefault = '';
        $data->dropdownmenu = get_config('dropdownmenu') ? 1 : 0;
        $data->skins = get_config('skins') ? 1 : 0;
        $data->commentsortorder = 'earliest';
        $data->commentthreaded = false;
        $lockedprofilefields = array();
    }
    $themeoptions = get_institution_themes($institution);
    $themeoptions['sitedefault'] = '- ' . get_string('sitedefault', 'admin') . ' (' . $themeoptions[get_config('theme')] . ') -';
    uksort($themeoptions, 'theme_sort');
    if (validate_theme($data->theme, $institution, $add) === false) {
        $data->theme = 'sitedefault';
    }
    $showonlineusersoptions = array('0' => get_string('none'),
                                    '1' => get_string('institutiononly', 'admin'),
                                    '2' => get_string('all', 'admin'));

    $isolatedinstitutions = is_isolated();
    if ($isolatedinstitutions) {
        unset($showonlineusersoptions['2']);
    }
     $sitename = get_config('sitename');

    safe_require('artefact', 'internal');
    $elements = array(
        'add' => array(
            'type'   => 'hidden',
            'value'  => true,
            'ignore' => !$add
        ),
        'inuse' => array(
            'type'   => 'hidden',
            'value'  => $inuse,
            'id'     => 'inuse',
            'ignore' => $add
        ),
        'i' => array(
            'type'   => 'hidden',
            'value'  => $institution,
            'ignore' => $add
        ),
        'displayname' => array(
            'type' => 'text',
            'title' => get_string('institutionname', 'admin'),
            'defaultvalue' => $data->displayname,
            'rules' => array(
                'required'  => true,
                'maxlength' => 255
            ),
            'help'   => true,
        ),
    );
    if (!$add) {
        $elements['shortname'] = array(
                'type' => 'select',
                'title' => get_string('institutionshortname', 'admin'),
                'defaultvalue' => $data->name,
                'description' => get_string('institutionshortnamedescription', 'admin'),
                'options' => array($data->name => $data->name),
        );
    }
    if ($USER->get('admin') && $institution != 'mahara') {
       $elements['expiry'] = array(
            'type'         => 'calendar',
            'title'        => get_string('institutionexpiry', 'admin'),
            'description'  => get_string('institutionexpirydescription', 'admin', hsc($sitename)),
            'defaultvalue' => is_null($data->expiry) ? null : strtotime($data->expiry),
            'help'         => true,
            'minyear'      => date('Y') - 2,
            'maxyear'      => date('Y') + 10,
            'caloptions' => array(
                'showsTime'      => false,
            ),
        );
    }
    if ($USER->get('admin')) {
        $elements['authplugin'] = array(
            'type'    => 'authlist',
            'title'   => get_string('authplugin', 'admin'),
            'options' => $authinstances,
            'instancearray' => $instancearray,
            'instancestring' => $instancestring,
            'institution' => $institution,
            'help'   => 'top',
            'ignore' => ($add)
        );
    }

    if (!$add && empty($authinstances)) {
        if ($USER->get('admin')) {
            $SESSION->add_error_msg(get_string('adminnoauthpluginforinstitution', 'admin'));
        }
        else {
            $SESSION->add_error_msg(get_string('noauthpluginforinstitution', 'admin'));
        }
    }

    if (!get_config('usersuniquebyusername')) {
        $elements['registerallowed'] = array(
            'type'         => 'switchbox',
            'title'        => get_string('registrationallowed', 'admin'),
            'description'  => get_string('registrationalloweddescription5', 'admin'),
            'defaultvalue' => $data->registerallowed,
            'help'   => true,
        );
        $elements['registerconfirm'] = array(
            'type'         => 'switchbox',
            'title'        => get_string('registrationconfirm', 'admin'),
            'description'  => get_string('registrationconfirmdescription3', 'admin'),
            'disabled'     => get_config('requireregistrationconfirm') == true,
            'defaultvalue' => ($isolatedinstitutions ? true : $data->registerconfirm),
        );
    }

    // Some fields to hide from the default institution config screen
    if (empty($data->name) || $data->name != 'mahara') {
        $elements['defaultmembershipperiod'] = array(
            'type'         => 'expiry',
            'title'        => get_string('defaultmembershipperiod', 'admin'),
            'description'  => get_string('defaultmembershipperioddescription', 'admin'),
            'defaultvalue' => $data->defaultmembershipperiod,
            'help'   => true,
        );

        $languages = get_languages();
        // Get the default language. If the institution has one stored, use that. Otherwise, use 'sitedefault'
        $defaultlang = false;
        if (!empty($data->name)) {
            $defaultlang = get_config_institution($data->name, 'lang');
        }
        // If the defaultlang they provided is no longer valid, use "site default"
        if (!$defaultlang || !array_key_exists($defaultlang, $languages)) {
            $defaultlang = 'sitedefault';
        }
        $elements['lang'] = array(
            'type' => 'select',
            'defaultvalue' => $defaultlang,
            'title' => get_string('institutionlanguage', 'admin'),
            'description' => get_string('institutionlanguagedescription', 'admin'),
            'options' => array_merge(array('sitedefault' => get_string('sitedefault', 'admin') . ' (' . $languages[get_config('lang')] . ')'), $languages),
            'ignore' => (count($languages) < 2),
        );
    }

    $elements['logo'] = array(
        'type'        => 'file',
        'title'       => get_string('Logo', 'admin'),
        'description' => get_string('logodescription1', 'admin'),
        'maxfilesize' => get_max_upload_size(false),
    );
    if (!empty($data->logo)) {
        $logourl = get_config('wwwroot') . 'thumb.php?type=logobyid&id=' . $data->logo;
        $elements['logohtml'] = array(
            'type'        => 'html',
            'value'       => '<img src="' . $logourl . '" alt="' . get_string('Logo', 'admin') . '">',
        );
        $elements['deletelogo'] = array(
            'type'        => 'switchbox',
            'title'       => get_string('deletelogo', 'admin'),
            'description' => get_string('deletelogodescription2', 'admin'),
        );
    }

    // logo-xs
    $elements['logoxs'] = array(
        'type'        => 'file',
        'title'       => get_string('Logomobile', 'admin'),
        'description' => get_string('logoxsdescription', 'admin'),
        'maxfilesize' => get_max_upload_size(false),
    );
    if (!empty($data->logoxs)) {
        $logoxsurl = get_config('wwwroot') . 'thumb.php?type=logobyid&id=' . $data->logoxs;
        $elements['logoxshtml'] = array(
            'type'        => 'html',
            'value'       => '<img src="' . $logoxsurl . '" alt="' . get_string('Logomobile', 'admin') . '">',
        );
        $elements['deletelogoxs'] = array(
            'type'        => 'switchbox',
            'title'       => get_string('deletelogoxsmobile', 'admin'),
            'description' => get_string('deletelogoxsdescription3', 'admin'),
        );
    }

    if (empty($data->name) || $data->name != 'mahara') {
        if (!empty($data->style)) {
            $customtheme = get_records_menu('style_property', 'style', $data->style, '', 'field,value');
        }
        $elements['theme'] = array(
            'type'         => 'select',
            'title'        => get_string('theme'),
            'description'  => get_string('sitethemedescription','admin'),
            'defaultvalue' => $data->theme ? $data->theme : 'sitedefault',
            'collapseifoneoption' => true,
            'options'      => $themeoptions,
            'help'         => true,
        );
        $elements['customthemefs'] = array(
            'type'         => 'fieldset',
            'class'        => 'customtheme' . ($elements['theme']['defaultvalue'] != 'custom' ? ' js-hidden' : ''),
            'legend'       => get_string('customtheme', 'admin'),
            'elements'     => array(),
        );
        foreach ($customthemedefaults as $name => $styledata) {
            $elements['customthemefs']['elements'][$name] = array(
                'type'         => $styledata['type'],
                'title'        => get_string('customtheme.' . $name, 'admin'),
                'defaultvalue' => isset($customtheme[$name]) ? $customtheme[$name] : $styledata['value'],
            );
        }
        $elements['customthemefs']['elements']['resetcustom'] = array(
            'type'         => 'switchbox',
            'class'        => 'nojs-hidden-inline',
            'title'        => get_string('resetcolours', 'admin'),
            'description'  => get_string('resetcoloursdesc2', 'admin'),
        );
        if (get_config('dropdownmenuenabled')) {
            $elements['dropdownmenu'] = array(
                'type'         => 'switchbox',
                'title'        => get_string('dropdownmenu', 'admin'),
                'description'  => get_string('dropdownmenudescriptioninstitution2','admin'),
                'defaultvalue' => $data->dropdownmenu,
                'help'         => true,
            );
        }
    }
    // The skins checkbox should be shown for the default institution
    if (get_config('skins')) {
        $elements['skins'] = array(
            'type' => 'switchbox',
            'title' => get_string('skins', 'admin'),
            'description' => get_string('skinsinstitutiondescription2', 'admin'),
            'defaultvalue' => $data->skins,
        );
    }
    $elements['commentsortorder'] = array(
        'type' => 'select',
        'title' => get_string('commentsortorder', 'admin'),
        'description' => get_string('commentsortorderdescription', 'admin'),
        'defaultvalue' => $data->commentsortorder,
        'options' => array('earliest' => get_string('earliest'),
                           'latest' => get_string('latest'),
                          ),
        'help' => true,
    );
    $elements['commentthreaded'] = array(
        'type' => 'switchbox',
        'title' => get_string('commentthreaded', 'admin'),
        'description' => get_string('commentthreadeddescription', 'admin'),
        'defaultvalue' => $data->commentthreaded,
    );
    // Some more fields that are hidden from the default institution
    if (empty($data->name) || $data->name != 'mahara') {
        $elements['showonlineusers'] = array(
            'type'                  => 'select',
            'disabled'              => get_config('showonlineuserssideblock') ? '' : 'disabled',
            'title'                 => get_string('showonlineusers', 'admin'),
            'description'           => get_string('showonlineusersdescription','admin'),
            'defaultvalue'          => $data->showonlineusers,
            'collapseifoneoption'   => true,
            'options'               => $showonlineusersoptions,
        );
        if (get_config('licensemetadata')) {
            $elements['licensemandatory'] = array(
                'type'         => 'switchbox',
                'title'        => get_string('licensemandatory', 'admin'),
                'description'  => get_string('licensemandatorydescription1','admin'),
                'defaultvalue' => $data->licensemandatory,
            );
            $elements['licensedefault'] = license_form_el_basic(null, true);
            $elements['licensedefault']['title'] = get_string('licensedefault','admin');
            $elements['licensedefault']['description'] = get_string('licensedefaultdescription','admin');
            if ($data->licensedefault) {
                $elements['licensedefault']['defaultvalue'] = $data->licensedefault;
            }
        }
        if ($USER->get('admin') || get_config_plugin('artefact', 'file', 'institutionaloverride')) {
            $elements['defaultquota'] = array(
               'type'         => 'bytes',
               'title'        => get_string('defaultquota', 'artefact.file'),
               'description'  => get_string('defaultinstitutionquotadescription', 'admin'),
               'defaultvalue' => !empty($data->defaultquota) ? $data->defaultquota : get_config_plugin('artefact', 'file', 'defaultquota'),
            );
            $elements['updateuserquotas'] = array(
                'type'         => 'switchbox',
                'title'        => get_string('updateuserquotas', 'artefact.file'),
                'description'  => get_string('updateinstitutionuserquotasdesc2', 'admin'),
            );
        }
        else {
            $elements['defaultquota'] = array(
                'type' => 'text',
                'title' => get_string('defaultquota', 'artefact.file'),
                'value' => display_size(!empty($data->defaultquota) ? $data->defaultquota : get_config_plugin('artefact', 'file', 'defaultquota')),
                'disabled' => true,
            );
        }

        $elements['allowinstitutionpublicviews'] = array(
            'type'         => 'switchbox',
            'title'        => get_string('allowinstitutionpublicviews1', 'admin'),
            'description'  => get_string('allowinstitutionpublicviewsdescription3','admin'),
            'defaultvalue' => get_config('allowpublicviews') && $data->allowinstitutionpublicviews,
            'disabled'     => get_config('allowpublicviews') == false,
            'help'         => true,
        );

        if ($USER->get('admin')) {
            $elements['maxuseraccounts'] = array(
                'type'         => 'text',
                'title'        => get_string('maxuseraccounts1','admin'),
                'description'  => get_string('maxuseraccountsdescription','admin'),
                'defaultvalue' => empty($data->maxuseraccounts) ? '' : $data->maxuseraccounts,
                'rules'        => array(
                    'regex'     => '/^\d*$/',
                    'maxlength' => 8,
                ),
                'size'         => 5,
            );
        }
    }
    if (is_plugin_active('signoff', 'blocktype')) {
        $elements['progresscompletion'] = array(
          'type'         => 'switchbox',
          'title'        => get_string('progresscompletion', 'admin'),
          'description'  => get_string('progresscompletiondescription','admin'),
          'defaultvalue' => isset($data->progresscompletion) && $data->progresscompletion,
          'help'         => true,
        );
    }
    $elements['allowinstitutionsmartevidence'] = array(
        'type'         => 'switchbox',
        'title'        => get_string('allowinstitutionsmartevidence', 'admin'),
        'description'  => get_string('allowinstitutionsmartevidencedescription','admin'),
        'defaultvalue' => is_plugin_active('framework', 'module') && $data->allowinstitutionsmartevidence,
        'disabled'     => is_plugin_active('framework', 'module') == false,
        'help'         => true,
    );
    $elements['allowinstitutiontags'] = array(
        'type'         => 'switchbox',
        'title'        => get_string('allowinstitutiontags'),
        'description'  => get_string('allowinstitutiontagsdescription'),
        'defaultvalue' => $data->tags,
    );
    $elements['reviewselfdeletion'] = array(
        'type'         => 'switchbox',
        'title'        => get_string('reviewsselfdeletion', 'admin'),
        'description'  => get_string('reviewsselfdeletiondescription','admin'),
        'disabled'     => get_config('defaultreviewselfdeletion') == true,
        'defaultvalue' => get_config('defaultreviewselfdeletion') ? get_config('defaultreviewselfdeletion') : (isset($data->reviewselfdeletion) && $data->reviewselfdeletion),
    );
    $elements['lockedfields'] = array(
        'type' => 'fieldset',
        'class' => 'last with-formgroup',
        'legend' => get_string('Lockedfields', 'admin'),
        'collapsible' => true,
        'collapsed' => true,
        'elements' => array(),
    );
    if ($institution != 'mahara') {
        $elements['lockedfields']['elements']['description'] = array(
            'type' => 'html',
            'value' => get_string('disabledlockedfieldhelp1', 'admin', get_field('institution', 'displayname', 'name', 'mahara')),
        );
    }
    foreach (ArtefactTypeProfile::get_all_fields() as $field => $type) {
        $elements['lockedfields']['elements'][$field] = array(
            'type' => 'switchbox',
            'title' => get_string($field, 'artefact.internal'),
            'defaultvalue' => in_array($field, $lockedprofilefields) || ($institution != 'mahara' && in_array($field, $sitelockedfields)),
            'disabled' => $institution != 'mahara' && in_array($field, $sitelockedfields)
        );
    }

    // Check for active plugins institution settings.
    $elements['pluginsfields'] = array(
        'type' => 'fieldset',
        'legend' => get_string('pluginsfields', 'admin'),
        'collapsible' => true,
        'collapsed' => true,
        'elements' => array(),
    );

    // Get plugins institution settings.
    $instobj = null;
    if (!$add && $institution != '') {
        $instobj = new Institution();
        $instobj->findByName($institution);
    }
    $elements['pluginsfields']['elements'] = array_merge($elements['pluginsfields']['elements'],
            plugin_institution_prefs_form_elements($instobj));

    // Remove plugin fieldset if no fields.
    if (empty($elements['pluginsfields']['elements'])) {
        unset($elements['pluginsfields']);
    }

    $elements['submit'] = array(
        'type' => 'submitcancel',
        'subclass' => array('btn-primary'),
        'value' => array(get_string('submit'), get_string('cancel'))
    );

    $institutionform = pieform(array(
        'name'     => 'institution',
        'renderer' => 'div',
        'plugintype' => 'core',
        'pluginname' => 'admin',
        'elements' => $elements
    ));

}
else {
    // Get a list of institutions
    define('SECTION_PAGE', 'institutions');
    require_once(get_config('libroot') . 'institution.php');
    if (!$USER->get('admin')) { // Filter the list for institutional admins
        $filter      = $USER->get('admininstitutions');
        $showdefault = false;
    }
    else {
        $filter      = false;
        $showdefault = true;
    }
    $data = build_institutions_html($filter, $showdefault, $query, $limit, $offset, $count);

    $smarty = smarty(array('lib/pieforms/static/core/pieforms.js', 'paginator'));
    setpageicon($smarty, 'icon-university');
    $smarty->assign('results', $data);
    $smarty->assign('countinstitutions', $count);

    /*search institution form*/
    $searchform = pieform(array(
        'name'   => 'search',
        'renderer' => 'div',
        'class' => 'form-inline with-heading',
        'autofocus' => false,
        'elements' => array(
            'inputgroup' => array(
                'type'  => 'fieldset',
                'title' => get_string('Query') . ': ',
                'class' => 'input-group form-inline',
                'elements'     => array(
                    'query' => array(
                        'type'  => 'text',
                        'defaultvalue' => $query,
                        'hiddenlabel' => true,
                        'value' => '',
                        'placeholder' => get_string('search'),
                        'title' => get_string('search'),
                    ),
                    'submit' => array(
                        'type'  => 'button',
                        'usebuttontag' => true,
                        'class' => 'btn-secondary input-group-append',
                        'value' => get_string('search'),
                    )
                ),
            ),
        ),
    ));
    $smarty->assign('searchform', $searchform);

    $js = <<< EOF
    jQuery(function($) {
      p = {$data['pagination_js']}
      $('#search_submit').on('click', function(event) {
        $('#messages').empty();
        var params = {'query': $('#search_query').val()};
        p.sendQuery(params);
        event.preventDefault();
      });
    });
EOF;

    $smarty->assign('INLINEJAVASCRIPT', $js);
    $smarty->assign('siteadmin', $USER->get('admin'));
    $smarty->assign('PAGEHEADING', get_string('admininstitutions', 'admin'));
    $smarty->display('admin/users/institutions.tpl');
    exit;
}

function institution_validate(Pieform $form, $values) {
    global $USER, $institution, $add;
    if ($add) {
        try {
            $check = institution_generate_name($values['displayname']);
        }
        catch (ParamOutOfRangeException $e) {
            $form->set_error('displayname', get_string('institutionnameinvalid', 'admin'));
        }
    }
    else {
        $check = strtolower($values['displayname']);
        $check = preg_replace('/[^a-z0-9]/', '', $check);
        if (strlen($check) < 1 || $check === '0') {
            $form->set_error('displayname', get_string('institutionnameinvalid', 'admin'));
        }
    }

    if ($USER->get('admin') || get_config_plugin('artefact', 'file', 'institutionaloverride')) {
        if (get_config_plugin('artefact', 'file', 'maxquotaenabled') && get_config_plugin('artefact', 'file', 'maxquota') < $values['defaultquota']) {
            $form->set_error('defaultquota', get_string('maxquotatoolow', 'artefact.file'));
        }
    }

    if (get_config('licensemetadata') && !empty($values['licensemandatory']) &&
        (isset($values['licensedefault']) && $values['licensedefault'] == '')) {
        $form->set_error('licensedefault', get_string('licensedefaultmandatory', 'admin'));
    }

    // Check uploaded logo
    if (!empty($values['logo'])) {
        require_once('file.php');
        require_once('uploadmanager.php');
        $um = new upload_manager('logo');
        if ($error = $um->preprocess_file()) {
            $form->set_error('logo', $error);
            return false;
        }

        $imageinfo = getimagesize($values['logo']['tmp_name']);
        if (!$imageinfo || !is_image_type($imageinfo[2])) {
            $form->set_error('logo', get_string('filenotimage'));
            return false;
        }

        // Check the file isn't greater than the max allowable size
        $width          = $imageinfo[0];
        $height         = $imageinfo[1];
        $imagemaxwidth  = get_config('imagemaxwidth');
        $imagemaxheight = get_config('imagemaxheight');
        if ($width > $imagemaxwidth || $height > $imagemaxheight) {
            $form->set_error('logo', get_string('profileiconimagetoobig', 'artefact.file', $width, $height, $imagemaxwidth, $imagemaxheight));
        }
    }

    // Check uploaded small logo
    if (!empty($values['logoxs'])) {
        require_once('file.php');
        require_once('uploadmanager.php');
        $um = new upload_manager('logoxs');
        if ($error = $um->preprocess_file()) {
            $form->set_error('logoxs', $error);
            return false;
        }

        $imageinfo = getimagesize($values['logoxs']['tmp_name']);
        if (!$imageinfo || !is_image_type($imageinfo[2])) {
            $form->set_error('logoxs', get_string('filenotimage'));
            return false;
        }

        // Check the file isn't greater than the max allowable size
        $width          = $imageinfo[0];
        $height         = $imageinfo[1];
        $imagemaxwidth  = get_config('imagemaxwidth');
        $imagemaxheight = get_config('imagemaxheight');
        if ($width > $imagemaxwidth || $height > $imagemaxheight) {
            $form->set_error('logoxs', get_string('profileiconimagetoobig', 'artefact.file', $width, $height, $imagemaxwidth, $imagemaxheight));
        }
        else {
            $ratio = $width / $height;
            if ($ratio != 1) {
                $form->set_error('logoxs', get_string('logoxsnotsquare', 'artefact.file'));
            }
        }
    }

    if (!empty($values['lang']) && $values['lang'] != 'sitedefault' && !array_key_exists($values['lang'], get_languages())) {
        $form->set_error('lang', get_string('institutionlanginvalid', 'admin'));
    }
    if (!is_plugin_active('framework', 'module') && (!empty($values['allowinstitutionsmartevidence']))) {
        $form->set_error('allowinstitutionsmartevidence', get_string('institutionsmartevidencenotallowed', 'admin'));
    }

    // Validate plugins settings.
    plugin_institution_prefs_validate($form, $values);
}

function institution_submit(Pieform $form, $values) {
    global $SESSION, $institution, $add, $instancearray, $USER, $authinstances, $customthemedefaults;

    db_begin();
    // Update the basic institution record...
    if ($add) {
        $institution = institution_generate_name($values['displayname']);
        $newinstitution = new Institution();
        $newinstitution->initialise($institution, $values['displayname']);
        $institution = $newinstitution->name;
    }
    else {
        $newinstitution = new Institution($institution);
        $newinstitution->displayname = $values['displayname'];
        $oldinstitution = get_record('institution', 'name', $institution);
    }

    $newinstitution->showonlineusers              = !isset($values['showonlineusers']) ? 2 : $values['showonlineusers'];
    if (get_config('usersuniquebyusername')) {
        // Registering absolutely not allowed when this setting is on, it's a
        // security risk. See the documentation for the usersuniquebyusername
        // setting for more information
        $newinstitution->registerallowed = 0;
    }
    else {
        $newinstitution->registerallowed              = ($values['registerallowed']) ? 1 : 0;
        $newinstitution->registerconfirm              = ($values['registerconfirm']) ? 1 : 0;
    }

    if (!empty($values['lang'])) {
        if ($values['lang'] == 'sitedefault') {
            $newinstitution->lang = null;
        }
        else {
            $newinstitution->lang = $values['lang'];
        }
    }

    $newinstitution->theme                 = (empty($values['theme']) || $values['theme'] == 'sitedefault') ? null : $values['theme'];
    $newinstitution->dropdownmenu          = (!empty($values['dropdownmenu'])) ? 1 : 0;
    $newinstitution->skins                 = (!empty($values['skins'])) ? 1 : 0;
    require_once(get_config('docroot') . 'artefact/comment/lib.php');
    $commentoptions = ArtefactTypeComment::get_comment_options();
    $newinstitution->commentsortorder      = (empty($values['commentsortorder'])) ? $commentoptions->sort : $values['commentsortorder'];
    $newinstitution->commentthreaded       = (!empty($values['commentthreaded'])) ? 1 : 0;

    if ($newinstitution->theme == 'custom') {
        // remove flag to add warning for configurable theme update if it exists.
        if (get_config_institution($institution, 'customthemeupdate')) {
            set_config_institution($institution, 'customthemeupdate', false);
        }

        if (!empty($oldinstitution->style)) {
            $styleid = $oldinstitution->style;
            delete_records('style_property', 'style', $styleid);
        }
        else {
            $record = (object) array('title' => get_string('customstylesforinstitution', 'admin', $newinstitution->displayname));
            $styleid = insert_record('style', $record, 'id', true);
        }

        $properties = array();
        $record = (object) array('style' => $styleid);
        foreach ($customthemedefaults as $name => $val) {
            $newvalue = !empty($values['resetcustom']) ? $val['value'] : $values[$name];
            $record->field = $name;
            $record->value = $newvalue;
            insert_record('style_property', $record);
            $properties[$name] = $newvalue;
        }

        // Cache the css
        $smarty = smarty_core();
        $smarty->assign('data', $properties);
        set_field('style', 'css', $smarty->fetch('customcss.tpl'), 'id', $styleid);

        $newinstitution->style = $styleid;
    }
    else {
        $newinstitution->style = null;
    }

    if (get_config('licensemetadata')) {
        $newinstitution->licensemandatory = (!empty($values['licensemandatory'])) ? 1 : 0;
        $newinstitution->licensedefault = (isset($values['licensedefault'])) ? $values['licensedefault'] : '';
    }

    if (!empty($values['resetcustom']) && $newinstitution->theme !== 'custom' && !empty($oldinstitution->style)) {
        $newinstitution->style = null;
    }

    if ($USER->get('admin') || get_config_plugin('artefact', 'file', 'institutionaloverride')) {
        if (!empty($values['updateuserquotas']) && !empty($values['defaultquota'])) {
            execute_sql(
                "UPDATE {usr} SET quota = ? WHERE id IN (SELECT usr FROM {usr_institution} WHERE institution = ?)",
                array($values['defaultquota'], $institution)
            );
            // get all the users from the institution and make sure that they are still below
            // their quota threshold
            if ($users = get_records_sql_array('SELECT * FROM {usr} u INNER JOIN {usr_institution} ui ON u.id = ui.usr AND ui.institution = ?', array($institution))) {
                $quotanotifylimit = get_config_plugin('artefact', 'file', 'quotanotifylimit');
                if ($quotanotifylimit <= 0 || $quotanotifylimit >= 100) {
                    $quotanotifylimit = 100;
                }
                foreach ($users as $user) {
                    // check if the user has gone over the quota notify limit
                    $user->quotausedpercent = $user->quotaused / $user->quota * 100;
                    $overlimit = false;
                    if ($quotanotifylimit <= $user->quotausedpercent) {
                        $overlimit = true;
                    }
                    $notified = get_field('usr_account_preference', 'value', 'field', 'quota_exceeded_notified', 'usr', $user->id);
                    if ($overlimit && '1' !== $notified) {
                        require_once(get_config('docroot') . 'artefact/file/lib.php');
                        ArtefactTypeFile::notify_users_threshold_exceeded(array($user), false);
                        // no need to email admin as we can alert them right now
                        $SESSION->add_error_msg(get_string('useroverquotathreshold', 'artefact.file', display_name($user), ceil((int) $user->quotausedpercent), display_size($user->quota)));
                    }
                    else if ($notified && !$overlimit) {
                        set_account_preference($user->id, 'quota_exceeded_notified', false);
                    }
                }
            }
        }
        $newinstitution->defaultquota = empty($values['defaultquota']) ? get_config_plugin('artefact', 'file', 'defaultquota') : $values['defaultquota'];
    }
    if ($institution != 'mahara') {
        $newinstitution->defaultmembershipperiod  = ($values['defaultmembershipperiod']) ? intval($values['defaultmembershipperiod']) : null;
        if ($USER->get('admin')) {
            $newinstitution->maxuseraccounts      = ($values['maxuseraccounts']) ? intval($values['maxuseraccounts']) : null;
            $newinstitution->expiry               = db_format_timestamp($values['expiry']);
        }
    }

    $newinstitution->allowinstitutionpublicviews  = (isset($values['allowinstitutionpublicviews']) && $values['allowinstitutionpublicviews']) ? 1 : 0;
    $newinstitution->allowinstitutionsmartevidence  = (isset($values['allowinstitutionsmartevidence']) && $values['allowinstitutionsmartevidence']) ? 1 : 0;
    $newinstitution->tags  = (isset($values['allowinstitutiontags']) && $values['allowinstitutiontags']) ? 1 : 0;
    $newinstitution->progresscompletion  = (isset($values['progresscompletion']) && $values['progresscompletion']) ? 1 : 0;

    // do not set 'reviewselfdeletion' if it has never been changed at institution level
    // and the value is the same as site setting 'defaultreviewselfdeletion'
    if (get_config_institution($institution, 'reviewselfdeletion') != null || get_config('defaultreviewselfdeletion') != $values['reviewselfdeletion']) {
        $newinstitution->reviewselfdeletion  = $values['reviewselfdeletion'] ? 1 : 0;
    }

    // TODO: Move handling of authentication instances within the Institution class as well?
    if (!empty($values['authplugin'])) {
        $allinstances = array_merge($values['authplugin']['instancearray'], $values['authplugin']['deletearray']);

        if (array_diff($allinstances, $instancearray)) {
            throw new ConfigException('Attempt to delete or update another institution\'s auth instance');
        }

        if (array_diff($instancearray, $allinstances)) {
            throw new ConfigException('One of your instances is unaccounted for in this transaction');
        }

        foreach($values['authplugin']['instancearray'] as $priority => $instanceid) {
            if (in_array($instanceid, $values['authplugin']['deletearray'])) {
                // Should never happen:
                throw new SystemException('Attempt to update AND delete an auth instance');
            }
            $record = new stdClass();
            $record->priority = $priority;
            $record->id = $instanceid;
            update_record('auth_instance', $record,  array('id' => $instanceid));
        }

        foreach($values['authplugin']['deletearray'] as $instanceid) {
            // If this authinstance is the only xmlrpc authinstance that references a host, delete the host record.
            $hostwwwroot = null;
            foreach ($authinstances as $ai) {
                if ($ai->id == $instanceid && $ai->authname == 'xmlrpc') {
                    $hostwwwroot = get_field_sql("SELECT \"value\" FROM {auth_instance_config} WHERE \"instance\" = ? AND field = 'wwwroot'", array($instanceid));
                    if ($hostwwwroot && count_records_select('auth_instance_config', "field = 'wwwroot' AND \"value\" = ?", array($hostwwwroot)) == 1) {
                        // Unfortunately, it's possible that this host record could belong to a different institution,
                        // so specify the institution here.
                        delete_records('host', 'wwwroot', $hostwwwroot, 'institution', $institution);
                        // We really need to fix this, either by removing the institution from the host table, or refusing to allow the
                        // institution to be changed in the host record when another institution's authinstance is still pointing at it.
                    }
                    break;
                }
            }
            delete_records('auth_remote_user', 'authinstance', $instanceid);
            delete_records('auth_instance_config', 'instance', $instanceid);
            delete_records('auth_instance', 'id', $instanceid);
            // Make it no longer be the parent authority to any auth instances
            delete_records('auth_instance_config', 'field', 'parent', 'value', $instanceid);
        }
    }

    // Store plugin settings.
    plugin_institution_prefs_submit($form, $values, $newinstitution);

    // Save the changes to the DB
    $newinstitution->commit();

    if ($add) {
        // Automatically create an internal authentication authinstance
        $authinstance = (object)array(
            'instancename' => 'internal',
            'priority'     => 0,
            'active'       => 1,
            'institution'  => $newinstitution->name,
            'authname'     => 'internal',
        );
        insert_record('auth_instance', $authinstance);

        // We need to add the default lines to the site_content table for this institution
        // We also need to set the institution to be using default static pages to begin with
        // so that using custom institution pages is an opt-in situation
        $pages = site_content_pages();
        $now = db_format_timestamp(time());
        foreach ($pages as $name) {
            $page = new stdClass();
            $page->name = $name;
            $page->ctime = $now;
            $page->mtime = $now;
            $page->mauthor = $USER->get('id');
            $page->content = get_string($page->name . 'defaultcontent', 'install', get_string('staticpageconfiginstitutions', 'install', get_config('wwwroot') . 'admin/users/institutionpages.php'));
            $page->institution = $newinstitution->name;
            insert_record('site_content', $page);

            $institutionconfig = new stdClass();
            $institutionconfig->institution = $newinstitution->name;
            $institutionconfig->field = 'sitepages_' . $name;
            $institutionconfig->value = 'mahara';
            insert_record('institution_config', $institutionconfig);
        }
    }

    if (is_null($newinstitution->style) && !empty($oldinstitution->style)) {
        delete_records('style_property', 'style', $oldinstitution->style);
        delete_records('style', 'id', $oldinstitution->style);
    }

    // Set the logos after updating the institution, because the institution
    // needs to exist before it can own the logo artefact.
    if (!empty($values['logo'])) {
        safe_require('artefact', 'file');

        // Entry in artefact table
        $data = (object) array(
            'institution' => $institution,
            'title'       => 'logo',
            'description' => 'Institution logo',
            'note'        => $values['logo']['name'],
            'size'        => $values['logo']['size'],
        );

        $imageinfo      = getimagesize($values['logo']['tmp_name']);
        $data->width    = $imageinfo[0];
        $data->height   = $imageinfo[1];
        $data->filetype = $imageinfo['mime'];
        $artefact = new ArtefactTypeProfileIcon(0, $data);
        if (preg_match("/\.([^\.]+)$/", $values['logo']['name'], $saved)) {
            $artefact->set('oldextension', $saved[1]);
        }
        $artefact->commit();

        $id = $artefact->get('id');

        // Move the file into the correct place.
        $directory = get_config('dataroot') . 'artefact/file/profileicons/originals/' . ($id % 256) . '/';
        check_dir_exists($directory);
        move_uploaded_file($values['logo']['tmp_name'], $directory . $id);

        // Delete the old logo
        if (!empty($oldinstitution->logo)) {
            $oldlogo = new ArtefactTypeProfileIcon($oldinstitution->logo);
            $oldlogo->delete();
        }

        set_field('institution', 'logo', $id, 'name', $institution);
    }

    if (!empty($values['deletelogo'])) {
        execute_sql("UPDATE {institution} SET logo = NULL WHERE name = ?", array($institution));
    }

    //small logo
    if (!empty($values['logoxs'])) {
        safe_require('artefact', 'file');

        // Entry in artefact table
        $data = (object) array(
            'institution' => $institution,
            'title'       => 'logoxs',
            'description' => 'Institution small logo',
            'note'        => $values['logoxs']['name'],
            'size'        => $values['logoxs']['size'],
        );

        $imageinfo      = getimagesize($values['logoxs']['tmp_name']);
        $data->width    = $imageinfo[0];
        $data->height   = $imageinfo[1];
        $data->filetype = $imageinfo['mime'];
        $artefact = new ArtefactTypeProfileIcon(0, $data);
        if (preg_match("/\.([^\.]+)$/", $values['logoxs']['name'], $saved)) {
            $artefact->set('oldextension', $saved[1]);
        }
        $artefact->commit();

        $id = $artefact->get('id');

        // Move the file into the correct place.
        $directory = get_config('dataroot') . 'artefact/file/profileicons/originals/' . ($id % 256) . '/';
        check_dir_exists($directory);
        move_uploaded_file($values['logoxs']['tmp_name'], $directory . $id);

        // Delete the old small logo
        if (!empty($oldinstitution->logoxs)) {
            $oldlogo = new ArtefactTypeProfileIcon($oldinstitution->logoxs);
            $oldlogo->delete();
        }

        set_field('institution', 'logoxs', $id, 'name', $institution);
    }

    if (!empty($values['deletelogoxs'])) {
        execute_sql("UPDATE {institution} SET logoxs = NULL WHERE name = ?", array($institution));
    }

    delete_records('institution_locked_profile_field', 'name', $institution);
    foreach (ArtefactTypeProfile::get_all_fields() as $field => $type) {
        if ($values[$field]) {
            $profilefield = new stdClass();
            $profilefield->name         = $institution;
            $profilefield->profilefield = $field;
            insert_record('institution_locked_profile_field', $profilefield);
        }
    }
    db_commit();

    if ($add) {
        if (!$newinstitution->registerallowed) {
            // If registration is not allowed, then an authinstance will not
            // have been created, and thus cause the institution page to add
            // its own error message on the next page load
            $SESSION->add_ok_msg(get_string('institutionaddedsuccessfully2', 'admin'));
        }
        $nexturl = '/admin/users/institutions.php?i='.urlencode($institution);
    }
    else {
        $message = get_string('institutionupdatedsuccessfully', 'admin');
        if (isset($values['theme'])) {
            $changedtheme = $oldinstitution->theme != $values['theme']
                && (!empty($oldinstitution->theme) || $values['theme'] != 'sitedefault');
            if ($changedtheme || $values['theme'] == 'custom') {
                $message .= '  ' . get_string('usersseenewthemeonlogin', 'admin');
            }
            $USER->reset_institutions();
        }
        $SESSION->add_ok_msg($message);
        $nexturl = '/admin/users/institutions.php';
    }
    // Clear out any cached menus for this institution
    clear_menu_cache();
    redirect($nexturl);
}

function institution_cancel_submit() {
    redirect('/admin/users/institutions.php');
}

if ($institution && $institution != 'mahara') {
    $_institution = get_record('institution', 'name', $institution);
    $suspended = $_institution->suspended;
    if ($USER->get('admin')) {
        function institution_suspend_submit(Pieform $form, $values) {
            global $SESSION, $USER;
            if (!$USER->get('admin')) {
                $SESSION->add_error_msg(get_string('errorwhilesuspending', 'admin'));
            }
            else {
                // Need to logout any users that are using this institution's authinstance.
                if ($loggedin = get_records_sql_array("SELECT ui.usr FROM {usr_institution} ui
                    JOIN {usr} u ON u.id = ui.usr
                    JOIN {auth_instance} ai ON ai.id = u.authinstance
                    JOIN {usr_session} us ON us.usr = u.id
                    WHERE ui.institution = ?
                    AND ai.institution = ?", array($values['i'], $values['i']))) {
                    foreach ($loggedin as $user) {
                        $loggedinarray[] = $user->usr;
                    }
                    delete_records_sql("DELETE FROM {usr_session} WHERE usr IN (" . join(',', $loggedinarray) . ")");
                    $SESSION->add_ok_msg(get_string('institutionlogoutusers', 'admin', count($loggedin)));
                }
                set_field('institution', 'suspended', 1, 'name', $values['i']);
                $SESSION->add_ok_msg(get_string('institutionsuspended', 'admin'));
            }
            redirect('/admin/users/institutions.php?i=' . $values['i']);
        }

        function institution_unsuspend_submit(Pieform $form, $values) {
            global $SESSION, $USER;
            if (!$USER->get('admin')) {
                $SESSION->add_error_msg(get_string('errorwhileunsuspending', 'admin'));
            }
            else {
                set_field('institution', 'suspended', 0, 'name', $values['i']);
                $SESSION->add_ok_msg(get_string('institutionunsuspended', 'admin'));
            }
            redirect('/admin/users/institutions.php?i=' . $values['i']);
        }

        // Suspension controls
        if (empty($suspended)) {
            $suspendformdef = array(
                'name'       => 'institution_suspend',
                'plugintype' => 'core',
                'renderer' => 'div',
                'class' => 'form-as-button last',
                'pluginname' => 'admin',
                'elements'   => array(
                    'i' => array(
                        'type'    => 'hidden',
                        'value'   => $institution,
                    ),
                    'submit' => array(
                        'type'        => 'button',
                        'usebuttontag' => true,
                        'class'       => 'btn-secondary',
                        'value'       => '<span class="icon text-danger icon-ban left" role="presentation" aria-hidden="true"></span>' . get_string('suspendinstitution','admin'),
                    ),
                )
            );

            $suspendform  = pieform($suspendformdef);
        }
        else {
            $suspendformdef = array(
                'name'       => 'institution_unsuspend',
                'plugintype' => 'core',
                'renderer' => 'div',
                'pluginname' => 'admin',
                'elements'   => array(
                    'i' => array(
                        'type'    => 'hidden',
                        'value'   => $institution,
                    ),
                    'submit' => array(
                        'type'        => 'button',
                        'usebuttontag' => true,
                        'class'       => 'btn-secondary',
                        'value'       => '<span class="icon text-success icon-check left" role="presentation" aria-hidden="true"></span>' . get_string('unsuspendinstitution','admin'),
                    ),
                )
            );
            $suspendform  = pieform($suspendformdef);
        }
    }
}

function search_submit(Pieform $form, $values) {
    redirect('/admin/users/institutions.php' . ((isset($values['query']) && ($values['query'] != '')) ? '?query=' . urlencode($values['query']) : ''));
}

// Hide/disable options based on theme selected
$themeoptionsjs = '
jQuery(function($) {
    if ($("#institution_theme").val() === "custom") {
        $(".customtheme").removeClass("js-hidden");
    }
    $("#institution_theme").on("change", function() {
        if ($(this).val() === "custom") {
            $(".customtheme").removeClass("js-hidden");
        }
        else {
            $(".customtheme").addClass("js-hidden");
        }
    });
});
';

$smarty = smarty(array('tinymce'));
setpageicon($smarty, 'icon-university');

$smarty->assign('INLINEJAVASCRIPT', $themeoptionsjs);
$smarty->assign('institution_form', $institutionform);
$smarty->assign('instancestring', $instancestring);
$smarty->assign('add', $add);

if (isset($suspended)) {
    if ($suspended) {
        $smarty->assign('suspended', get_string('suspendedinstitutionmessage', 'admin'));
    }
    if (isset($suspendform)) {
        $smarty->assign('suspendform', $suspendform);
    }
}

$smarty->assign('PAGEHEADING', get_string('admininstitutions', 'admin'));
$smarty->display('admin/users/institutions.tpl');

function theme_sort($a, $b) {
    if ($a == 'sitedefault') {
        return -1;
    }
    if ($b == 'sitedefault') {
        return 1;
    }
    return $a > $b;
}
