<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-textbox
 * @author     Catalyst IT Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 * @copyright  For copyright information on Mahara, please see the README file distributed with this software.
 *
 */

defined('INTERNAL') || die();

class PluginBlocktypeNavigation extends MaharaCoreBlocktype {

    public static function get_title() {
        return get_string('title', 'blocktype.navigation');
    }

    public static function get_description() {
        return get_string('description', 'blocktype.navigation');
    }

    public static function get_categories() {
        return array('general' => 20000);
    }

     /**
     * Optional method. If exists, allows this class to decide the title for
     * all blockinstances of this type
     */
    public static function get_instance_title(BlockInstance $bi) {
        $configdata = $bi->get('configdata');

        if (!empty($configdata['collection'])) {
            return $bi->get_data('collection', (int) $configdata['collection'])->get('name');
        }
        return '';
    }

    public static function render_instance(BlockInstance $instance, $editing=false, $versioning=false) {
        $configdata = $instance->get('configdata');
        $smarty = smarty_core();

        if (!empty($configdata['collection'])) {
            $collection = $instance->get_data('collection', (int) $configdata['collection']);
            $views = $collection->get('views');
            if (!empty($views)) {
                if ($collection->has_framework()) {
                    // Add the framework link to start of list
                    $framework = $collection->collection_nav_framework_option();
                    array_unshift($views['views'], $framework);
                }
                $smarty->assign('views', $views['views']);
            }
        }
        $smarty->assign('currentview',$instance->get('view'));
        return $smarty->fetch('blocktype:navigation:navigation.tpl');
    }

    // Called by $instance->get_data('collection', ...).
    public static function get_instance_collection($id) {
        require_once('collection.php');
        return new Collection($id);
    }

    public static function has_instance_config() {
        return true;
    }

    public static function instance_config_form(BlockInstance $instance) {
        $configdata = $instance->get('configdata');

        $view = $instance->get_view();
        $groupid = $view->get('group');
        $institutionid = $view->get('institution');
        $userid = $view->get('owner');
        $urlparams['new'] = 1;
        if (!empty($groupid)) {
            $where = 'c.group = ?'; $values = array($groupid);
            $urlparams['group'] = $groupid;
        }
        else if (!empty($institutionid)) {
            $where = 'c.institution = ?'; $values = array($institutionid);
            $urlparams['institution'] = $institutionid;
        }
        else {
            $where = 'c.owner = ?'; $values = array($userid);
        }
        ($collections = get_records_sql_array("
            SELECT c.id, c.name
                FROM {collection} c
            WHERE " . $where . "
            ORDER BY c.name, c.ctime ASC", $values))
            || ($collections = array());

        $default = false;
        $options = array();
        if (!empty($collections)) {
            foreach ($collections as $collection) {
                if (!$default) { // need to have an initially selected item
                    $default = $collection->id;
                }
                $options[$collection->id] = $collection->name;
            }
            $elements = array(
                'collection' => array(
                    'type' => 'select',
                    'title' => get_string('collection','blocktype.navigation'),
                    'rules' => array('required' => true),
                    'options' => $options,
                    'defaultvalue' => !empty($configdata['collection']) ? $configdata['collection'] : $default,
                ),
            );
            if ($pageincollection = $view->get_collection()) {
                $elements['copytoall'] = array(
                    'type' => 'switchbox',
                    'title' => get_string('copytoall', 'blocktype.navigation'),
                    'description' => get_string('copytoalldesc', 'blocktype.navigation'),
                    'defaultvalue' => false,
                );
            }
            return $elements;
        }
        else {
            $baseurl = get_config('wwwroot') . 'collection/edit.php';
            if ($urlparams) {
                $baseurl .= '?' . http_build_query($urlparams);
            }
            return array(
                'nocollections' => array(
                    'type'  => 'html',
                    'title' => get_string('collection', 'blocktype.navigation'),
                    'description' => get_string('nocollections1', 'blocktype.navigation', $baseurl),
                    'value' => '',
                ),
            );
        }

    }

    public static function instance_config_save($values, $instance) {
        if (!empty($values['copytoall'])) {
            $view = $instance->get('view_obj');
            if ($collection = $view->get_collection()) {
                foreach ($viewids = $collection->get_viewids() as $vid) {
                    if ($vid !== (int)$view->get('id')) {
                        $needsblock = true;
                        if ($blocks = get_records_sql_array("SELECT id FROM {block_instance} WHERE blocktype = ? AND view = ?", array('navigation', $vid))) {
                            foreach ($blocks as $block) {
                                // need to check the block to see if it's for this navigation
                                $bi = new BlockInstance($block->id);
                                $configdata = $bi->get('configdata');
                                if (!empty($configdata['collection']) && $configdata['collection'] == $values['collection']) {
                                    $needsblock = false;
                                }
                            }
                        }
                        if ($needsblock) {
                            // need to add new navigation block
                            $otherview = new View($vid);
                            $bidata = array(
                                'blocktype'  => 'navigation',
                                'title'      => $values['title'],
                                'configdata' => array(
                                    'collection' => $values['collection'],
                                    'retractable' => $values['retractable'],
                                    'retractedonload' => $values['retractedonload'],
                                ),
                            );
                            if ($otherview->uses_new_layout()) {
                                // Save block with dimensions
                                $bidata['positionx'] = 0;
                                $bidata['positiony'] = 0;
                                $bidata['width'] = 4;
                                $bidata['height'] = 3;
                            }
                            else {
                                // Save block in old layout
                                $bidata['row'] = 1;
                                $bidata['column'] = 1;
                                $bidata['order'] = get_field_sql("SELECT MAX(bi.order) + 1 FROM {block_instance} bi WHERE bi.view = ?", array($vid));
                            }
                            $bi = new BlockInstance(0, $bidata);
                            $otherview->addblockinstance($bi);
                        }
                    }
                }
            }
        }
        unset($values['copytoall']);
        return $values;
    }

    public static function default_copy_type() {
        return 'full';
    }


    /**
     * Change the collection ID format to match the ID format we use in Leap2A,
     * e.g.: portfolio:collection23
     *
     * @param BlockInstance $bi The blockinstance to export the config for.
     * @return array The config for the blockinstance
     */
    public static function export_blockinstance_config_leap(BlockInstance $bi) {
        $jsonconfigdata = parent::export_blockinstance_config_leap($bi);
        if (isset($jsonconfigdata['collection'])) {
            // It should be a collection...
            $collection = json_decode($jsonconfigdata['collection']);
            if (is_array($collection)) {
                $collection = $collection[0];
            }
            $jsonconfigdata['collection'] = json_encode(array('portfolio:collection' . (int) $collection));
        }
        return $jsonconfigdata;
    }


    /**
     * After a leap2a import, rewrite the block instance's collection ID to the collection's new ID.
     * (If the collection was part of this import. If it's not, just remove it.)
     *
     * @param int $blockinstanceid
     * @param PluginLeapImport $importer
     */
    public static function import_rewrite_blockinstance_relationships_leap($blockinstanceid, $importer) {
        $bi = new BlockInstance($blockinstanceid);
        $configdata = $bi->get('configdata');

        // Rewrite the collection ID from the old one to the new one.
        if (isset($configdata['collection'])) {
            $oldcollectionid = $configdata['collection'];

            // Backwards-compatibility for Leap2a files before we started rewriting the collection ID
            if (strpos($oldcollectionid, 'portfolio:collection') !== 0) {
                $oldcollectionid = 'portfolio:collection' . (int) $oldcollectionid;
            }

            if (isset($importer->collectionids[$oldcollectionid])) {
                // If the collection was present in this import, point to its new ID
                $configdata['collection'] = $importer->collectionids[$oldcollectionid];
            }
            else {
                // If the collection was not present, then deactivate this block
                // TODO: Make some guesses about what it should point at?
                unset($configdata['collection']);
            }
        }
        $bi->set('configdata', $configdata);
        $bi->commit();
    }

    /**
     * Shouldn't be linked to any artefacts via the view_artefacts table.
     *
     * @param BlockInstance $instance
     * @return multitype:
     */
    public static function get_artefacts(BlockInstance $instance) {
        return array();
    }
}
