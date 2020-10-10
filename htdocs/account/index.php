<?php
/**
 *
 * @package    mahara
 * @subpackage core
 * @author     Catalyst IT Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 * @copyright  For copyright information on Mahara, please see the README file distributed with this software.
 *
 */

define('INTERNAL', 1);
define('MENUITEM', 'settings/account');
define('SECTION_PLUGINTYPE', 'core');
define('SECTION_PLUGINNAME', 'account');
define('SECTION_PAGE', 'preferences');

require(dirname(dirname(__FILE__)) . '/init.php');
define('TITLE', get_string('preferences'));

// load up user preferences
$prefs = (object) load_account_preferences($USER->id);

$authobj = AuthFactory::create($USER->authinstance);

// @todo auth preference for a password change screen for all auth methods other than internal
if (method_exists($authobj, 'change_password')) {

    $elements = array(
        'changepassworddesc' => array(
            'value' => '<tr><td colspan="2"><h2>' . get_string('changepassworddesc', 'account') . '</h2></td></tr>'
        ),
        // HACK: A decoy password field to prevent Firefox from trying to autofill the "oldpassword" field.
        // (FF will fill in this one instead, because it comes first. Then we can just ignore it.
        // TODO: move the password reset form to a separate screen
        'password' => array(
            'type' => 'password',
            'title' => '',
            'class' => 'd-none',
            'value' => 'decoypassword',
        )
    );
    if ($USER->get('password')) {
        $elements['oldpassword'] = array(
            'type' => 'password',
            'title' => get_string('oldpassword'),
            'help'  => true,
            'autocomplete' => 'off',
        );
    }
    $elements['password1'] = array(
        'type' => 'password',
        'title' => get_string('newpassword'),
        'description' => get_password_policy_description(),
        'showstrength' => true
    );
    $elements['password2'] = array(
        'type' => 'password',
        'title' => get_string('confirmpassword')
    );
}
else if ($url = get_config_plugin_instance('auth', $USER->authinstance, 'changepasswordurl')) {
    // @todo contextual help
    $elements = array(
        'changepasswordotherinterface' => array(
            'value' => '<tr><td colspan="2"><h2>' . get_string('changepasswordotherinterface', 'account', $url) . '</h2></td></tr>'
        )
    );
}
else {
    $elements = array();
}

if ($authobj->authname == 'internal') {
    $elements['changeusernameheading'] = array(
        'value' => '<tr><td colspan="2"><h2>' . get_string('changeusernameheading', 'account') . '</h2></td></tr>'
    );
    $elements['username'] = array(
        'type' => 'text',
        'defaultvalue' => $USER->get('username'),
        'title' => get_string('changeusername', 'account'),
        'description' => get_string('changeusernamedesc', 'account', hsc(get_config('sitename'))),
    );
    $elements['oldpasswordchangeuser'] = array(
        'type' => 'password',
        'title' => get_string('oldpassword'),
        'help'  => false,
        'autocomplete' => 'off',
    );
}

if (get_config('cleanurls') && get_config('cleanurlusereditable')) {
    $elements['changeprofileurl'] = array(
        'value' => '<tr><td colspan="2"><h2>' . get_string('changeprofileurl', 'account') . '</h2></td></tr>'
    );
    if (get_config('cleanurlusersubdomains')) {
        list($proto, $rest) = explode('://', get_config('wwwroot'));
        $prehtml = $proto . ':// ';
        $posthtml = ' .' . $rest;
    }
    else {
        $prehtml = get_config('wwwroot') . get_config('cleanurluserdefault') . '/ ';
        $posthtml = '';
    }
    $elements['urlid'] = array(
        'type'         => 'text',
        'defaultvalue' => $USER->get('urlid'),
        'title'        => get_string('profileurl', 'account'),
        'prehtml'      => '<span class="description">' . $prehtml . '</span>',
        'posthtml'     => '<span class="description">' . $posthtml . '</span>',
        'description'  => get_string('profileurldescription', 'account') . ' ' . get_string('cleanurlallowedcharacters'),
        'rules'        => array('maxlength' => 30, 'regex' => get_config('cleanurlvalidate')),
    );
}

$elements['accountoptionsdesc'] = array(
    'value' => '<tr><td colspan="2"><h2>' . get_string('accountoptionsdesc', 'account') . '</h2></td></tr>'
);

// Add general account options
$elements = array_merge($elements, general_account_prefs_form_elements($prefs));

// Add plugins account options.
$elements = array_merge($elements, plugin_account_prefs_form_elements($prefs));

$blogcount = count_records('artefact', 'artefacttype', 'blog', 'owner', $USER->get('id')) ;
if ($blogcount != 1 && $prefs->multipleblogs == 1) {
    $elements['multipleblogs']['readonly'] = true;
}


$elements['submit'] = array(
    'type' => 'submit',
    'class' => 'btn-primary',
    'value' => get_string('save')
);

$prefsform = array(
    'name'        => 'accountprefs',
    'renderer'    => 'div',
    'method'      => 'post',
    'jsform'      => true,
    'plugintype'  => 'core',
    'pluginname'  => 'account',
    'jssuccesscallback' => 'clearPasswords',
    'elements'    => $elements
);

function accountprefs_validate(Pieform $form, $values) {
    global $USER;

    $authobj = AuthFactory::create($USER->authinstance);

    if (isset($values['oldpassword'])) {
        if ($values['oldpassword'] !== '') {
            try {
                if (!$authobj->authenticate_user_account($USER, $values['oldpassword'])) {
                    $form->set_error('oldpassword', get_string('oldpasswordincorrect', 'account'));
                    return;
                }
            }
            // propagate error correctly for User validation issues - this should
            // be catching AuthUnknownUserException and AuthInstanceException
            catch  (UserException $e) {
                 $form->set_error('oldpassword', $e->getMessage());
                 return;
            }
            password_validate($form, $values, $USER);
        }
        else if ($values['password1'] !== '' || $values['password2'] !== '') {
            $form->set_error('oldpassword', get_string('mustspecifyoldpassword'));
        }
    }
    else if (empty($USER->get('password')) && !empty($values['password1'])) {
        password_validate($form, $values, $USER);
    }

    if (isset($values['username']) && $values['username'] != $USER->get('username')) {
        if (isset($values['oldpasswordchangeuser'])) {
            if ($values['oldpasswordchangeuser'] !== '') {
                try {
                    if (!$authobj->authenticate_user_account($USER, $values['oldpasswordchangeuser'])) {
                        $form->set_error('oldpasswordchangeuser', get_string('oldpasswordincorrect', 'account'));
                        return;
                    }
                }
                // propagate error correctly for User validation issues - this should
                // be catching AuthUnknownUserException and AuthInstanceException
                catch  (UserException $e) {
                     $form->set_error('oldpasswordchangeuser', $e->getMessage());
                     return;
                }
            }
            else {
                $form->set_error('oldpasswordchangeuser', get_string('mustspecifycurrentpassword'));
            }
        }
        else {
            $form->set_error('oldpasswordchangeuser', get_string('mustspecifycurrentpassword'));
        }
    }

    if ($authobj->authname == 'internal' && $values['username'] != $USER->get('username')) {
        if (!AuthInternal::is_username_valid($values['username'])) {
            $form->set_error('username', get_string('usernameinvalidform', 'auth.internal'));
        }
        if (!$form->get_error('username') && record_exists_select('usr', 'LOWER(username) = ?', array(strtolower($values['username'])))) {
            $form->set_error('username', get_string('usernamealreadytaken1', 'auth.internal'));
        }
    }

    if (isset($values['urlid']) && get_config('cleanurls') && $values['urlid'] != $USER->get('urlid')) {
        if (strlen($values['urlid']) < 3) {
            $form->set_error('urlid', get_string('rule.minlength.minlength', 'pieforms', 3));
        }
        else if (record_exists('usr', 'urlid', $values['urlid'])) {
            $form->set_error('urlid', get_string('urlalreadytaken', 'account'));
        }
    }

    plugin_account_prefs_validate($form, $values);
}

function accountprefs_submit(Pieform $form, $values) {
    global $USER, $SESSION;

    $authobj = AuthFactory::create($USER->authinstance);

    db_begin();
    $ispasswordchanged = false;
    if (isset($values['password1']) && $values['password1'] !== '') {
        $password = $authobj->change_password($USER, $values['password1']);
        $USER->password = $password;
        $USER->passwordchange = 0;
        $USER->commit();
        $ispasswordchanged = true;
    }

    // use this as looping through values is not safe.
    $expectedprefs = expected_account_preferences();
    if ($values['maildisabled'] == 0 && get_account_preference($USER->get('id'), 'maildisabled') == 1) {
        // Reset the sent and bounce counts otherwise mail will be disabled
        // on the next send attempt
        $u = new stdClass();
        $u->email = $USER->get('email');
        $u->id = $USER->get('id');
        update_bounce_count($u,true);
        update_send_count($u,true);
    }

    // Remember the user's language & theme prefs, so we can reload the page if they change them
    $oldlang = $USER->get_account_preference('lang');
    $oldtheme = $USER->get_account_preference('theme');
    $oldgroupsideblockmaxgroups = $USER->get_account_preference('groupsideblockmaxgroups');
    $oldgroupsideblocksortby = $USER->get_account_preference('groupsideblocksortby');
    $oldgroupsideblocklabels = $USER->get_account_preference('groupsideblocklabels');
    if (isset($values['groupsideblocklabels'][0]) && empty($values['groupsideblocklabels'][0])) {
        $values['groupsideblocklabels'] = '';
    }
    else {
        $values['groupsideblocklabels'] = json_encode(array_values($values['groupsideblocklabels']));
    }

    // Set user account preferences
    foreach ($expectedprefs as $eprefkey => $epref) {
        if (isset($values[$eprefkey]) && $values[$eprefkey] !== get_account_preference($USER->get('id'), $eprefkey)) {
            $USER->set_account_preference($eprefkey, $values[$eprefkey]);
        }
    }

    if (isset($values['theme']) && $values['theme'] == 'sitedefault') {
        $USER->set_account_preference('theme', '');
    }

    $returndata = array();
    if (isset($values['username']) && $values['username'] != $USER->get('username')) {
        $USER->username = $values['username'];
        $USER->commit();
        $returndata['username'] = $values['username'];
    }

    $reload = false;
    if (get_config('cleanurls') && isset($values['urlid']) && $values['urlid'] != $USER->get('urlid')) {
        $USER->urlid = $values['urlid'];
        $USER->commit();
        $reload = true;
    }

    if ($ispasswordchanged) {
        // Destroy other sessions of the user
        require_once(get_config('docroot') . 'auth/session.php');
        remove_user_sessions($USER->get('id'));
        $reload = true;
    }

    db_commit();

    $returndata['message'] = get_string('prefssaved', 'account');

    if (isset($values['theme']) && $values['theme'] != $oldtheme) {
        $USER->update_theme();
        $reload = true;
    }

    if (isset($values['lang']) && $values['lang'] != $oldlang) {
        // The session language pref is used when the user has no user pref,
        // and when logged out.
        $SESSION->set('lang', $values['lang']);
        $returndata['message'] = get_string_from_language($values['lang'], 'prefssaved', 'account');
        $reload = true;
    }
    if (isset($values['groupsideblockmaxgroups']) && $values['groupsideblockmaxgroups'] != $oldgroupsideblockmaxgroups) {
        $reload = true;
    }
    if ($values['groupsideblocksortby'] != $oldgroupsideblocksortby) {
        $reload = true;
    }
    if ($values['groupsideblocklabels'] != $oldgroupsideblocklabels) {
        $reload = true;
    }

    $reload = plugin_account_prefs_submit($form, $values) || $reload;

    if (!empty($reload)) {
        // Use PIEFORM_CANCEL here to force a page reload and show the new language.
        $returndata['location'] = get_config('wwwroot') . 'account/index.php';
        $SESSION->add_ok_msg($returndata['message']);
        $form->json_reply(PIEFORM_CANCEL, $returndata);
    }

    $form->json_reply(PIEFORM_OK, $returndata);
}

$prefsform = pieform($prefsform);

$ijs = <<< EOF
var clearPasswords = (function($) {
  return function (form, data) {
      formSuccess(form, data);
      if (jQuery('#accountprefs_oldpassword')) {
          jQuery('#accountprefs_oldpassword').val('');
          jQuery('#accountprefs_password1').val('');
          jQuery('#accountprefs_password2').val('');
      }
  }
}(jQuery))

$(function() {
    $('#accountprefs_oldpasswordchangeuser_container').hide();
    $('#accountprefs_username').on('change', function() {
        $('#accountprefs_oldpasswordchangeuser_container').show();
    });
});
EOF;

$request = get_record('usr_pendingdeletion', 'usr', $USER->id);

$smarty = smarty();
setpageicon($smarty, 'icon-cogs');
$smarty->assign('form', $prefsform);
$smarty->assign('candeleteself', $USER->can_delete_self());
$smarty->assign('deletionsent', !empty($request));
$smarty->assign('requestdate', !empty($request) ? format_date(strtotime($request->ctime)) : '');
$smarty->assign('INLINEJAVASCRIPT', $ijs);
$smarty->display('account/index.tpl');
