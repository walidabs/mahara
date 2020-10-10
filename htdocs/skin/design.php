<?php
/**
 *
 * @package    mahara
 * @subpackage skin
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 * @copyright  For copyright information on Mahara, please see the README file distributed with this software.
 * @copyright  (C) 2010-2013 Gregor Anzelj <gregor.anzelj@gmail.com>
 *
 */

define('INTERNAL', true);
define('SECTION_PLUGINTYPE', 'core');
define('SECTION_PLUGINNAME', 'skin');
define('SECTION_PAGE', 'design');

require_once(dirname(dirname(__FILE__)) . '/init.php');
require_once('skin.php');
safe_require('artefact', 'file');

$fieldset = param_alpha('fs', 'viewskin');
$designsiteskin = param_boolean('site', false);

if (!can_use_skins(null, $designsiteskin)) {
    throw new FeatureNotEnabledException();
}

if ($designsiteskin) {
    define('ADMIN', 1);
    if (!$USER->get('admin')) {
        $SESSION->add_error_msg(get_string('accessforbiddentoadminsection'));
        redirect();
    }
    define('MENUITEM', 'configsite/siteskins');
    $goto = get_config('wwwroot') . 'admin/site/skins.php';
}
else {
    define('MENUITEM', 'create/skins');
    $goto = get_config('wwwroot') . 'skin/index.php';
}
$id = param_integer('id', 0); // id of Skin to be edited...
$skindata = null;
if ($id > 0) {
    $skinobj = new Skin($id);
    if ($skinobj->can_edit()) {
        $viewskin = $skinobj->get('viewskin');
        // check to see if any background images being referenced have not
        // been deleted from site and if they have set the value to false
        if (!empty($viewskin['body_background_image'])) {
            if (!record_exists('artefact', 'id', $viewskin['body_background_image'])) {
                $viewskin['body_background_image'] = false;
            }
        }
        if (!empty($viewskin['header_background_image'])) {
            if (!record_exists('artefact', 'id', $viewskin['header_background_image'])) {
                $viewskin['header_background_image'] = false;
            }
        }
    }
    else {
        throw new AccessDeniedException("You can't access and/or edit Skin with id $id");
    }
    define('TITLE', get_string('editskin', 'skin'));
}
else {
    define('TITLE', get_string('createskin', 'skin'));
    $skinobj = new Skin();
}

// Set the Skin access options (for creating or editing form)...
$designsiteskin = $designsiteskin || (isset($skinobj) && $skinobj->get('type') == 'site');
if ($designsiteskin) {
    $accessoptions = array(
            'site' => get_string('siteskinaccess', 'skin')
    );
}
else {
    $accessoptions = array(
            'private' => get_string('privateskinaccess', 'skin'),
            'public' => get_string('publicskinaccess', 'skin'),
    );
}
$folder = param_integer('folder', 0);
$browse = (int) param_variable('browse', 0);
$highlight = null;
if ($file = param_integer('file', 0)) {
    $highlight = array($file);
}
$skintitle = $skinobj->get('title');
$skindesc = $skinobj->get('description');
$skintype = $skinobj->get('type');

$positions = array(
    1 => get_string('topleft', 'skin'),
    2 => get_string('top', 'skin'),
    3 => get_string('topright', 'skin'),
    4 => get_string('left', 'skin'),
    5 => get_string('centre', 'skin'),
    6 => get_string('right', 'skin'),
    7 => get_string('bottomleft', 'skin'),
    8 => get_string('bottom', 'skin'),
    9 => get_string('bottomright', 'skin'),
);

$elements = array();
$elements['id'] = array(
        'type' => 'hidden',
        'value' => $id,
);
$elements['viewskin'] = array(
        'type'   => 'fieldset',
        'legend' => get_string('skingeneraloptions', 'skin'),
        'class'  => $fieldset != 'viewskin' ? 'collapsed' : '',
        'elements'     => array(
                'viewskin_title' => array(
                        'type' => 'text',
                        'title' => get_string('skintitle', 'skin'),
                        'defaultvalue' => (!empty($skintitle) ? $skintitle : null),
                ),
                'viewskin_description' => array(
                        'type' => 'textarea',
                        'rows' => 3,
                        'cols' => 40,
                        'resizable' => true,
                        'title' => get_string('skindescription', 'skin'),
                        'defaultvalue' => (!empty($skindesc) ? $skindesc : null),
                ),
                'viewskin_access' => array(
                        'type' => 'select',
                        'title' => get_string('skinaccessibility1', 'skin'),
                        'defaultvalue' => (!empty($skintype) ? $skintype : null),
                        'options' => $accessoptions,
                ),
        ),
);

// Use the theme default or default to theme 'Raw'
$themeheaderbgcolor = isset($THEME->themeheaderbgcolor) ? $THEME->themeheaderbgcolor : '#F7F7F7'; // $pageheader-bg

// Page
$elements['skinbg'] = array(
    'type'   => 'fieldset',
    'legend' => get_string('view', 'mahara'),
    'class'  => $fieldset != 'skinbg' ? 'collapsed' : '',
    'elements'     => array(
        'header_background_color' => array(
            'type' => 'color',
            'title' => get_string('headerbackgroundcolor', 'skin'),
            'description' => get_string('headerbackgroundcolordescription', 'skin'),
            'defaultvalue' => (!empty($viewskin['header_background_color']) ? $viewskin['header_background_color'] : $themeheaderbgcolor),
            'size' => 7,
            'options' => array(
                'themedefault' => $themeheaderbgcolor,
            ),
            'class' => 'input-push-right',
        ),
        'header_background_image' => array(
            'type'         => 'filebrowser',
            'title'        => get_string('headerbackgroundimage', 'skin'),
            'folder'       => $folder,
            'highlight'    => $highlight,
            'description'  => get_string('headerbackgroundimagedescription', 'skin'),
            'browse'       => $browse,
            'filters'      => array(
                'artefacttype' => array('image'),
            ),
            'page'         => get_config('wwwroot') . 'skin/design.php?id=' . $id . '&fs=skinbg',
            'config'       => array(
                'upload'          => false,
                'uploadagreement' => get_config_plugin('artefact', 'file', 'uploadagreement'),
                'resizeonuploaduseroption' => get_config_plugin('artefact', 'file', 'resizeonuploaduseroption'),
                'resizeonuploaduserdefault' => $USER->get_account_preference('resizeonuploaduserdefault'),
                'createfolder'    => false,
                'edit'            => false,
                'select'          => true,
                'selectone'       => true,
            ),
            'defaultvalue'       => (!empty($viewskin['header_background_image']) ? array(intval($viewskin['header_background_image'])) : array()),
            'selectlistcallback' => 'artefact_get_records_by_id',
            // TODO: Make this work so skins can include site files
            // 'tabs' => true,
        ),
        'body_background_color' => array(
            'type' => 'color',
            'title' => get_string('bodybackgroundcolour', 'skin'),
            'defaultvalue' => (!empty($viewskin['body_background_color']) ? $viewskin['body_background_color'] : '#FFFFFF'),
            'size' => 7,
            'options' => array(
                'themedefault' => '#FFFFFF',
            ),
            'class' => 'input-push-right',
        )
    )
);

// Currently site files don't work properly with site skins. And since site files are the only files that would make
// sense with site skins, we're going to just hide background images entirely for site skins for the time being.
if (!$designsiteskin) {
    $elements['skinbg']['elements'] = array_merge($elements['skinbg']['elements'], array(
        'body_background_image' => array(
                'type'         => 'filebrowser',
                'title'        => get_string('bodybackgroundimage', 'skin'),
                'folder'       => $folder,
                'highlight'    => $highlight,
                'browse'       => $browse,
                'filters'      => array(
                         'artefacttype' => array('image'),
                ),
                'page'         => get_config('wwwroot') . 'skin/design.php?id=' . $id . '&fs=skinbg',
                'config'       => array(
                        'upload'          => false,
                        'uploadagreement' => get_config_plugin('artefact', 'file', 'uploadagreement'),
                        'resizeonuploaduseroption' => get_config_plugin('artefact', 'file', 'resizeonuploaduseroption'),
                        'resizeonuploaduserdefault' => $USER->get_account_preference('resizeonuploaduserdefault'),
                        'createfolder'    => false,
                        'edit'            => false,
                        'select'          => true,
                        'selectone'       => true,
                ),
                'defaultvalue'       => (!empty($viewskin['body_background_image']) ? array(intval($viewskin['body_background_image'])) : array()),
                'selectlistcallback' => 'artefact_get_records_by_id',
                // TODO: Make this work so skins can include site files
                // 'tabs' => true,
        ),
        'body_background_repeat' => array(
                'type' => 'select',
                'title' => get_string('backgroundrepeat', 'skin'),
                'defaultvalue' => (!empty($viewskin['body_background_repeat']) ? intval($viewskin['body_background_repeat']) : 4),
                'options' => array(
                        Skin::BACKGROUND_REPEAT_NO => get_string('backgroundrepeatno', 'skin'),
                        Skin::BACKGROUND_REPEAT_X => get_string('backgroundrepeatx', 'skin'),
                        Skin::BACKGROUND_REPEAT_Y => get_string('backgroundrepeaty', 'skin'),
                        Skin::BACKGROUND_REPEAT_BOTH => get_string('backgroundrepeatboth', 'skin'),
                ),
        ),
        'body_background_attachment' => array(
                'type' => 'radio',
                'title' => get_string('backgroundattachment', 'skin'),
                'defaultvalue' => (!empty($viewskin['body_background_repeat']) ? $viewskin['body_background_attachment'] : 'scroll'),
                'options' => array(
                        'fixed' => get_string('backgroundfixed', 'skin'),
                        'scroll' => get_string('backgroundscroll', 'skin'),
                ),
        ),
        'body_background_position' => array(
                'type' => 'radio',
                'title' => get_string('backgroundposition', 'skin'),
                'defaultvalue' => (!empty($viewskin['body_background_position']) ? intval($viewskin['body_background_position']) : 1),
                'rowsize' => 3,
                'hiddenlabels' => false,
                'options' => $positions,
        )
    ));
}

// Add option for theme font
$themefontoption = array ('' => get_string('themedefault', 'skin'));
$headerfontoptions = Skin::get_all_font_options();
$fontoptions = Skin::get_textonly_font_options();

// Add theme font element to list of fonts
$headerfontoptions = $themefontoption + $headerfontoptions;
$fontoptions = $themefontoption + $fontoptions;

// Set the 'Custom' theme default variables
if ($THEME->basename == 'custom') {
    $themeheadingcolor = get_custom_theme_field('headings');
    $themelinkcolor = get_custom_theme_field('link');
}
// Set the theme default variables, default to theme 'Raw'
if (!isset($themeheadingcolor)) {
    $themeheadingcolor = isset($THEME->themeheadingcolor) ? $THEME->themeheadingcolor : '#333333'; // $view_text_heading_color
}
if (!isset($themelinkcolor)) {
    $themelinkcolor = isset($THEME->themelinkcolor) ? $THEME->themelinkcolor : '#556d32'; // $view_link_normal_color
}
$themetextcolor = isset($THEME->themetextcolor) ? $THEME->themetextcolor : '#333333'; // $view_text_font_color
$themefocusedlinkcolor = isset($THEME->themefocusedlinkcolor) ? $THEME->themefocusedlinkcolor :  '#475c2a'; // $view_link_hover_color
$themeblockheadingfontcolor = isset($THEME->themeblockheadingfontcolor) ? $THEME->themeblockheadingfontcolor :  '#333333'; // $theme-block-header-color

// Text
$elements['viewcontent'] = array(
        'type'   => 'fieldset',
        'legend' => get_string('sampletext', 'skin'),
        'class'  => $fieldset != 'viewcontent' ? 'collapsed' : '',
        'elements'     => array(
            'header_title' => array(
                'type' => 'html',
                'value' => '<h2>' . get_string('header', 'skin') .'</h2>',
                'class' => 'title',
            ),
            'view_heading_font_family' => array(
                'type' => 'select',
                'title' => get_string('headingfontfamily', 'skin'),
                'defaultvalue' => (!empty($viewskin['view_heading_font_family']) ? $viewskin['view_heading_font_family'] : '' ),
                'width' => 144,
                'options' => $headerfontoptions
            ),
            'view_text_heading_color' => array(
                    'type' => 'color',
                    'title' => get_string('headingcolor1', 'skin'),
                    'description' => get_string('headingcolordescription2', 'skin'),
                    'defaultvalue' => (!empty($viewskin['view_text_heading_color']) ? $viewskin['view_text_heading_color'] :  $themeheadingcolor),
                    'size' => 7,
                    'options' => array(
                        'themedefault' => $themeheadingcolor,
                    ),
                    'class' => 'input-push-right',
            ),
            'block_header_title' => array(
                'type' => 'html',
                'value' => '<h2>' . get_string('blockheading', 'skin') .'</h2>',
                'class' => 'title',
            ),
            'view_block_header_font' => array(
                'type' => 'select',
                'title' => get_string('blockheaderfontfamily', 'skin'),
                'defaultvalue' => (!empty($viewskin['view_block_header_font']) ? $viewskin['view_block_header_font'] : ''),
                'width' => 144,
                'options' => $headerfontoptions
            ),
            'view_block_header_font_color' => array(
                'type' => 'color',
                'title' => get_string('blockheaderfontcolor', 'skin'),
                'defaultvalue' => (!empty($viewskin['view_block_header_font_color']) ? $viewskin['view_block_header_font_color'] : $themeblockheadingfontcolor),
                'size' => 7,
                'options' => array(
                    'themedefault' => $themeblockheadingfontcolor,
                ),
                'class' => 'input-push-right',

            ),
            'content_title' => array(
                'type' => 'html',
                'value' => '<h2>' . get_string('content', 'mahara') .'</h2>',
                'class' => 'title',
            ),
            'view_text_font_family' => array(
                    'type' => 'select',
                    'title' => get_string('textfontfamily', 'skin'),
                    'defaultvalue' => (!empty($viewskin['view_text_font_family']) ? $viewskin['view_text_font_family'] : ''),
                    'width' => 144,
                    'options' => $fontoptions
            ),
            'view_text_font_size' => array(
                    'type' => 'select',
                    'title' => get_string('fontsize', 'skin'),
                    'defaultvalue' => (!empty($viewskin['view_text_font_size']) ? $viewskin['view_text_font_size'] : 'medium'),
                    'width' => 144,
                    'options' => array(
                            'small' => array('value' => get_string('fontsizesmall', 'skin'), 'style' => 'font-size: small;'),
                            'medium' => array('value' => get_string('fontsizemedium', 'skin'), 'style' => 'font-size: medium;'),
                            'large' => array('value' => get_string('fontsizelarge', 'skin'), 'style' => 'font-size: large;'),
                            'x-large' => array('value' => get_string('fontsizelarger', 'skin'), 'style' => 'font-size: x-large;'),
                            'xx-large' => array('value' => get_string('fontsizelargest', 'skin'), 'style' => 'font-size: xx-large;'),
                    ),
            ),
            'view_text_font_color' => array(
                    'type' => 'color',
                    'title' => get_string('textcolor', 'skin'),
                    'description' => get_string('textcolordescription', 'skin'),
                    'defaultvalue' => (!empty($viewskin['view_text_font_color']) ? $viewskin['view_text_font_color'] : $themetextcolor),
                    'size' => 7,
                    'options' => array(
                        'themedefault' => $themetextcolor,
                    ),
                    'class' => 'input-push-right',
            ),
            'view_link_normal_color' => array(
                    'type' => 'color',
                    'title' => get_string('normallinkcolor', 'skin'),
                    'defaultvalue' => (!empty($viewskin['view_link_normal_color']) ? $viewskin['view_link_normal_color'] : $themelinkcolor),
                    'size' => 7,
                    'options' => array(
                        'themedefault' => $themelinkcolor,
                    ),
                    'class' => 'input-push-right',
            ),
            'view_link_normal_underline' => array(
                    'type' => 'switchbox',
                    'title' => get_string('normallinkunderlined', 'skin'),
                    'defaultvalue' => (isset($viewskin['view_link_normal_underline']) and intval($viewskin['view_link_normal_underline']) == 1 ? 'checked' : ''),
            ),
            'view_link_hover_color' => array(
                    'type' => 'color',
                    'title' => get_string('hoverlinkcolor1', 'skin'),
                    'defaultvalue' => (!empty($viewskin['view_link_hover_color']) ? $viewskin['view_link_hover_color'] : $themefocusedlinkcolor),
                    'size' => 7,
                    'options' => array(
                        'themedefault' => $themefocusedlinkcolor,
                    ),
                    'class' => 'input-push-right',
            ),
            'view_link_hover_underline' => array(
                    'type' => 'switchbox',
                    'title' => get_string('hoverlinkunderlined', 'skin'),
                    'defaultvalue' => (isset($viewskin['view_link_hover_underline']) and intval($viewskin['view_link_hover_underline']) == 1 ? 'checked' : ''),
            ),
        ),
);
$elements['viewadvanced'] = array(
        'type'   => 'fieldset',
        'legend' => get_string('viewadvancedoptions', 'skin'),
        'class'  =>  $fieldset != 'viewadvanced' ? 'collapsed' : '',
        'elements'     => array(
                'view_custom_css' => array(
                        'type' => 'textarea',
                        'rows' => 7,
                        'cols' => 50,
                        'style' => 'font-family:monospace',
                        'resizable' => true,
                        'fullwidth' => true,
                        'title' => get_string('skincustomcss', 'skin'),
                        'description' => get_string('skincustomcssdescription', 'skin'),
                        'defaultvalue' => ((!empty($viewskin['view_custom_css'])) ? $viewskin['view_custom_css'] : null),
                ),
        ),
);
$elements['fs'] = array(
        'type' => 'hidden',
        'value' => $fieldset,
);
$elements['submitform'] = array(
        'type' => 'submitcancel',
        'subclass' => array('btn-primary'),
        'value' => array(get_string('save', 'mahara'), get_string('cancel', 'mahara')),
        'goto' => $goto,
);

$designskinelements = array(
        'name'       => 'designskinform',
        'class'      => 'jstabs form-group-nested',
        'method'     => 'post',
        'plugintype' => 'core',
        'pluginname' => 'skin',
        'renderer'   => 'div',  // don't change unless you also modify design.js to not require tables.
        'autofocus'  => false,
        'configdirs' => array(get_config('libroot') . 'form/', get_config('docroot') . 'artefact/file/form/'),
        'elements' => $elements
);

if (!$designsiteskin) {
    $designskinelements['jssuccesscallback'] = 'designskinform_callback';
    $designskinelements['jserrorcallback'] = 'designskinform_callback';
    $designskinelements['newiframeonsubmit'] = true;
    $designskinelements['jsform'] = true;
}

$designskinform = pieform($designskinelements);

$javascript = <<<EOF
  function designskinform_callback(form, data) {
      // with multiple filebrowsers on form we need to update the right one
      // when changing to subdirectories
      if (data.formelement && typeof window[data.formelement] != "undefined") {
          window[data.formelement].callback(form, data);
      }
      else {
          // We are submitting the form so need to allow one of the
          // filebrowsers to callback to complete the save
          if (typeof designskinform_body_background_image != "undefined") {
              designskinform_body_background_image.callback(form, data);
          }
      }
  };
EOF;

$smarty = smarty(array(), array(), array(
    'mahara' => array(
        'tab',
        'selected',
    ),
));
setpageicon($smarty, 'icon-paint-brush');
$smarty->assign('LANG', substr($CFG->lang, 0, 2));
$smarty->assign('USER', $USER);
$smarty->assign('INLINEJAVASCRIPT', $javascript);
$smarty->assign('designskinform', $designskinform);
$smarty->assign('PAGEHEADING', hsc(TITLE));
$smarty->display('skin/design.tpl');

function get_custom_theme_field($field) {
    global $USER;
    $institutions = $USER->get('institutions');
    $theme_var = get_field_sql(
        "SELECT sp.value FROM {style_property} sp
         JOIN {institution} i ON i.style = sp.style AND sp.field = ?
         WHERE i.name IN ('" . join("','", array_keys($institutions)) . "')
         ORDER BY i.name
         LIMIT 1", array($field)
    );
    return $theme_var;
}

function designskinform_validate(Pieform $form, $values) {
    global $USER;

    if (isset($values['viewskin_access']) && !($values['viewskin_access'] == 'site')) {
        $artefactfields = array(
            'body_background_image'
        );
        foreach ($artefactfields as $field) {
            if (empty($values[$field])) {
                continue;
            }
            $obj = new ArtefactTypeImage($values[$field]);
            // Make sure the user has access to each of the image artefacts they're trying to
            // embed. This will indicate that they've hacked the HTTP request, so we don't
            // need to bother with a clean response.
            if (!$USER->can_publish_artefact($obj)) {
                throw new AccessDeniedException();
            }
        }
    }
}

function designskinform_submit(Pieform $form, $values) {
    global $USER, $SESSION, $goto;

    $siteskin = (isset($values['viewskin_access']) && ($values['viewskin_access'] == 'site'));
    // Only an admin can create a site skin
    if ($siteskin && !$USER->get('admin')) {
        $values['viewskin_access'] = 'private';
    }
    $skin = Skin::$defaultviewskin;
    foreach ($skin as $k => $v) {
        if (array_key_exists($k, $values)) {
            $skin[$k] = $values[$k];
        }
    }
    if ($siteskin) {
        // Site skin can't have background images
        unset($skin['body_background_image']);
        unset($skin['body_background_repeat']);
        unset($skin['body_background_attachment']);
        unset($skin['body_background_position']);
    }
    // clean the css
    $skin['view_custom_css'] = clean_css($skin['view_custom_css'], $preserve_css=true);

    $viewskin = array();
    $viewskin['id'] = $values['id'];
    if ($values['viewskin_title'] <> '') {
        $viewskin['title'] = $values['viewskin_title'];
    }
    $viewskin['description'] = $values['viewskin_description'];
    $viewskin['owner'] = $USER->get('id');
    $viewskin['type'] = $values['viewskin_access'];
    $viewskin['viewskin'] = $skin;

    $subgoto = $form->get_element_option('submitform', 'goto');
    Skin::create($viewskin);
    if ($form->submitted_by_js()) {
        $result = array(
            'error'   => false,
            'message' => get_string('skinsaved', 'skin'),
            'goto'    => $subgoto,
        );
        // Redirect back to the page from within the iframe
        $SESSION->add_ok_msg($result['message']);
        $form->json_reply(PIEFORM_OK, $result, false);
    }

    $SESSION->add_ok_msg(get_string('skinsaved', 'skin'));
    redirect($goto);
}
