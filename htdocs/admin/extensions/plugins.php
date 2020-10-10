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
define('ADMIN', 1);
define('MENUITEM', 'configextensions/pluginadmin');
require(dirname(dirname(dirname(__FILE__))) . '/init.php');
define('TITLE', get_string('pluginadmin', 'admin'));
define('SECTION_PLUGINTYPE', 'core');
define('SECTION_PLUGINNAME', 'admin');
define('SECTION_PAGE', 'plugins');
require('upgrade.php');

// @TODO when artefact plugins get installed, move the not installed blocktypes
// that get installed into the list of installed blocktype plugins

$plugins = array();
$plugins['blocktype'] = array();

foreach (plugin_types()  as $plugin) {
    // this has to happen first because of broken artefact/blocktype ordering
    $plugins[$plugin] = array();
    $plugins[$plugin]['installed'] = array();
    $plugins[$plugin]['notinstalled'] = array();
}
foreach (array_keys($plugins) as $plugin) {
    if (table_exists(new XMLDBTable($plugin . '_installed'))) {
        if ($installed = plugins_installed($plugin, true)) {
            foreach ($installed as $i) {
                $key = $i->name;
                if ($plugin == 'blocktype') {
                    $key = blocktype_single_to_namespaced($i->name, $i->artefactplugin);
                }
                if (!safe_require_plugin($plugin, $key)) {
                    continue;
                }

                $classname = generate_class_name($plugin, $key);
                $plugins[$plugin]['installed'][$key] = array(
                    'active' => $i->active,
                    'disableable' => call_static_method($classname, 'can_be_disabled'),
                    'deprecated' => call_static_method($classname, 'is_deprecated'),
                    'name' => call_static_method($classname, 'get_plugin_display_name'),
                    'dependencies' => call_static_method($classname, 'has_plugin_dependencies'),
                    'enableable' => call_static_method($classname, 'is_usable')
                );
                if (
                    ($i->active && $plugins[$plugin]['installed'][$key]['disableable'])
                    || (!$i->active && $plugins[$plugin]['installed'][$key]['enableable'])
                ){
                    $plugins[$plugin]['installed'][$key]['activateform'] = activate_plugin_form($plugin, $i);
                }
                if ($plugin == 'artefact') {
                    $plugins[$plugin]['installed'][$key]['types'] = array();
                    safe_require('artefact', $key);
                    if ($types = call_static_method(generate_class_name('artefact', $i->name), 'get_artefact_types')) {
                        foreach ($types as $t) {
                            $classname = generate_artefact_class_name($t);
                            if ($collapseto = call_static_method($classname, 'collapse_config')) {
                                $plugins[$plugin]['installed'][$key]['types'][$collapseto]['config'] = true;
                            }
                            else {
                                $plugins[$plugin]['installed'][$key]['types'][$t]['config'] =
                                    call_static_method($classname, 'has_config');
                            }
                            if ($collapseto = call_static_method($classname, 'collapse_config_info')) {
                                $plugins[$plugin]['installed'][$key]['types'][$collapseto]['info'] = true;
                            }
                            else {
                                $plugins[$plugin]['installed'][$key]['types'][$t]['info'] =
                                    call_static_method($classname, 'has_config_info');
                            }
                        }
                    }
                }
                else {
                    $classname = generate_class_name($plugin, $i->name);
                    safe_require($plugin, $key);
                    if (call_static_method($classname, 'has_config')) {
                        $plugins[$plugin]['installed'][$key]['config'] = true;
                    }
                    if (call_static_method($classname, 'has_config_info')) {
                        $plugins[$plugin]['installed'][$key]['info'] = true;
                    }
                }
            }
        }

        $dirhandle = opendir(get_config('docroot') . $plugin);
        while (false !== ($dir = readdir($dirhandle))) {
            $installed = false; // reinitialise
            if (strpos($dir, '.') === 0) {
                continue;
            }
            if (!is_dir(get_config('docroot') . $plugin . '/' . $dir)) {
                continue;
            }
            if (array_key_exists($dir, $plugins[$plugin]['installed'])) {
                $installed = true;
            }
            // if we're already installed keep going
            // if we're an artefact plugin, we have to check for blocktypes.
            if ($plugin != 'artefact' && !empty($installed)) {
                continue;
            }
            if (empty($installed)) {
                $plugins[$plugin]['notinstalled'][$dir] = array();
                try {
                    validate_plugin($plugin, $dir);
                    $classname = generate_class_name($plugin, $dir);
                    $classname::sanity_check();
                    $plugins[$plugin]['notinstalled'][$dir]['name'] = call_static_method($classname, 'get_plugin_display_name');
                    $plugins[$plugin]['notinstalled'][$dir]['dependencies'] = call_static_method($classname, 'has_plugin_dependencies');
                }
                catch (InstallationException $e) {
                    $plugins[$plugin]['notinstalled'][$dir]['notinstallable'] = $e->GetMessage();
                }
                // If there are 'required' dependencies then we mark the plugin notinstallable
                if (isset($plugins[$plugin]['notinstalled'][$dir]['dependencies']['requires'])) {
                    if (isset($plugins[$plugin]['notinstalled'][$dir]['notinstallable'])) {
                        $plugins[$plugin]['notinstalled'][$dir]['notinstallable'] .= $plugins[$plugin]['notinstalled'][$dir]['dependencies']['requires'];
                    }
                    else {
                        $plugins[$plugin]['notinstalled'][$dir]['notinstallable'] = $plugins[$plugin]['notinstalled'][$dir]['dependencies']['requires'];
                    }
                }
            }
            if ($plugin == 'artefact' && table_exists(new XMLDBTable('blocktype_installed'))) { // go check it for blocks as well
                $btlocation = get_config('docroot') . $plugin . '/' . $dir . '/blocktype';
                if (!is_dir($btlocation)) {
                    continue;
                }

                $btdirhandle = opendir($btlocation);
                while (false !== ($btdir = readdir($btdirhandle))) {
                    if (strpos($btdir, '.') === 0) {
                        continue;
                    }
                    if (!is_dir(get_config('docroot') . $plugin . '/' . $dir . '/blocktype/' . $btdir)) {
                        continue;
                    }
                    if (!array_key_exists($dir . '/' . $btdir, $plugins['blocktype']['installed'])) {
                        try {
                            if (!array_key_exists($dir, $plugins['artefact']['installed'])) {
                                throw new InstallationException(get_string('blocktypeprovidedbyartefactnotinstallable', 'error', $dir));
                            }
                            validate_plugin('blocktype', $dir . '/' . $btdir,
                                get_config('docroot') . 'artefact/' . $dir . '/blocktype/' . $btdir);
                            $plugins['blocktype']['notinstalled'][$dir . '/' . $btdir] = array();
                        }
                        catch (InstallationException $_e) {
                            $plugins['blocktype']['notinstalled'][$dir . '/' . $btdir]['notinstallable'] = $_e->getMessage();
                        }
                    }
                }
            }
        }
    }
}

global $THEME;

$loadingicon = 'icon icon-spinner icon-pulse';
$successicon = 'icon icon-check text-success';
$failureicon = 'icon icon-exclaimation-triangle';

$loadingstring = json_encode(get_string('upgradeloading', 'admin'));
$successstring = json_encode(get_string('upgradesuccesstoversion', 'admin'));
$failurestring = json_encode(get_string('upgradefailure', 'admin'));

$javascript = <<<JAVASCRIPT

var installplugin = (function($) {
  return function (name) {
      $('[id="' + name + '.message"]').html('<span class="{$loadingicon}" title=' + {$loadingstring} + '" role="presentation" aria-hidden="true"></span>');

      sendjsonrequest('../upgrade.json.php', { 'name': name }, 'GET', function (data) {
          if (!data.error) {
              var message = {$successstring} + data.newversion;
              $('[id="' + name + '.message"]').html('<span class="{$successicon}" title=":)" role="presentation" aria-hidden="true"></span>' + message);
              $('[id="' + name + '.install"]').html('');
              $('[id="' + name + '"]').removeClass('list-group-item-danger').addClass('list-group-item-success');
              // move the whole thing into the list of installed plugins
              // new parent node
              var bits = name.split('\.');
              $("ul[id='" + bits[0] + ".installed'] li").eq(0).after($('[id="' + name + '"]'));
              var oldlist = $("ul[id='" + bits[0] + ".notinstalled']").find('li:not(:has(h3))');
              if (oldlist.length == 0) {
                  $("ul[id='" + bits[0] + ".notinstalled']").hide();
              }
          }
          else {
              var message = '';
              if (data.errormessage) {
                  message = data.errormessage;
              }
              else {
                  message = {$failurestring};
              }
              $('[id="' + name + '"]').html('<span class="{$failureicon}" title=":(" role="presentation" aria-hidden="true"></span>' + message);
          }
      },
      function () {
          message = {$failurestring};
          $('[id="' + name + '"]').html(message);
      },
      true);
  }
}(jQuery));
JAVASCRIPT;

$plugins['blocktype']['configure'] = true;
$smarty = smarty();
setpageicon($smarty, 'icon-plug');

$smarty->assign('INLINEJAVASCRIPT', $javascript);
$smarty->assign('plugins', $plugins);
$smarty->assign('installlink', 'installplugin');
$smarty->display('admin/extensions/plugins.tpl');
