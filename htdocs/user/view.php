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
define('PUBLIC', 1);
define('MENUITEM', 'userdashboard');
// Technically these are lies, but we set them like this to hook in the right
// plugin stylesheet. This file should be provided by artefact/internal anyway.
define('SECTION_PLUGINTYPE', 'artefact');
define('SECTION_PLUGINNAME', 'internal');
define('SECTION_PAGE', 'view');
define('VIEW_TYPE', 'profile');

require(dirname(dirname(__FILE__)).'/init.php');
require_once('group.php');
require_once(get_config('libroot') . 'view.php');

if (param_variable('acceptfriend_submit', null)) {
    acceptfriend_form(param_integer('id'), 'modal');
}
else if (param_variable('addfriend_submit', null)) {
    addfriend_form(param_integer('id'));
}

$loggedinid = $USER->get('id');

if ($profileurlid = param_alphanumext('profile', null)) {
    if (!$user = get_record('usr', 'urlid', $profileurlid, 'deleted', 0)) {
        if ($USER->is_logged_in()) {
            throw new UserNotFoundException("User $profileurlid not found");
        }
        else {
            // For logged-out users we show "access denied" in order to prevent an enumeration attack
            throw new AccessDeniedException(get_string('youcannotviewthisusersprofile', 'error'));
        }
    }
    $userid = $user->id;
}
else if (!empty($loggedinid)) {
    $userid = param_integer('id', $loggedinid);
}
else {
    $userid = param_integer('id');
}
if ($userid == 0) {
    redirect();
}

isolatedinstitution_access($userid);

// Get the user's details
if (!isset($user)) {
    if (!$user = get_record('usr', 'id', $userid, 'deleted', 0)) {
        if ($USER->is_logged_in()) {
            throw new UserNotFoundException("User with id $userid not found");
        }
        else {
            // For logged-out users we show "access denied" in order to prevent an enumeration attack
            throw new AccessDeniedException(get_string('youcannotviewthisusersprofile', 'error'));
        }
    }
}
$is_friend = is_friend($userid, $loggedinid);

if ($loggedinid == $userid) {
    $userobj = clone $USER;
    $view = $userobj->get_profile_view();
}
else {
    $userobj = new User();
    $userobj->find_by_id($userid);
    $view = $userobj->get_profile_view();
}

# access will either be logged in (always) or public as well
if (!$view) {
    // No access, so restrict profile view
    throw new AccessDeniedException(get_string('youcannotviewthisusersprofile', 'error'));
}

$viewid = $view->get('id');
// Special behaviour: Logged in users who the page hasn't been shared with, see a special page
// with the user's name, icon, and little else.
$restrictedview = !can_view_view($viewid);
// Logged-out users can't see any details, though
if ($restrictedview && !$USER->is_logged_in()) {
    throw new AccessDeniedException();
}
else if ($restrictedview && is_isolated()) {
    // Check if isolated institutions are on and user being viewed is a site admin
    // or both users are in no institution
    $userinsts = $userobj->get('institutions');
    $loggedininsts = $USER->get('institutions');
    if ($userobj->get('admin') ||
        (empty($userinsts) && empty($loggedininsts))) {
        $restrictedview = false;
    }
}

$blocksjs = '';
$layoutjs = array();
if (!$restrictedview) {
    if ($newlayout = $view->uses_new_layout()) {
        $layoutjs = array('js/lodash/lodash.js', 'js/gridstack/gridstack.js', 'js/gridlayout.js');
        $blocks = $view->get_blocks();
        $blocks = json_encode($blocks);
        $blocksjs = <<<EOF
            $(function () {
                var options = {
                    verticalMargin: 5,
                    cellHeight: 10,
                    disableDrag : true,
                    disableResize: true,
                };
                var grid = $('.grid-stack');
                grid.gridstack(options);
                grid = $('.grid-stack').data('gridstack');

                if (grid) {
                    // should add the blocks one by one
                    var blocks = {$blocks};
                    loadGrid(grid, blocks);
                }
            });
EOF;
    }
    else {
        $layoutjs= array();
        $viewcontent = $view->build_rows(); // Build content before initialising smarty in case pieform elements define headers.
        $blocksjs = "$(function () {jQuery(document).trigger('blocksloaded');});";
    }
}

$javascript = array('paginator',
                    'lib/pieforms/static/core/pieforms.js',
                  );
$javascript = array_merge($javascript, $layoutjs);
$blocktype_js = $view->get_all_blocktype_javascript();
$javascript = array_merge($javascript, $blocktype_js['jsfiles']);
$inlinejs = <<<JS
jQuery(function($) {
JS;
$inlinejs .= join("\n", $blocktype_js['initjs']) . "\n";
$inlinejs .= <<<JS
    // Disable the modal_links for images etc... when page loads
    $('a[class*=modal_link], a[class*=inner-link]').addClass('no-modal');
    $('a[class*=modal_link], a[class*=inner-link]').css('cursor', 'default');
});
JS;

// Set up theme
$viewtheme = $view->get('theme');
if ($viewtheme && $THEME->basename != $viewtheme) {
    $THEME = new Theme($view);
}
$stylesheets = array();
$stylesheets = array_merge($stylesheets, $view->get_all_blocktype_css());

$name = display_name($user);
define('TITLE', $name);

$sql = "SELECT g.*, a.type FROM {group} g JOIN (
SELECT gm.group, 'invite' AS type
    FROM {group_member_invite} gm WHERE gm.member = ?
UNION
SELECT gm.group, 'request' AS type
    FROM {group_member_request} gm WHERE gm.member = ?
UNION
SELECT gm.group, gm.role AS type
    FROM {group_member} gm
    WHERE gm.member = ?
) AS a ON a.group = g.id
WHERE g.deleted = 0
ORDER BY g.name";
if (!$allusergroups = get_records_sql_assoc($sql, array($userid, $userid, $userid))) {
    $allusergroups = array();
}
$groupinvitedlist = false;
$groupinvitedlistform = false;
$grouprequestedlist = false;
$grouprequestedlistform = false;
$remoteusermessage = false;
$remoteuseracceptform = false;
$remoteusernewfriendform = false;
$remoteuserfriendscontrol = false;
$remoteuserrelationship = false;
if (!empty($loggedinid) && $loggedinid != $userid) {

    $invitedlist = array();   // Groups admin'ed by the logged in user that the displayed user has been invited to
    $requestedlist = array(); // Groups admin'ed by the logged in user that the displayed user has requested membership of

    // Get all groups where either:
    // - the logged in user is an admin, or
    // - the logged in user has a role which is allowed to assess submitted views, or
    // - the logged in user is a member & is allowed to invite friends (when the displayed user is a friend)
    $groups = array();
    foreach (group_get_user_groups() as $g) {
        if ($g->role == 'admin' || $g->see_submitted_views || ($is_friend && $g->invitefriends)) {
            $groups[] = $g;
        }
    }
    if ($groups) {
        $invitelist     = array(); // List of groups the displayed user can be invited to join
        $controlledlist = array(); // List of groups the displayed user can be directly added to

        foreach ($groups as $group) {
            if (array_key_exists($group->id, $allusergroups)) {
                if ($allusergroups[$group->id]->type == 'invite') {
                    $invitedlist[$group->id] = $group->name;
                }
                else if ($allusergroups[$group->id]->type == 'request') {
                    $requestedlist[$group->id] = $group->name;
                    $controlledlist[$group->id] = $group->name;
                    continue;
                }
                else {
                    continue; // Already a member
                }
            }
            $canadd = $group->role == 'admin' || $group->see_submitted_views;
            if ($canadd && $group->jointype == 'controlled') {
                $controlledlist[$group->id] = $group->name;
            }
            if (!isset($invitedlist[$group->id])) {
                $invitelist[$group->id] = $group->name;
            }
        }
        $groupinvitedlist = join(', ', $invitedlist);
        if (count($invitelist) > 0) {
            $default = array_keys($invitelist);
            $default = $default[0];
            $inviteform = pieform(array(
                'name'              => 'invite',
                'successcallback'   => 'invite_submit',
                'renderer'          => 'div',
                'class'             => 'form-inline with-heading invite-friend',
                'elements'          => array(
                    'id' => array(
                        'type'  => 'hidden',
                        'value' => $userid,
                    ),
                    'invitegroup' => array (
                        'type' => 'fieldset',
                        'class' => 'input-group',
                        'elements'          => array(
                            'group' => array(
                                'class'               => 'last hide-label',
                                'type'                => 'select',
                                'title'               => get_string('inviteusertojoingroup', 'group'),
                                'collapseifoneoption' => false,
                                'options'             => $invitelist,
                                'defaultvalue'        => $default,
                            ),

                            'submit' => array(
                                'type'  => 'button',
                                'usebuttontag' => true,
                                'class' => 'btn-secondary input-group-append',
                                'value' => '<span class="icon icon-paper-plane left" role="presentation" aria-hidden="true"></span>' . get_string('sendinvitation', 'group'),
                            )
                        )
                    )
                ),
            ));
            $groupinvitedlistform = $inviteform;
        }

        $grouprequestedlist = join(', ', $requestedlist);
        if (count($controlledlist) > 0) {
            $default = array_keys($controlledlist);
            $default = $default[0];
            $addform = pieform(array(
                'name'                => 'addmember',
                'successcallback'     => 'addmember_submit',
                'renderer'            => 'div',
                'class'             => 'form-inline with-heading with-user-icon',
                'autofocus'           => false,
                'elements'            => array(
                    'member' => array(
                        'type'  => 'hidden',
                        'value' => $userid,
                    ),
                    'addgroup' => array (
                        'type' => 'fieldset',
                        'class' => 'input-group',
                        'elements'  => array(
                            'group' => array(
                                'class'   => 'last hide-label',
                                'type'    => 'select',
                                'title'   => get_string('addusertogroup', 'group'),
                                'collapseifoneoption' => false,
                                'options' => $controlledlist,
                                'defaultvalue' => $default,
                            ),

                            'submit' => array(
                                'type'  => 'button',
                                'usebuttontag' => true,
                                'class' => 'btn-secondary input-group-append',
                                'value' => '<span class="icon icon-plus left" role="presentation" aria-hidden="true"></span>' . get_string('add'),
                            )
                        )
                    )
                ),
            ));
            $grouprequestedlistform = $addform;
        }
    }

    if ($is_friend) {
        $relationship = 'existingfriend';
    }
    else if (record_exists('usr_friend_request', 'requester', $loggedinid, 'owner', $userid)) {
        $relationship = 'requestedfriendship';
    }
    else if ($record = get_record('usr_friend_request', 'requester', $userid, 'owner', $loggedinid)) {
        $relationship = 'pending';
        $remoteusermessage = $record->message;
        $remoteuseracceptform = acceptfriend_form($userid);
    }
    else {
        $relationship = 'none';
        $friendscontrol = get_account_preference($userid, 'friendscontrol');
        if ($friendscontrol == 'auto') {
            $remoteusernewfriendform = true;
        }
        $remoteuserfriendscontrol = $friendscontrol;
    }
    $remoteuserrelationship = $relationship;
}

if ($userid != $USER->get('id') && $USER->is_admin_for_user($user) && is_null($USER->get('parentuser'))) {
    $loginas = get_string('loginasuser', 'admin', display_username($user));
} else {
    $loginas = null;
}
// Set up skin, if the page has one
$viewskin = $view->get('skin');
$owner    = $view->get('owner');
$issiteview = $view->get('institution') == 'mahara';
if ($viewskin && get_config('skins') && can_use_skins($owner, false, $issiteview) && (!isset($THEME->skins) || $THEME->skins !== false)) {
    $skin = array('skinid' => $viewskin, 'viewid' => $view->get('id'));
}
else {
    $skin = false;
}

$smarty = smarty(
    $javascript,
    $stylesheets,
    array(),
    array(
        'sidebars'    => false,
        'skin' => $skin
    )
);
$smarty->assign('restrictedview', $restrictedview);
if ($groupinvitedlist) {
    $smarty->assign('invitedlist', $groupinvitedlist);
}
if ($groupinvitedlistform) {
    $smarty->assign('inviteform',$groupinvitedlistform);
}
if ($grouprequestedlist) {
    $smarty->assign('requestedlist', $grouprequestedlist);
}
if ($grouprequestedlistform) {
    $smarty->assign('addform',$grouprequestedlistform);
}
if ($remoteusermessage) {
    $smarty->assign('message', $record->message);
}
if ($remoteuseracceptform) {
    $smarty->assign('acceptform', acceptfriend_form($userid, 'modal'));
}
if ($remoteusernewfriendform) {
    $smarty->assign('newfriendform', addfriend_form($userid, 'pageactions'));
}
if ($remoteuserfriendscontrol) {
    $smarty->assign('friendscontrol', $friendscontrol);
}
if ($remoteuserrelationship) {
    $smarty->assign('relationship', $relationship);
}

$smarty->assign('loginas', $loginas);

$smarty->assign('INLINEJAVASCRIPT', $blocksjs . $inlinejs);
if ($userobj->get('admin') || $userobj->get('staff')) {
    $url = get_config('wwwroot') . 'institution/index.php?institution=mahara';
    $link = get_string('institutionlink', 'mahara', $url, 'mahara');
    // If user is both Admin and Staff, only say Site administrator and not both
    $role = $userobj->get('admin') ? get_string('siteadmin', 'admin') : get_string('sitestaff', 'admin');
    $smarty->assign('siterole', $role . ' ' . $link);
}
$smarty->assign('institutions', get_institution_string_for_user($userid));
$smarty->assign('canmessage', $loggedinid != $userid && can_send_message($loggedinid, $userid));
$smarty->assign('USERID', $userid);
$smarty->assign('viewtitle', get_string('usersprofile', 'mahara', display_name($user, null, true)));
$smarty->assign('viewtype', 'profile');
$smarty->assign('PAGEHEADING', null);
$smarty->assign('user', $user);
$smarty->assign('lastupdatedstr', $view->lastchanged_message());
$smarty->assign('visitstring', $view->visit_message());
if ($loggedinid && $loggedinid == $userid) {
    $smarty->assign('ownprofile', true);
}
$smarty->assign('pageheadinghtml', $view->display_title(false));
if (!$restrictedview) {
    $smarty->assign('newlayout', $newlayout);
    if (!$newlayout) {
        $smarty->assign('viewcontent', $viewcontent);
    }
}
safe_require('module', 'multirecipientnotification');
$smarty->assign('mrmoduleactive', PluginModuleMultirecipientnotification::is_active());

$smarty->display('user/view.tpl');

mahara_touch_record('view', $viewid); // Update record 'atime'
mahara_log('views', "$viewid"); // Log view visits

// Send an invitation to the user to join a group
function invite_submit(Pieform $form, $values) {
    global $userid;
    redirect('/group/invite.php?id=' . $values['group'] . '&user=' . $userid);
}

// Add the user as a member of a group
function addmember_submit(Pieform $form, $values) {
    global $USER, $SESSION, $userid;

    $group = get_group_by_id($values['group'], true);
    $ctitle = $group->name;
    $adduser = get_record('usr', 'id', $userid);

    try {
        group_add_user($values['group'], $userid, 'member');
        $lang = get_user_language($userid);
        require_once(get_config('libroot') . 'activity.php');
        activity_occurred('maharamessage', array(
            'users'   => array($userid),
            'subject' => get_string_from_language($lang, 'addedtogroupsubject', 'group'),
            'message' => get_string_from_language($lang, 'addedtogroupmessage', 'group', display_name($USER, $adduser), $ctitle),
            'url'     => group_homepage_url($group, false),
            'urltext' => $ctitle,
        ));
        $SESSION->add_ok_msg(get_string('useradded', 'group'));
    }
    catch (SQLException $e) {
        $SESSION->add_error_msg(get_string('adduserfailed', 'group'));
    }
    redirect(profile_url($adduser));
}
