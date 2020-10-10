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

defined('INTERNAL') || die();

/**
 * module plugin class. Used for registering the plugin and functions.
 */
class PluginModuleFramework extends PluginModule {
    /**
     * Is the plugin activated or not?
     *
     * @return boolean true, if the plugin is activated, otherwise false
     */
    public static function is_active() {
        $active = false;
        if (get_field('module_installed', 'active', 'name', 'framework')) {
            $active = true;
        }
        return $active;
    }

    /**
     * API-Function get the Plugin ShortName
     *
     * @return string ShortName of the plugin
     */
    public static function get_plugin_display_name() {
        return 'smartevidence';
    }

    public static function postinst($prevversion) {
        if ($prevversion < 2016071400) {
            // Add foreign key to the collection.framework table on install
            log_debug('Add a foreign key on collection.framework to framework.id');
            $table = new XMLDBTable('collection');
            $field = new XMLDBField('framework');
            if (field_exists($table, $field)) {
                $key = new XMLDBKey('frameworkfk');
                $key->setAttributes(XMLDB_KEY_FOREIGN, array('framework'), 'framework', array('id'));
                add_key($table, $key);
            }
            // Add in any smart evidence framework data to the framework tables
            // based on any existing .matrix files in the matrices directory
            $matricesdir = get_config('docroot') . 'module/framework/matrices/';
            $files = glob($matricesdir . '*.matrix');
            foreach ($files as $file) {
                self::add_matrix_to_db($file);
            }
            // Activate annotation blocktype as it is used with smart evidence
            if (!is_plugin_active('annotation', 'blocktype')) {
                 set_field('blocktype_installed', 'active', 1, 'name', 'annotation');
            }
        }
    }

    public static function matrix_is_valid_json($filename) {
        global $SESSION;
        $ok = array('error' => false);
        $matrix = file_get_contents($filename);
        if (!$matrix) {
            $ok['error'] = true;
            $ok['message'] = get_string('invalidfilename', 'admin', $filename);
        }
        else {
            $content = json_decode($matrix);
            if (is_null($content)) {
                $ok['error'] = true;
                $ok['message'] = get_string('invalidjson', 'module.framework');
            }
            else {
                if (empty($content->framework) || empty($content->framework->name)) {
                    $ok['error'] = true;
                    $ok['message'] = get_string('jsonmissingvars', 'module.framework');
                }
                else {
                    $ok['content'] = $content;
                    if (isset($content->framework->standardelements)) {
                        // new style .matrix file so we need to alter the array to fit what we want
                        foreach ($content->framework->standards as $key => $standard) {
                            foreach ($content->framework->standardelements as $k => $element) {
                                if ($standard->standardid === $element->standardid) {
                                    if (!isset($content->framework->standards[$key]->standardelement)) {
                                        $content->framework->standards[$key]->standardelement = array();
                                    }
                                    $content->framework->standards[$key]->standardelement[] = $element;
                                }
                            }
                        }
                        unset($content->framework->standardelements);
                    }
                    //put ok content into session variable
                    $SESSION->set('jsoneditorcontent', $content);
                }
            }
        }
        return $ok;
    }

    public static function add_matrix_to_db($filename) {
        $ok = self::matrix_is_valid_json($filename);
        if ($ok['error']) {
            return false;
        }
        else {
            $framework = new Framework(null, $ok['content']->framework);
            $framework->commit();
            $framework->set_config_fields();
        }
    }

    public static function has_config() {
        return false;
    }

    public static function admin_menu_items() {

        if (!is_plugin_active('framework', 'module')) {
            return array();
        }

        $map = array(
            'configextensions/frameworks' => array(
                'path'   => 'configextensions/frameworks',
                'url'    => 'module/framework/frameworks.php',
                'title'  => get_string('frameworknav', 'module.framework'),
                'weight' => 50,
            ),
        );

        if (defined('MENUITEM') && isset($map[MENUITEM])) {
            $map[MENUITEM]['selected'] = true;
        }

        return $map;
    }

    // Set up 3rd level nav for upload json file vs. edit
    static function submenu_items($active_tab = null) {
        $tabs = array(
            'overview' => array(
                'page' => 'overview',
                'url'  => 'module/framework/frameworks.php',
                'title'=>  get_string('Management', 'module.framework'),
            ),
            'editor' => array(
                'page'  => 'editor',
                'url'   => 'module/framework/frameworks.php?upload=1',
                'title' => get_string('editor', 'module.framework'),
            ),
            'import' => array(
                'page'  => 'import',
                'url'   => 'module/framework/frameworks.php?uploadmatrix=1',
                'title' => get_string('Import', 'admin'),
            ),
        );
        if ($active_tab && isset($tabs[$active_tab])) {
            $tabs[$active_tab]['selected'] = true;
        }
        return $tabs;
    }
}

/**
 * module class
 */
class Framework {

    private $id;
    private $name;
    private $institution;
    private $institution_name;
    private $description;
    private $selfassess;
    private $active = 1; // active by default
    private $evidencestatuses;
    private $standards;

    const EVIDENCE_BEGUN = 0;
    const EVIDENCE_INCOMPLETE = 1;
    const EVIDENCE_PARTIALCOMPLETE = 2;
    const EVIDENCE_COMPLETED = 3;

    public function __construct($id=0, $data=null) {
        if (!empty($id)) {
            $tempdata = get_record('framework', 'id', $id);
            if (empty($tempdata)) {
                throw new FrameworkNotFoundException("Framework with id $id not found");
            }
            if (!empty($data)) {
                $data = array_merge((array)$tempdata, (array)$data);
            }
            else {
                $data = $tempdata; // use what the database has
            }
            $this->id = $id;
        }

        if (empty($data)) {
            $data = array();
        }
        foreach ((array)$data as $field => $value) {
            if (property_exists($this, $field)) {
                if (empty($id) && $field === 'standards') {
                    $value = array('standards' => $value,
                                   'count' => count($value));
                }
                if ($field == 'selfassess' || $field == 'active') {
                    $value = (int) $value;
                }
                if ($field === 'institution' && $value != 'all') {
                    $this->institution_name = get_field('institution', 'displayname', 'name', $value);
                }
                $this->{$field} = $value;
            }
        }
    }

    public function get($field) {
        if (!property_exists($this, $field)) {
            throw new InvalidArgumentException("Field $field wasn't found in class " . get_class($this));
        }
        if ($field == 'standards') {
            return $this->standards(false);
        }
        if ($field == 'standardsoptions') {
            return $this->standards();
        }
        if ($field == 'collections') {
            return $this->collections();
        }
        if ($field == 'evidencestatuses') {
            $this->evidencestatuses = self::get_evidence_statuses($this->id);
            return $this->evidencestatuses;
        }
        return $this->{$field};
    }

    public function set($field, $value) {
        if (property_exists($this, $field)) {
            $this->{$field} = $value;
            $this->mtime = time();
            return true;
        }
        throw new InvalidArgumentException("Field $field wasn't found in class " . get_class($this));
    }

    /**
     * Deletes a Framework
     */
    public function delete() {
        // Unable to delete if there are collections using this framework
        if ($this->is_in_collections()) {
            throw new MaharaException('Unable to delete framework - currently used in collections');
        }

        $standards = get_column('framework_standard', 'id', 'framework', $this->id);

        db_begin();
        delete_records('framework_evidence', 'framework', $this->id);
        delete_records('framework_evidence_statuses', 'framework', $this->id);
        delete_records('framework_assessment_feedback', 'framework', $this->id);
        if ($standards) {
            delete_records_sql('DELETE FROM {framework_standard_element} WHERE standard IN (' . join(',', array_map('intval', $standards)) . ')');
        }
        delete_records('framework_standard', 'framework', $this->id);
        delete_records('framework_config', 'framework', $this->id);
        delete_records('framework', 'id', $this->id);

        db_commit();
    }

    /**
     * This method updates the contents of the
     * - framework table
     * - framework_standard table (optional based on $this->standards data)
     * - framework_standard_element table (optional based on $this->standards data)
     */
    public function commit() {

        $fordb = new stdClass();
        foreach (get_object_vars($this) as $k => $v) {
            $fordb->{$k} = $v;
        }

        db_begin();

        // if id is not empty we are editing an existing framework
        if (!empty($this->id)) {
            update_record('framework', $fordb, 'id');
        }
        else {
            $id = insert_record('framework', $fordb, 'id', true);
            if ($id) {
                $this->set('id', $id);
            }
        }
        // update evidence statuses
        if (isset($this->evidencestatuses) && is_array($this->evidencestatuses)) {
            foreach ($this->evidencestatuses as $k => $choice) {
                $keystr = key((array) $choice);
                switch ($keystr) {
                 case 'begun':
                 case '0':
                    $key = self::EVIDENCE_BEGUN;
                    break;
                 case 'incomplete':
                 case '1':
                    $key = self::EVIDENCE_INCOMPLETE;
                    break;
                 case 'partialcomplete':
                 case '2':
                    $key = self::EVIDENCE_PARTIALCOMPLETE;
                    break;
                 case 'completed':
                 case '3':
                    $key = self::EVIDENCE_COMPLETED;
                    break;
                 default:
                    $key = $k;
                }
                $cfordb = new stdClass();
                $cfordb->framework = $this->id;
                $cfordb->name = isset($choice->{$keystr}) ? $choice->{$keystr} : '';
                $cfordb->type = $key;
                if ($choiceid = get_field('framework_evidence_statuses', 'id', 'framework', $this->id, 'type', $key)) {
                    $cfordb->id = $choiceid;
                    update_record('framework_evidence_statuses', $cfordb, 'id');
                }
                else {
                    insert_record('framework_evidence_statuses', $cfordb, 'id', true);
                }
            }
        }
        $standardsvars = array('shortname','name','description');

        if (isset($this->standards) && is_array($this->standards)) {
            $uniqueids = array();
            foreach ($this->standards['standards'] as $key => $standard) {
                $sfordb = new stdClass();
                $sfordb->framework = $this->id;
                $sfordb->mtime = db_format_timestamp(time());
                $sfordb->priority = $key;//needs to be a number, works if array of objects
                foreach ($standardsvars as $v) {
                    $sfordb->{$v} = isset($standard->{$v}) ? $standard->{$v} : null;
                }
                //update each standard
                if (!empty($standard->id)) {
                    $sid = $standard->id;
                    $sfordb->id = $standard->id;
                    update_record('framework_standard', $sfordb, 'id');

                }
                //create new standard
                else {
                    $sfordb->ctime = db_format_timestamp(time());
                    $sid = insert_record('framework_standard', $sfordb, 'id', true);
                }
                    // From .matrix file reading
                if (isset($standard->standardelement) && is_array($standard->standardelement)) {
                    $standard->options = $standard->standardelement;
                }
                if ($sid && isset($standard->options) && is_array($standard->options)) {

                    $priority = 0;
                    foreach ($standard->options as $option) {
                        $priority++;
                        $sofordb = new stdClass();
                        $sofordb->standard = $sid;
                        $sofordb->mtime = db_format_timestamp(time());
                        foreach ($standardsvars as $ov) {
                            $sofordb->{$ov} = isset($option->{$ov}) ? $option->{$ov} : null;
                        }
                        // set priority based on the order the array is passed in
                        $sofordb->priority = $priority;
                        if (!empty($option->id)) {//if existing se
                            $sofordb->id = $option->id;
                            if (!empty($option->elementid)) {
                                $uniqueids[$option->id] = $option->elementid;
                            }
                            if (($index = array_search($option->parentelementid, $uniqueids)) !== false) {
                                $sofordb->parent = $index;
                            }
                            update_record('framework_standard_element', $sofordb, 'id');
                        }
                        else {//otherwise it's new
                            $sofordb->ctime = db_format_timestamp(time());
                            if (isset($option->parentelementid) && ($index = array_search($option->parentelementid, $uniqueids)) !== false) {
                                $option->parentelementid = $index;//db id for the parent
                            }
                            else if (isset($option->parentelementid) && $option->parentelementid == 'undefined') {
                                $option->parentelementid = null;
                            }
                            $sofordb->parent = !empty($option->parentelementid) ? $option->parentelementid : null;
                            //where se record goes in
                            $inserted = insert_record('framework_standard_element', $sofordb, 'id', true);
                            if (!empty($option->elementid)) {
                                $uniqueids[$inserted] = $option->elementid;
                            }
                        }
                    }
                }
            }
        }
        db_commit();
    }

    function set_config_fields() {
        if (db_table_exists('framework_config')) {
            $status = new stdClass();

            $status->framework = $this->get('id');
            $status->field     = 'completed_field_enabled';
            $status->value     = 1;
            insert_record('framework_config', $status);

            $status->framework = $this->get('id');
            $status->field     = 'readyforassesment_field_enabled';
            $status->value     = 0;
            insert_record('framework_config', $status);

            $status->framework = $this->get('id');
            $status->field     = 'dontmatch_field_enabled';
            $status->value     = 0;
            insert_record('framework_config', $status);

            $status->framework = $this->get('id');
            $status->field     = 'partiallycomplete_field_enabled';
            $status->value     = 0;
            insert_record('framework_config', $status);
        }
    }

    /**
     * Returns array of standards in the current framework
     *
     * @param boolean $options  Set to false if you only want the standards without substandard options
     * @return array standards
     */
    public function standards($options = true) {

        if (!isset($this->standards)) {

            $sql = "SELECT id, shortname, name, description, priority, ctime, mtime
                     FROM {framework_standard}
                     WHERE framework = ?
                    ORDER BY priority, shortname, name, ctime";

            $result = get_records_sql_assoc($sql, array($this->get('id')));

            if (!empty($result)) {
                if ($options) {
                    // get all options relating to the standards
                    $sql = "SELECT id, standard, shortname, name, description, priority, parent, ctime, mtime,
                             CASE WHEN fse.id > 0 THEN (
                                SELECT COUNT(parent) FROM {framework_standard_element}
                                WHERE parent = fse.id
                             ) END AS children
                             FROM {framework_standard_element} fse
                             WHERE standard IN (" . join(',', array_map('intval', array_keys($result))) . ")
                             ORDER BY standard, priority, shortname, name, ctime";

                    $optresult = get_records_sql_assoc($sql, array());
                    $indents = array();
                    if ($optresult) {
                        $currentlevel = 0;
                        foreach ($optresult as $opt) {
                            if (!isset($result[$opt->standard]->options)) {
                                $result[$opt->standard]->options = array();
                            }
                            $result[$opt->standard]->options[] = $opt;
                            $opt->level = 0;
                            if ($opt->children) {
                                $indents[$opt->id] = $opt->children;
                                $currentlevel ++;
                            }
                            if (isset($indents[$opt->parent]) && $indents[$opt->parent] > 0) {
                                $opt->level = $currentlevel;
                                if (!empty($opt->children) && !empty($opt->parent)) {
                                    $opt->level --;
                                }
                                $indents[$opt->parent] --;
                                if ($indents[$opt->parent] === 0) {
                                    unset($indents[$opt->parent]);
                                    $currentlevel --;
                                }
                            }
                        }
                    }
                }

                $standards = array(
                    'standards' => array_values($result),
                    'count'     => count($result),
                );

                $this->standards = $standards;
            }
            else {
                $this->standards = array();
            }

        }

        return $this->standards;
    }

    /**
     * Check that the framework is being used by a collection
     *
     * @return boolean
     */
    public function is_in_collections() {
        if (!isset($this->collections)) {
            $this->collections();
        }
        if (empty($this->collections)) {
            return false;
        }
        return true;
    }

    /**
     * Get collections that use the framework
     *
     * @return object $collections
     */
    public function collections() {
        require_once('collection.php');
        if (!isset($this->collections)) {
            $collections = array();
            $ids = get_column('collection', 'id', 'framework', $this->id);
            foreach ($ids as $id) {
                $collection = new Collection($id);
                $collections[] = $collection;
            }
            $this->collections = $collections;
        }

        return $this->collections;
    }

    /**
     * Get ids of collections that use the framework
     *
     * @return array $ids
     */
    public function get_collectionids() {
        $ids = array();
        $data = $this->collections();

        if (!empty($data)) {
            foreach ($data as $c) {
                $ids[] = $c->get('id');
            }
        }

        return $ids;
    }

    /**
     * Return the current state as part of array of all states
     * Includes the state classes that render the circles/colours
     *
     * @param string $state Current state
     * @param bool  $current  return only the current state item rather than full array
     *
     * @return array All states with current active
     */
    public static function get_state_array($state, $current = false) {
        $states = array(
            'begun' => array(
                'state' => (int) $state === self::EVIDENCE_BEGUN ? 1 : 0,
                'classes' => 'icon icon-regular icon-circle begun',
            ),
            'incomplete' => array(
                'state' => (int) $state === self::EVIDENCE_INCOMPLETE ? 1 : 0,
                'classes' => 'icon icon-times-circle incomplete',
            ),
            'partialcomplete' => array(
                'state' => (int) $state === self::EVIDENCE_PARTIALCOMPLETE ? 1 : 0,
                'classes' => 'icon icon-adjust partial',
            ),
            'completed' => array(
                'state' => (int) $state === self::EVIDENCE_COMPLETED ? 1 : 0,
                'classes' => 'icon icon-check-circle completed',
            ),
        );
        if ($current) {
            foreach ($states as $state) {
                if ($state['state'] === 1) {
                    return $state;
                }
            }
        }
        return $states;
    }

    /**
     * Get available frameworks based on institution
     *
     * @param string  $institution  If set to 'any' all results returned
     * @param boolean $shared       Return frameworks that can be viewed by all institutions
     *
     * @return frameworks
     */
    public static function get_frameworks($institution = 'any', $shared = false) {
        global $USER;

        $sql = "SELECT * FROM {framework}";
        $values = array();
        if ($institution != 'any') {
            // Only get the frameworks available to this institution
            $placeholders = '?';
            $values[] = $institution;
            if ($shared) {
                // Include frameworks with institution set to 'all'
                $placeholders .= ',?';
                $values[] = 'all';
            }
            $sql .= " WHERE institution IN (" . $placeholders . ")";
        }
        $sql .= " ORDER BY name, id";
        $frameworks = get_records_sql_array($sql, $values);
        return $frameworks;
    }

    /**
     * Get evidence for a collection.
     *
     * @param int $collectionid The id of the collection we are wanting evidence for
     * @param int $annotationid Optional return only the evidence for a single block
     *
     * @return mixed array / false Depending if evidence is found
     */
    public function get_evidence($collectionid, $annotationid = false) {
        if ($viewids = get_column('collection_view', 'view', 'collection', $collectionid)) {
            $evidence = get_records_sql_array('SELECT * FROM {framework_evidence} WHERE framework = ? AND view IN (' . join(',', $viewids) . ')', array($this->id));
            if (!empty($annotationid) && $evidence) {
                foreach ($evidence as $e) {
                    if ($e->annotation === $annotationid) {
                        return $e;
                    }
                }
                return false;
            }
            return $evidence;
        }
        return false;
    }

    /**
     * Add/update an annotation block on a view via the framework matrix page.
     * This hooks into using the annotation block's config form.
     *
     * @param object $data Data for populating the annotation config form
     *                     and building an annotation block instance
     *
     * @return array Info for the config form
     */
    public static function annotation_config_form($data) {
        require_once(get_config('docroot') . 'blocktype/lib.php');
        if (empty($data->annotation)) {
            // Get the title for the option
            $title = get_field('framework_standard_element', 'shortname', 'id', $data->option);

            $view = new View($data->view);
            $blockpositiony = $view->bottomfreeposition();

            // Create the block at the end of the 'cell'.
            $annotation = new BlockInstance(0, array(
                'blocktype'  => 'annotation',
                'title'      => (get_string('Annotation', 'artefact.annotation') . ': ' . $title),
                'view'       => $data->view,
                'positionx'  => 0,
                'positiony'  => (int)$blockpositiony,
                'width'      => 4,
                'height'     => 3,
            ));
            $annotation->commit();
            $new = true;
        }
        else {
            $annotation = new BlockInstance($data->annotation);
            $new = false;
        }
        $title = $annotation->get_title();
        $annotation->option = $data->option;
        $annotation->frommatrix = true;
        list($content, $js, $css) = array_values($annotation->build_configure_form($new));

        $return = array(
            'content' => $content,
            'js' => $js,
            'css' => $css,
            'title' => $title,
            'isnew' => $new
        );
        return $return;
    }

    /**
     * Save evidence
     * @param string $id          Framework_evidence id
     * @param string $framework   Framework id                  }
     * @param string $element     Framework_standard_element id }  A unique grouping
     * @param string $view        View id                       }
     * @param string $annotation  Annotation block id (not artefact id)
     * @param string $state       See constants in this class
     * @param string $reviewer    The user marking the evidence as completed
     */
    public static function save_evidence($id = null, $framework = null, $element = null, $view = null, $annotation = null, $state = self::EVIDENCE_BEGUN, $reviewer = null) {
        global $USER;

        // need to check we have at least one indicator of uniqueness
        $uniqueness = false;
        if (!empty($id)) {
            $uniqueness = true;
        }
        else if (!empty($framework) && !empty($element) && !empty($view)) {
            $uniqueness = true;
        }

        if (!$uniqueness) {
            throw new ParamOutOfRangeException('No unique identifier supplied');
        }

        $fordb = array('mtime' => db_format_timestamp(time()),
                       'annotation' => $annotation,
                       'state' => $state);
        if ($id) {
            // get view
            $evidence = get_record('framework_evidence', 'id', $id);
            $view = $evidence->view;
            // update row
            if (!empty($element)) {
                $fordb['element'] = $element;
            }
            $fordb['reviewer'] = ((int) $state === self::EVIDENCE_COMPLETED) ? $reviewer : null;
            update_record('framework_evidence', (object) $fordb, (object) array('id' => $id));
            if ($evidence->state != $state) {
                // need to add a blank annotationion feedback and assessment evidence combo
                safe_require('blocktype', 'annotation');
                $block = new BlockInstance($evidence->annotation);
                $configdata = $block->get('configdata');

                $data = (object) array(
                    'title'        => get_string('Annotation', 'artefact.annotation'),
                    'description'  => '',
                    'onannotation' => $configdata['artefactid'],
                );
                $viewobj = new View($view);
                $data->view        = $viewobj->get('id');
                $data->owner       = $viewobj->get('owner');
                $data->group       = $viewobj->get('group');
                $data->institution = $viewobj->get('institution');
                $data->author      = $USER->get('id');
                $data->private     = 0;
                $annotationfeedback = new ArtefactTypeAnnotationfeedback(0, $data);
                $annotationfeedback->commit();

                // We need to log this assessment change
                insert_record('framework_assessment_feedback', (object) array('framework' => $evidence->framework,
                                                                              'artefact' => $annotationfeedback->get('id'),
                                                                              'oldstatus' => $evidence->state,
                                                                              'newstatus' => $state,
                                                                              'usr' => $USER->get('id')));
            }
        }
        else {
            // insert
            $fordb['view'] = $view;
            $fordb['element'] = $element;
            $fordb['framework'] = $framework;
            $fordb['ctime'] = db_format_timestamp(time());
            $id = insert_record('framework_evidence', (object) $fordb, 'id', true);
        }
        // We need to update mtime for the view
        require_once('view.php');
        $view = new View($view);
        $view->set('mtime', time());
        $view->commit();
        return $id;
    }

    /**
     * Save evidence when adding block to page on block edit view
     *
     * @param string $blockid  Block id
     * @param string $element  The framework_standard_element id
     */
    public static function save_evidence_in_block($blockid, $element) {
        $evidence = get_record('framework_evidence', 'annotation', $blockid);
        $id = !empty($evidence) ? $evidence->id : null;

        if (!$id) {
            // We need to find the view/framework info via the blockid
            if ($records = get_records_sql_array("SELECT bi.view, c.framework FROM {block_instance} bi
                                                 JOIN {collection_view} cv ON cv.view = bi.view
                                                 JOIN {collection} c ON c.id = cv.collection
                                                 WHERE bi.id = ?", array($blockid))) {
                $record = $records[0];
                try {
                    $id = self::save_evidence(null, $record->framework, $element, $record->view, $blockid);
                    return $id;
                }
                catch (SQLException $e) {
                    // An error occured like an existing annotation block exist for this view/standard option
                    return false;
                }
            }
            else {
                // block not on a page that is in a collection that is using a framework
                return false;
            }
        }
        else {
            try {
                $id = self::save_evidence($id, null, $element, null, $blockid);
                return $id;
            }
            catch (SQLException $e) {
                // An error occured like an existing annotation block exist for this view/standard option
                return false;
            }
        }
    }

    /**
     * Add/update an annotation status form on the framework matrix page.
     * This uses a feedback style config form with some extra bits.
     */
    public static function annotation_feedback_form($data) {
        global $USER;

        require_once(get_config('docroot') . 'blocktype/lib.php');
        $annotation = new BlockInstance($data->annotation);
        $configdata = $annotation->get('configdata');
        if (empty($configdata['artefactid'])) {
            return false;
        }

        safe_require('artefact', 'file');
        $artefactid = $configdata['artefactid'];
        $artefact = $annotation->get_artefact_instance($artefactid);
        $view = $annotation->get_view();
        $text = $artefact->get('description');
        $collection = $view->get('collection');
        $evidence = get_record('framework_evidence', 'annotation', $annotation->get('id'));
        $defaultval = $evidence->state;
        $selfassess = get_field('framework', 'selfassess', 'id', $evidence->framework);

        if (!is_object($collection) || !$collection->get('framework')) {
            return false;
        }

        $annotationhtml = clean_html($text);
        $annotationdivhtml = '<div class="modal-header modal-section">' . get_string("assessment", "module.framework") . '</div>';
        $assessmenthtml = '<div class="top-line select form-group">
                            <span class="pseudolabel">' . get_string("assessment", "module.framework") . '</span>
                            ' . self::get_evidence_statuses($collection->get('framework'))[$defaultval] . '
                          </div>';

        $options = self::get_my_assessment_options_for_user($view, $evidence->framework);
        if (!$options || !array_key_exists($defaultval, $options)
            || ($view->get('owner') == $USER->get('id') && !$selfassess)
            || ($view->get('owner') != $USER->get('id') && $selfassess)
            ) {
            // not allowed to set the assessment so we just show the current state as html
            $smarty = smarty_core();
            $smarty->assign('annotationhtml', $annotationhtml);
            $smarty->assign('annotationdivhtml', $annotationdivhtml);
            $smarty->assign('assessmenthtml', $assessmenthtml);
            $content = $smarty->fetch('module:framework:evidencestatus.tpl');
        }
        else {
            // Show the select box with current state selected
            $assessment = array(
                'type' => 'select',
                'title' => get_string('assessment', 'module.framework'),
                'options' => $options,
                'defaultvalue' => $defaultval,
                'width' => '280px',
                'class' => 'top-line',
            );

            $form = array(
                'name' => 'annotationfeedback',
                'jsform' => true,
                'renderer' => 'div',
                'plugintype' => 'module',
                'pluginname' => 'framework',
                'jssuccesscallback' => 'updateAnnotation',
                'elements'   => array(
                    'annotation' => array(
                        'type' => 'html',
                        'value' => $annotationhtml,
                    ),
                ),
            );
            if ($options) {
                $form['elements']['annotationdiv'] = array(
                    'type' => 'html',
                    'value' => $annotationdivhtml,
                );
                $form['elements']['assessment'] = $assessment;
            }
            $frameworkurl = $collection->collection_nav_framework_option();
            if ($options) {
                $form['elements']['submitcancel'] = array(
                    'type' => 'submitcancel',
                    'subclass' => array('btn-secondary'),
                    'value' => array(get_string('save'), get_string('cancel')),
                    'goto' => $frameworkurl->fullurl,
                );
            }
            $content = pieform($form);
        }
        list($feedbackcount, $annotationfeedback) = ArtefactTypeAnnotationfeedback::get_annotation_feedback_for_matrix($artefact, $view, $annotation->get('id'));
        $content .= $annotationfeedback;

        $return = array(
            'content' => $content,
            'js' => 'function updateAnnotation(form, data) { formSuccess(form, data); }',
            'css' => '',
            'title' => $annotation->get_title(),
        );
        return $return;
    }

    /**
     * Check to see if a user can add an annotation via the matrix page. Currently only view owner
     *
     * @param string $viewid    The view the matrix point is associated with
     *
     * @return bool
     */
    public static function can_annotate_view($viewid) {
        global $USER;

        if (empty($viewid) || !is_numeric($viewid)) {
            return false;
        }

        require_once(get_config('libroot') . 'view.php');
        $view = new View($viewid);
        $collection = $view->get('collection');
        if (!is_object($collection)) {
            return false;
        }
        $framework = $collection->get('framework');
        if (empty($framework)) {
            return false;
        }

        $userid = $USER->get('id');
        if ($USER->get('id') == $view->get('owner')) {
            // Is owner
            return true;
        }
        return false;
    }

    /**
     * Check to see if a user can set the assessment status for a piece of evidence.
     *
     * @param string $view   The view being assessed
     * @param string $framework ID of the framework
     *
     * @return bool
     */
    public static function can_assess_user($view, $framework = null) {
        return (boolean) static::get_my_assessment_options_for_user($view, $framework);
    }

    /**
     * Get assessment status options for a piece of evidence.
     *
     * @param string $view The view being assessed
     * @param string $framework ID of the framework

     * @return array Options for select dropdown
     */
    public static function get_my_assessment_options_for_user($view, $framework = null) {
        global $USER;

        $ownerid = $view->get('owner');

        if (empty($ownerid) || !is_numeric($ownerid)) {
            return false;
        }

        $owner = new User();
        $owner->find_by_id($ownerid);
        $ownerinstitutions = array_keys($owner->get('institutions'));
        $institution = (!empty($ownerinstitutions)) ? $ownerinstitutions[0] : 'mahara';
        $isowner = ($owner->get('id') === $USER->get('id'));
        $isadminofowner = $selfcomplete = false;

        if ($USER->get('admin') || $USER->get('staff')) {
            if ($USER->get('id') != $owner->get('id')) {
                $isadminofowner = true;
            }
        }
        else if ($institution != 'mahara' && ($USER->is_institutional_admin($institution) || $USER->is_institutional_staff($institution))) {
            if ($USER->get('id') != $owner->get('id')) {
                $isadminofowner = true;
            }
        }
        else if (!empty($USER->get('id')) && !empty($view->get('submittedgroup')) && group_user_can_assess_submitted_views($view->get('submittedgroup'), $USER->get('id'))) {
            if ($USER->get('id') != $owner->get('id')) {
                $isadminofowner = true;
            }
        }

        require_once(get_config('libroot') . 'institution.php');
        $institution = new Institution($institution);
        // Check that smart evidence self assessment is enabled for the framework
        if ($framework) {
            $fmk = new Framework($framework);
            if ($fmk->selfassess) {
                $selfcomplete = true;
            }
        }

        if ($isowner || $isadminofowner) {
            $reply = self::get_evidence_statuses($framework);
            if (($isowner && $selfcomplete === false) ||
                ($isadminofowner && $selfcomplete === true)) {
                unset($reply[1]);
                unset($reply[2]);
                unset($reply[3]);
            }
            return $reply;
        }
        return false;
    }

    /**
     * Get array of all status options with evidence state integer as key
     * The array either contains provided evidence status in db (via the .matrix file
     * in the 'evidencestatuses' array) or uses the default strings if none provided.
     *
     * @param string $id The id of the framework
     *
     * @return array  Array containing the status names for all the statuses
     */
    public static function get_evidence_statuses($id) {
        $statuses = array(
            self::EVIDENCE_BEGUN => get_string('begun','module.framework'),
            self::EVIDENCE_INCOMPLETE => get_string('incomplete','module.framework'),
            self::EVIDENCE_PARTIALCOMPLETE => get_string('partialcomplete','module.framework'),
            self::EVIDENCE_COMPLETED => get_string('completed','module.framework')
        );
        if ($records = get_records_array('framework_evidence_statuses', 'framework', $id)) {
            $map = array();
            foreach ($records as $record) {
                $statuses[$record->type] = $record->name;
            }
        }
        return $statuses;
    }

    function get_config($value) {
        $record = false;
        if (db_table_exists('framework_config') &&
            $record = get_record('framework_config', 'framework', $this->id, 'field', $value)) {
            return $record->value;
        }
        return $record;
    }

    function set_config($field, $value) {
        set_field('framework_config', 'value', $value, 'field', $field, 'framework', $this->id);
    }

    public function config_option_enabled($configoption) {
        $enabled = false;
        if ($configoption == 'active_framework') {
            $enabled = $this->get('active');
        }
        else {
            $enabled = $this->get_config($configoption);
        }
        return $enabled;
    }

    public function get_config_option_fields() {
        $options = array(
                    'active_framework',
                    'readyforassesment_field_enabled',
                    'dontmatch_field_enabled',
                    'partiallycomplete_field_enabled',
                    'completed_field_enabled',
                );
        return $options;
    }

    public function get_framework_config_options() {
        // check if the plugin has been upgraded
        // if not, all status settings should be greyed out
        $disabled = !db_table_exists('framework_config');
        $warning = array();
        if ($disabled) {
            $warning['plugin_warning'] = array(
                    'type'  => 'markup',
                    'value' => '<div class="admin-warning alert alert-warning">' .
                               '<p>' .  get_string('upgradeplugin', 'module.framework') . '</p></div>',
            );
        }

        $choices = Framework::get_evidence_statuses($this->get('id'));

        $options = array(
            'active_framework' => array(
                'type'  => 'switchbox',
                'title' => get_string('activeframework','module.framework'),
                'defaultvalue' => $this->config_option_enabled('active_framework'),
            ),
            'statusestitle' => array(
                'type'  => 'html',
                'value' => '<h2 class="title">' . get_string('displaystatusestitle','module.framework') . '</h2>' .
                           "<p>" . get_string('displaystatusestitledetail','module.framework') . "</p>",
            ),
            'readyforassesment_container' => array(
                'type' => 'fieldset',
                'class' => 'form-inline',
                'elements' => array(
                    'label' => array(
                        'type'=> 'html',
                        'value' => '<div class="pseudolabel"><span>' . $choices[Framework::EVIDENCE_BEGUN] . '</span>' .
                                   '<div class="statusheader text-inline"><span class="' . $this->get_state_array(Framework::EVIDENCE_BEGUN)['begun']['classes'] . '"></span></div></div>',
                    ),
                    'readyforassesment_field_enabled' => array(
                        'type'  => 'switchbox',
                        'title' => '',
                        'defaultvalue' => $this->config_option_enabled('readyforassesment_field_enabled'),
                        'disabled' => $disabled,
                    ),
                ),
            ),
            'dontmatch_container' => array(
                'type' => 'fieldset',
                'class' => 'form-inline',
                'elements' => array(
                    'label' => array(
                        'type'=> 'html',
                        'value' => '<div class="pseudolabel"><span>' . $choices[Framework::EVIDENCE_INCOMPLETE] . '</span>' .
                                   '<div class="statusheader text-inline"><span class="' . $this->get_state_array(Framework::EVIDENCE_INCOMPLETE)['incomplete']['classes'] . '"></span></div></div>',
                    ),
                    'dontmatch_field_enabled' => array(
                        'type'  => 'switchbox',
                        'title' => '',
                        'defaultvalue' => $this->config_option_enabled('dontmatch_field_enabled'),
                        'disabled' => $disabled,
                    ),
                ),
            ),
            'partiallycomplete_container' => array(
                'type' => 'fieldset',
                'class' => 'form-inline',
                'elements' => array(
                    'label' => array(
                        'type'=> 'html',
                        'value' => '<div class="pseudolabel"><span>' . $choices[Framework::EVIDENCE_PARTIALCOMPLETE] . '</span>' .
                                   '<div class="statusheader text-inline"><span class="' . $this->get_state_array(Framework::EVIDENCE_PARTIALCOMPLETE)['partialcomplete']['classes'] . '"></span></div></div>',
                    ),
                    'partiallycomplete_field_enabled' => array(
                        'type'  => 'switchbox',
                        'title' => '',
                        'defaultvalue' => $this->config_option_enabled('partiallycomplete_field_enabled'),
                        'disabled' => $disabled,
                    ),
                ),
            ),
            'completed_container' => array(
                'type' => 'fieldset',
                'class' => 'form-inline',
                'elements' => array(
                    'label' => array(
                        'type'=> 'html',
                        'value' => '<div class="pseudolabel"><span>' . $choices[Framework::EVIDENCE_COMPLETED] . '</span>' .
                                   '<div class="statusheader text-inline"><span class="' . $this->get_state_array(Framework::EVIDENCE_COMPLETED)['completed']['classes'] . '"></span></div></div>',
                    ),
                    'completed_field_enabled' => array(
                        'type'  => 'switchbox',
                        'title' => '',
                        'value' => $disabled || $this->config_option_enabled('completed_field_enabled'),
                        'disabled' => true,
                    ),
                ),
            ),
        );

        return array(
            'elements' => array_merge($warning, $options),
        );
    }

    public function save_config_options(Pieform $form, $values) {

        $configoptions = $this->get_config_option_fields();

        foreach ($configoptions as $option) {
            if (isset($values[$option])) {
                $enabled = ($values[$option] == true || $values[$option] == 1) ? 1 : 0;
                if ($option === 'active_framework') {
                    $this->set('active', $enabled);
                    $this->commit();
                }
                else {
                    if ($option != 'completed_field_enabled')
                    $this->set_config($option, $enabled);
                }
            }
        }
    }

}

class FrameworkNotFoundException extends NotFoundException {}

/**
 * The functions for verifying/saving the matrix upload
 */
function upload_matrix_form() {

    $elements = array(
        'matrix' => array(
            'type' => 'file',
            'title' => get_string('matrixfile', 'module.framework'),
            'description' => get_string('matrixfiledesc', 'module.framework'),
            'accept' => '.matrix',
            'rules' => array(
               'required' => true
            )
        ),
        'submit' => array(
            'type' => 'submitcancel',
            'subclass' => array('btn-primary'),
            'value' => array(get_string('savematrix','module.framework'), get_string('cancel')),
            'goto' => get_config('wwwroot') . 'module/framework/frameworks.php',
        )
    );

    $form = array(
        'name' => 'matrixupload',
        'plugintype' => 'module',
        'pluginname' => 'framework',
        'validatecallback' => 'validate_matrixupload',
        'successcallback' => 'matrixupload_submit',
        'elements' => $elements,
    );

    return pieform($form);
}

function validate_matrixupload(Pieform $form, $values) {
    require_once('uploadmanager.php');
    if (empty($values['matrix'])) {
        $form->set_error('matrix', get_string('matrixfilenotfound', 'module.framework'));
        return;
    }
    $um = new upload_manager('matrix');
    if ($error = $um->preprocess_file()) {
        $form->set_error('matrix', $error);
        return;
    }
    $reqext = ".matrix";
    $fileext = substr($values['matrix']['name'], (-strlen($reqext)));
    if ($fileext !== $reqext) {
        $form->set_error('matrix', get_string('notvalidmatrixfile', 'module.framework'));
        return;
    }
    $matrixfile = PluginModuleFramework::matrix_is_valid_json($um->file['tmp_name']);
    if ($matrixfile['error']) {
        $form->set_error('matrix', $matrixfile['message']);
    }
}

function matrixupload_submit(Pieform $form, $values) {
    PluginModuleFramework::add_matrix_to_db($values['matrix']['tmp_name']);
    redirect(get_config('wwwroot') . 'module/framework/frameworks.php');
}
