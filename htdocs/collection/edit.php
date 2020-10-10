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

define('SECTION_PLUGINTYPE', 'core');
define('SECTION_PLUGINNAME', 'collection');
define('SECTION_PAGE', 'edit');

require(dirname(dirname(__FILE__)) . '/init.php');
require_once('collection.php');

$new = param_boolean('new', 0);
$copy = param_boolean('copy', 0);

$subtitle = false;
if ($new) {    // if creating a new collection
    $owner = null;
    $groupid = param_integer('group', 0);
    $institutionname = param_alphanum('institution', false);
    if (empty($groupid) && empty($institutionname)) {
        $owner = $USER->get('id');
    }
    $collection = new Collection(null, array('owner' => $owner, 'group' => $groupid, 'institution' => $institutionname));
    define('SUBSECTIONHEADING', get_string('edittitleanddesc', 'collection'));
}
else {    // if editing an existing or copied collection
    $id = param_integer('id');
    $collection = new Collection($id);
    $owner = $collection->get('owner');
    $groupid = $collection->get('group');
    $institutionname = $collection->get('institution');
    define('SUBSECTIONHEADING', $collection->get('name'));
}

if ($collection->is_submitted()) {
    $submitinfo = $collection->submitted_to();
    throw new AccessDeniedException(get_string('canteditsubmitted', 'collection', $submitinfo->name));
}

$urlparams = array();
if (!empty($groupid)) {
    require_once('group.php');
    define('MENUITEM', 'engage/index');
    define('MENUITEM_SUBPAGE', 'views');
    define('GROUP', $groupid);
    $group = group_current_group();
    define('TITLE', $group->name . ' - ' . get_string('editcollection', 'collection'));
    $baseurl = get_config('wwwroot') . 'view/groupviews.php';
    $urlparams['group'] = $groupid;
}
else if (!empty($institutionname)) {
    if ($institutionname == 'mahara') {
        define('ADMIN', 1);
        define('MENUITEM', 'configsite/views');
        $baseurl = get_config('wwwroot') . 'admin/site/views.php';
    }
    else {
        define('INSTITUTIONALADMIN', 1);
        define('MENUITEM', 'manageinstitutions/institutionviews');
        $baseurl = get_config('wwwroot') . 'view/institutionviews.php';
    }
    define('TITLE', get_string('editcollection', 'collection'));
    $urlparams['institution'] = $institutionname;
}
else {
    define('MENUITEM', 'create/views');
    define('TITLE', get_string('editcollection', 'collection'));
    $baseurl = get_config('wwwroot') . 'view/index.php';
}

if (!$USER->can_edit_collection($collection)) {
    throw new AccessDeniedException(get_string('canteditcollection', 'collection'));
}

if ($urlparams) {
    $baseurl .= '?' . http_build_query($urlparams);
}

$elements = $collection->get_collectionform_elements();

if ($copy) {
    $type = 'submit';
    $submitstr = get_string('next') . ': ' . get_string('editviews', 'collection');
    $confirm = null;
    $class = 'btn-primary';
    $subclass = null;
}
else {
    $type = 'submitcancel';
    if ($new) {
        $submitstr = array('button' => get_string('next') . ': ' . get_string('editviews', 'collection'), 'cancel' => get_string('cancel'));
        $confirm = array('cancel' => get_string('confirmcancelcreatingcollection','collection'));
    }
    else {
        $submitstr = array(get_string('save'), get_string('cancel'));
        $confirm = null;
    }
    $class = 'btn-primary';
    $subclass = array('btn-primary');
}
$elements['submitform'] = array(
    'type'      => $type,
    'class'     => $class,
    'subclass'  => $subclass,
    'value'     => $submitstr,
    'confirm'   => $confirm,
    'goto'      => $baseurl,
);
$form = pieform(array(
    'name' => 'edit',
    'method'     => 'post',
    'jsform'     => true,
    'jssuccesscallback' => 'edit_callback',
    'jserrorcallback'   => 'edit_callback',
    'plugintype' => 'core',
    'pluginname' => 'collection',
    'validatecallback' => 'collectionedit_validate',
    'successcallback' => 'collectionedit_submit',
    'elements' => $elements,
));

$inlinejs = <<<EOF
function edit_callback(form, data) {
    edit_coverimage.callback(form, data);
};
EOF;

$smarty = smarty();
setpageicon($smarty, 'icon-folder-open');

$smarty->assign('headingclass', 'page-header');
$smarty->assign('INLINEJAVASCRIPT', $inlinejs);
$smarty->assign('form', $form);
$smarty->display('collection/edit.tpl');

function collectionedit_validate(Pieform $form, $values) {
    if (!empty($values['id'])) {
        $collection = new Collection($values['id']);
        if ($collection->has_framework() && $collection->get('framework') != $values['framework']) {
            // Make sure that if the user is changing the framework that there isn't annotations paired to the old framework
            $views = get_records_sql_array("SELECT v.id, v.title FROM {view} v
                                            JOIN {collection_view} cv ON cv.view = v.id
                                            JOIN {framework_evidence} fe ON fe.view = cv.view
                                            WHERE cv.collection = ?
                                            GROUP BY v.id, v.title", array($values['id']));
            if (!empty($views)) {
                $errorstr = get_string('changeframeworkproblems', 'module.framework');
                foreach ($views as $view) {
                    $errorstr .= " '" . $view->title . "'";
                }
                $form->set_error('framework', $errorstr);
            }
        }
    }
}

function collectionedit_submit(Pieform $form, $values) {
    global $SESSION, $new, $copy, $urlparams;
    $values['navigation'] = (int) $values['navigation'];
    if (isset($values['progresscompletion'])) {
        $values['progresscompletion'] = (int) $values['progresscompletion'];
    }
    if (empty($values['framework'])) {
        $values['framework'] = null;
    }
    $values['coverimage'] = (isset($values['coverimage']) ? $values['coverimage'] : null);
    $collection = Collection::save($values);

    $result = array(
        'error'   => false,
        'message' => get_string('collectionsaved', 'collection'),
        'goto'    => $collection->post_edit_redirect_url($new, $copy, $urlparams),
    );

    if ($form->submitted_by_js()) {
        // Redirect back to the note page from within the iframe
        $SESSION->add_ok_msg($result['message']);
        $form->json_reply(PIEFORM_OK, $result, false);
    }
    $form->reply(PIEFORM_OK, $result);
}

function edit_cancel_submit() {
    global $baseurl;
    redirect($baseurl);
}
