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

define('INTERNAL', 1);
define('ADMIN', 1);
define('MENUITEM', 'configsite/sitefonts');
define('SECTION_PLUGINTYPE', 'core');
define('SECTION_PLUGINNAME', 'admin');
define('SECTION_PAGE', 'sitefonts');

require(dirname(dirname(dirname(__FILE__))) . '/init.php');
require_once(get_config('libroot') . 'skin.php');
define('TITLE', get_string('sitefonts', 'skin'));

if (!get_config('skins')) {
    throw new AccessDeniedException(get_string('pluginnotenabled', 'mahara', 'skins'));
}

$fontpreview  = !is_null($SESSION->get('fontpreview')) ? $SESSION->get('fontpreview') : 21;
$fontsize     = !is_null($SESSION->get('fontsize')) ? $SESSION->get('fontsize') : 28;
$fonttype     = !is_null($SESSION->get('fonttype')) ? $SESSION->get('fonttype') : 'all'; // possible values: all, site, google

$limit   = param_integer('limit', 10);
$offset  = param_integer('offset', 0);
$query   = param_integer('query', null);

$previewform = pieform(array(
    'name' => 'preview',
    'renderer' => 'div',
    'class' => 'pieform with-heading form-inline',
    'elements' => array(
        'fontpreview' => array(
            'type' => 'select',
            'title' => get_string('sampletext', 'skin') . ': ',
            'options' => array(
                10 => get_string('samplefonttitle', 'skin'),
                11 => get_string('sampletitle11', 'skin'),
                12 => get_string('sampletitle12', 'skin'),
                13 => get_string('sampletitle13', 'skin'),
                14 => get_string('sampletitle14', 'skin'),
                15 => get_string('sampletitle15', 'skin'),
                18 => get_string('sampletitle18', 'skin'),
                19 => get_string('sampletitle19', 'skin'),
                20 => get_string('sampletitle20', 'skin'),
                21 => get_string('sampletitle21', 'skin'),
                22 => get_string('sampletitle22', 'skin'),
            ),
            'defaultvalue' => $fontpreview,
        ),
        'fontsize' => array(
            'type' => 'select',
            'title' => get_string('samplesize', 'skin') . ': ',
            'options' => array(
                9 => '9',
                10 => '10',
                12 => '12',
                13 => '13',
                14 => '14',
                16 => '16',
                18 => '18',
                24 => '24',
                28 => '28',
                36 => '36',
                48 => '48',
                64 => '64',
                72 => '72',
            ),
            'defaultvalue' => $fontsize,
        ),
        'fonttype' => array(
            'type' => 'select',
            'title' => get_string('showfonts', 'skin') . ': ',
            'options' => array(
                'all'    => get_string('fonttypes.all', 'skin'),
                'site'   => get_string('fonttypes.site', 'skin'),
                'google' => get_string('fonttypes.google', 'skin'),
                'theme'   => get_string('fonttypes.theme', 'skin'),
            ),
            'defaultvalue' => $fonttype,
        ),
        'limit' => array(
            'type'  => 'hidden',
            'value' => $limit,
        ),
        'offset' => array(
            'type'  => 'hidden',
            'value' => $offset,
        ),
        'submit' => array(
            'type' => 'submit',
            'class' => 'btn-secondary',
            'value' => get_string('preview', 'skin')
        )
    )
));
$data = Skin::get_sitefonts_data($limit, $offset, $fonttype);
$sitefonts = '';
$googlefonts = '';
foreach ($data->data as $font) {
    if ($font['fonttype'] == 'site' || preg_match('/^t_/', $font['fonttype'])) {
        $sitefonts .= $font['title'] . '|';
    }
    if ($font['fonttype'] == 'google') {
        $googlefonts .= urlencode($font['title']) . '|';
    }
}
$sitefonts = rtrim($sitefonts, '|');
$googlefonts = rtrim($googlefonts, '|');

$css = array('<link rel="stylesheet" type="text/css" href="' . get_config('wwwroot') . 'admin/site/font/css.php">');
if (!empty($sitefonts)) {
    $css[] = '<link rel="stylesheet" type="text/css" href="' . get_config('wwwroot') . 'admin/site/font/css.php?family=' . $sitefonts . '">';
}
if (!empty($googlefonts)) {
    $protocol = (is_https()) ? 'https://' : 'http://';
    $css[] = '<link rel="stylesheet" type="text/css" href="' . $protocol . 'fonts.googleapis.com/css?family=' . $googlefonts . '">';
}


$pagination = build_pagination(array(
    'url' => get_config('wwwroot') . 'admin/site/fonts.php',
    'count' => $data->count,
    'limit' => $limit,
    'offset' => $offset,
    'datatable' => 'fontlist',
    'jsonscript' => 'admin/site/fonts.json.php',
    'resultcounttextsingular' => get_string('font', 'skin'),
    'resultcounttextplural' => get_string('fonts', 'skin')
));

$js = <<< EOF
    function wire_specimens() {
        $('.btn-display').each(function() {
            var btn = $(this);
            $(btn).off('click');
            $(btn).on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var url = $(this).prop('href');
                sendjsonrequest(url, {}, 'GET', function(data) {
                    $('#page-modal .modal-body').html(data.data.html);
                    $('#page-modal').modal('show');
                });
            });
        });
    }

jQuery(function ($) {
EOF;
if ($offset > 0) {
    $js .= <<< EOF
    if ($('#fontlist').length) {
      $('#fontlist a').first().trigger("focus");
    }
EOF;
}
else {
    $js .= <<< EOF
    if ($('#searchresultsheading').length) {
      $('#searchresultsheading')
      .addClass('hidefocus')
      .prop('tabIndex', 0)
      .trigger("focus");
    }
EOF;
}
$js .= '});';

$smarty = smarty(array('paginator'), $css, array(), array());
setpageicon($smarty, 'icon-paragraph');
$smarty->assign('INLINEJAVASCRIPT', $js);
$smarty->assign('query', $query);
$smarty->assign('sitefonts', $data->data);
$smarty->assign('preview', $fontpreview); // Transfer $SESSION value into template
$smarty->assign('size', $fontsize);       // Transfer $SESSION value into template
$html = $smarty->fetch('skin/sitefontresults.tpl');
$smarty->assign('sitefontshtml', $html);
$smarty->assign('form', $previewform);
$smarty->assign('pagination', $pagination['html']);
$smarty->assign('pagination_js', $pagination['javascript']);
$smarty->assign('PAGEHEADING', hsc(TITLE));
$smarty->display('skin/sitefonts.tpl');


function preview_submit(Pieform $form, $values) {
    global $SESSION;
    $SESSION->set('fontpreview', $values['fontpreview']);
    $SESSION->set('fontsize', $values['fontsize']);
    $SESSION->set('fonttype', $values['fonttype']);
    redirect(get_config('wwwroot') . 'admin/site/fonts.php?offset=' . $values['offset'] . '&limit=' . $values['limit'] . '&query=1');
}
