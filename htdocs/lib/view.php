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

class View {
    private $dirty;
    private $deleted;
    private $id;
    private $owner;
    private $ownerformat;
    private $group;
    private $institution;
    private $ctime;
    private $mtime;
    private $atime;
    private $startdate;
    private $stopdate;
    private $submittedgroup;
    private $submittedhost;
    private $submittedtime;
    private $submittedstatus;
    private $title;
    private $description;
    private $loggedin;
    private $friendsonly;
    private $artefact_instances;
    private $artefact_metadata;
    private $ownerobj;
    private $groupobj;
    private $institutionobj;
    private $numcolumns; // Obsolete - need to leave for upgrade purposes. This can be deleted once we no longer need to support direct upgrades from 15.10 and earlier.
    private $columnsperrow; // assoc array of rows set and get using view_rows_columns db table
    private $oldcolumnsperrow; // for when we change stuff
    private $numrows;
    private $layout;
    private $theme;
    private $rows;
    private $columns;
    private $tags;
    private $categorydata;
    private $template;
    private $retainview;
    private $copynewuser = 0;
    private $copynewgroups;
    private $type;
    private $visits;
    private $allowcomments;
    private $approvecomments;
    private $collection;
    private $locked;
    private $urlid;
    private $skin;
    private $anonymise = 0;
    private $lockblocks = 0;
    private $instructions;
    private $instructionscollapsed=0;
    private $newlayout = 1;
    private $grid;
    private $accessibleview = 0;
    private $coverimage;
    private $locktemplate = 0;

    const UNSUBMITTED = 0;
    const SUBMITTED = 1;
    const PENDING_RELEASE = 2;

    // constansts view templates
    const USER_TEMPLATE = 1;
    const SITE_TEMPLATE = 2;

    /**
     * Which view layout is considered the "default" for views with the given
     * number of columns. Must be present in $layouts of course.
     */
    public static $defaultcolumnlayouts = array(
            1 => '100',
            2 => '50,50',
            3 => '33,33,33',
            4 => '25,25,25,25',
            5 => '20,20,20,20,20',
    );

    /**
     * Valid view column layouts. These are read at install time and inserted into
     * view_layout_columns, but not updated afterwards, so if you're changing one
     * you'll need to do that manually.
     *
     * The key represents the number of columns, and the value is an array of all the
     * view_layout_columns records that have that number of columns
     */
    public static $basic_column_layouts = array(
        1 => array(
            '100',
        ),
        2 => array(
            '50,50',
            '67,33',
            '33,67',
        ),
        3 => array(
            '33,33,33',
            '25,50,25',
            '25,25,50',
            '50,25,25',
            '15,70,15',
        ),
        4 => array(
            '25,25,25,25',
            '20,30,30,20',
        ),
        5 => array(
            '20,20,20,20,20',
        ),
    );

    /**
     * The default layout options to be read at install time.
     * Each view_layout record is based on the array key and the count of its values.
     * Each view_layout_rows_columns record is based on the sub array.
     * For example:
     *  18 => array(
     *              1 => '100',
     *              2 => '50,50',
     *              3 => '100'
     *              'order' => 3
     *  ),
     * will insert a record in view_layout with id = 18 and rows = 3
     * and will insert 3 records in view_layout_rows_columns:
     *  - viewlayout = 18, rows = 1, columns = 1
     *  - viewlayout = 18, rows = 2, columns = 2
     *  - viewlayout = 18, rows = 3, columns = 1
     * And the "order" key indicates that this should be the 3rd option in the layout menu
     */
    public static $defaultlayoutoptions = array(
        1 => array(
                1 => '100',
                'order' => 1,
            ),
        2 => array(
                1 => '50,50',
                'order' => 2,
            ),
        3 => array(
                1 => '67,33',
                'order' => 3,
            ),
        4 => array(
                1 => '33,67',
                'order' => 4,
            ),
        5 => array(
                1 => '33,33,33',
                'order' => 5,
            ),
        6 => array(
                1 => '25,50,25',
                'order' => 6,
            ),
        7 => array(
                1 => '25,25,50'
            ),
        8 => array(
                1 => '50,25,25'
            ),
        9 => array(
                1 => '15,70,15'
            ),
        10 => array(
                1 => '25,25,25,25'
            ),
        11 => array(
                1 => '20,30,30,20'
            ),
        12 => array(
                1 => '20,20,20,20,20'
            ),
        13 => array(
                1 => '100',
                2 => '25,50,25'
            ),
        14 => array(
                1 => '100',
                2 => '33,67',
                'order' => 7
            ),
        15 => array(
                1 => '100',
                2 => '67,33'
            ),
        16 => array(
                1 => '100',
                2 => '50,50'
            ),
        17 => array(
                1 => '100',
                2 => '33,33,33',
                'order' => 8
            ),
        18 => array(
                1 => '100',
                2 => '50,50',
                3 => '100'
            ),
        19 => array(
                1 => '100',
                2 => '33,33,33',
                3 => '100',
                'order' => 9
            ),
        20 => array(
                1 => '100',
                2 => '25,50,25',
                3 => '100'
            ),
        21 => array(
                1 => '100',
                2 => '50,50',
                3 => '33,33,33',
                'order' => 10
            ),
    );

    public static $maxlayoutrows = 20;

    /**
     * For retrieving and checking numbers of columnns in any given row
     * Initialised in constructor
     * An array of objects which represent each row in view_layout_columns
     */
    public static $layoutcolumns;

    public function __construct($id=0, $data=null) {
        global $USER;
        if (is_array($id) && isset($id['urlid']) && isset($id['ownerurlid'])) {
            $tempdata = get_record_sql('
                SELECT v.*
                FROM {view} v JOIN {usr} u ON v.owner = u.id
                WHERE v.urlid = ? AND u.urlid = ?',
                array($id['urlid'], $id['ownerurlid']),
                ERROR_MULTIPLE
            );
            if (empty($tempdata)) {
                throw new ViewNotFoundException(get_string('viewnotfoundbyname', 'error', $id['urlid'], $id['ownerurlid']));
            }
        }
        else if (is_array($id) && isset($id['urlid']) && isset($id['groupurlid'])) {
            $tempdata = get_record_sql('
                SELECT v.*
                FROM {view} v JOIN {group} g ON v.group = g.id
                WHERE v.urlid = ? AND g.urlid = ? AND g.deleted = 0',
                array($id['urlid'], $id['groupurlid'])
            );
            if (empty($tempdata)) {
                throw new ViewNotFoundException(get_string('viewnotfoundbyname', 'error', $id['urlid'], $id['groupurlid']));
            }
        }
        else if (!empty($id) && is_numeric($id)) {
            $tempdata = get_record_sql('
                SELECT v.*
                FROM {view} v LEFT JOIN {group} g ON v.group = g.id
                WHERE v.id = ? AND (v.group IS NULL OR g.deleted = 0)',
                array($id)
            );
            if (empty($tempdata)) {
                throw new ViewNotFoundException(get_string('viewnotfound', 'error', $id));
            }
        }
        if (isset($tempdata)) {
            if (!empty($data)) {
                $data = array_merge((array)$tempdata, $data);
            }
            else {
                $data = $tempdata; // use what the database has
            }
            $this->id = $tempdata->id;
        }
        else {
            $this->ctime = time();
            $this->mtime = time();
            $this->dirty = true;
        }

        $data = empty($data) ? array() : (array)$data;
        foreach ($data as $field => $value) {
            if (property_exists($this, $field)) {
                $this->{$field} = $value;
            }
        }

        if (empty(self::$layoutcolumns) && db_table_exists('view_layout_columns')) {
            self::$layoutcolumns = get_records_assoc('view_layout_columns', '', '', 'columns,id');
        }

        // Add in owner and group objects if we already happen to have them from view_search(), etc.
        if (isset($data['user']) && isset($data['user']->id) && $data['user']->id == $this->owner) {
            $this->ownerobj = $data['user'];
        }
        else if (isset($data['groupdata']->id) && $data['groupdata']->id == $this->group) {
            $this->groupobj = $data['groupdata'];
        }
        else if (!isset($data['user']) && !empty($this->owner) && $this->owner == $USER->get('id')) {
            $this->ownerobj = $USER;
        }

        $this->atime = time();

        if ($this->newlayout) {
            $this->grid = array();
        }
        else {
            $this->rows = array();
            $this->columns = array();
            $this->oldcolumnsperrow = $this->get('columnsperrow');
            // set only for existing views - _create provides default value
            // Ignore if the constructor is called with deleted set to true
            if (empty($this->deleted)) {
                if ($this->columnsperrow === false || ($this->numrows > 0 && count($this->columnsperrow) != $this->numrows)) {
                    // if we are missing the info for some reason we will give the page it's layout back
                    // this can happen in MySQL when many users are copying the same page
                    if ($this->layout) {
                        if ($rowscols = get_records_sql_array("
                            SELECT vlrc.row, vlc.columns
                            FROM {view_layout_rows_columns} vlrc
                            JOIN {view_layout_columns} vlc ON vlc.id = vlrc.columns
                            WHERE viewlayout = ?", array($this->layout))) {
                                $default = array();
                                foreach ($rowscols as $row) {
                                    if ($this->get('id')) {
                                        $vrc = (object) array(
                                            'view' => $this->get('id'),
                                            'row' => $row->row,
                                            'columns' => $row->columns
                                        );
                                        ensure_record_exists('view_rows_columns', $vrc, $vrc);
                                    }
                                    $default[$row->row] = $row;
                                }
                        }
                    }
                    else if ($rowscols = get_records_sql_array("
                        SELECT vrc.row, vrc.columns
                        FROM {view} v
                        JOIN {view_rows_columns} vrc ON vrc.view = v.id
                        WHERE v.template = ?
                        AND v.type = ?", array(self::SITE_TEMPLATE, $this->type))) {
                            // Layout not specified so use the view type default layout
                            $default = array();
                            foreach ($rowscols as $row) {
                                if ($this->get('id')) {
                                    $vrc = (object) array(
                                        'view' => $this->get('id'),
                                        'row' => $row->row,
                                        'columns' => $row->columns
                                    );
                                    ensure_record_exists('view_rows_columns', $vrc, $vrc);
                                }
                                $default[$row->row] = $row;
                            }
                    }
                    $this->columnsperrow = $default;
                }
            }
        }
    }

    /**
     * Creates a new View for the given user/group/institution.
     *
     * You can specify who the view is being created _by_ with the second
     * parameter. This defaults to the current logged in user's ID.
     *
     * @param array $viewdata See View::_create
     * @return View           The newly created View
     */
    public static function create($viewdata, $userid=null) {
        if (is_null($userid)) {
            global $USER;
            $userid = $USER->get('id');
        }

        $view = self::_create($viewdata, $userid);
        return $view;
    }

    /**
     * Creates a View for the given user, based off a given template and other
     * View information supplied.
     *
     * Will set a default title of 'Copy of $viewtitle' if title is not
     * specified in $viewdata and $titlefromtemplate == false.
     *
     * @param array $viewdata See View::_create
     * @param int $templateid The ID of the View to copy
     * @param int $userid     The user who has issued the command to create the
     *                        view. See View::_create
     * @param int $checkaccess Whether to check that the user can see the view before copying it
     * @param bool $titlefromtemplate Use the default title supplied by template
     * @param array $artefactcopies The mapping between old artefact ids and new ones (created in blockinstance copy)
     * @return array A list consisting of the new view, the template view and
     *               information about the copy - i.e. how many blocks and
     *               artefacts were copied
     * @throws SystemException under various circumstances, see the source for
     *                         more information
     */
    public static function create_from_template($viewdata, $templateid, $userid=null, $checkaccess=true, $titlefromtemplate=false, &$artefactcopies) {
        if (is_null($userid)) {
            global $USER;
            $userid = $USER->get('id');
        }

        $user = new User();
        $user->find_by_id($userid);

        db_begin();

        $template = new View($templateid);

        if ($template->get('deleted')) {
            throw new SystemException("View::create_from_template: This template has been deleted");
        }

        if ($checkaccess && !$template->get('template') && !$user->can_edit_view($template)) {
            throw new SystemException("View::create_from_template: Attempting to create a View from another View that is not marked as a template");
        }
        else if ($checkaccess && !can_view_view($templateid, $userid)) {
            throw new SystemException("View::create_from_template: User $userid is not permitted to copy View $templateid");
        }

        $view = self::_create($viewdata, $userid);

        // Set a default title if one wasn't set
        if ($titlefromtemplate) {
            $view->set('title', $template->get('title'));
        }
        else if (!isset($viewdata['title'])
                && !($template->get('owner') === 0
                    && $template->get('type') == 'portfolio')) {
            $desiredtitle = $template->get('title');
            if (get_config('renamecopies')) {
                $desiredtitle = get_string('Copyof', 'mahara', $desiredtitle);
            }
            $view->set('title', self::new_title($desiredtitle, (object)$viewdata));
            $view->set('dirty', true);
        }

        $view->urlid = generate_urlid($view->title, get_config('cleanurlviewdefault'), 3, 100);
        $viewdata['owner'] = $userid;
        $view->urlid = self::new_urlid($view->urlid, (object)$viewdata);

        try {
            $copystatus = $view->copy_contents($template, $artefactcopies);
        }
        catch (QuotaExceededException $e) {
            db_rollback();
            return array(null, $template, array('quotaexceeded' => true));
        }

        // Lockblocks if set on template
        $view->set('lockblocks', $template->get('lockblocks'));

        if ($template->get('locktemplate')) {
            $view->set('locktemplate', 0);
            $view->lock_instructions_edit($template->get('id'));
        }

        $view->commit();

        $blocks = get_records_array('block_instance', 'view', $view->get('id'));
        if ($blocks) {
            foreach ($blocks as $b) {
                // As some artefact references have been changed, e.g embedded images
                // we need to rebuild the artefact list for each block
                $bi = new BlockInstance($b->id);
                $bi->rebuild_artefact_list();
                $configdata = unserialize($b->configdata);
                if (!isset($configdata['artefactid'])) {
                    continue;
                }
                if (!isset($configdata['copytype']) || $configdata['copytype'] !== 'reference') {
                    continue;
                }
                $va = new stdClass();
                $va->view = $b->view;
                $va->artefact = $configdata['artefactid'];
                $va->block = $b->id;
                insert_record('view_artefact', $va);
            }
        }

        if ($template->get('retainview') && !$template->get('institution')) {
            $obj = new stdClass();
            $obj->view  = $view->get('id');
            $obj->ctime = db_format_timestamp(time());
            $obj->usr   = $template->get('owner');
            $obj->group = $template->get('group');
            $vaid = insert_record('view_access', $obj, 'id', true);
            handle_event('updateviewaccess', array(
                'id' => $vaid,
                'eventfor' => (!empty($template->get('group')) ? 'group' : 'user'),
                'parentid' => $view->get('id'),
                'parenttype' => 'view',
                'rules' => $obj)
            );
        }

        db_commit();
        return array(
            $view,
            $template,
            $copystatus,
        );
    }

    /**
     * Creates a new View for the given user, based on the given information
     * about the view.
     *
     * Validation of the view data is performed, then the View is created. If
     * the View is to be owned by a group, that group is given access to it.
     *
     * @param array $viewdata Data about the view. You can pass in most fields
     *                        that appear in the view table.
     *
     *                        Note that you set who owns the View by setting
     *                        either the owner, group or institution field as
     *                        approriate.
     *
     *                        Currently, you cannot pass in access data. Use
     *                        $view->set_access() after retrieving the $view
     *                        object.
     *
     * @param int $userid The user who has issued the command to create the
     *                    View (note: this is different from the "owner" of the
     *                    View - a group or institution could be the "owner",
     *                    but it's a _user_ who requests a View is created for it)
     * @return View The created View
     * @throws SystemException if the View data is invalid - mostly this is due
     *                         to owner information being specified incorrectly.
     */
    private static function _create(&$viewdata, $userid) {
        // If no owner information is provided, assume that the view is being
        // created by the user for themself
        if (!isset($viewdata['owner']) && !isset($viewdata['group']) && !isset($viewdata['institution'])) {
            $viewdata['owner'] = $userid;
        }

        if (isset($viewdata['owner'])) {
            if ($viewdata['owner'] != $userid) {
                $userobj = new User();
                $userobj->find_by_id($userid);
                if (!$userobj->is_admin_for_user($viewdata['owner'])) {
                    throw new SystemException("View::_create: User $userid is not allowed to create a view for owner {$viewdata['owner']}");
                }
            }

            // Users can only have one view of each non-portfolio type
            if (isset($viewdata['type']) && $viewdata['type'] != 'portfolio' && get_record('view', 'owner', $viewdata['owner'], 'type', $viewdata['type'])) {
                $viewdata['type'] = 'portfolio';
            }
        }

        if (isset($viewdata['group'])) {
            require_once('group.php');
            if (!group_user_can_edit_views($viewdata['group'], $userid)) {
                throw new SystemException("View::_create: User $userid is not permitted to create a view for group {$viewdata['group']}");
            }
        }

        if (isset($viewdata['institution'])) {
            $user = new User();
            $user->find_by_id($userid);
            if (!$user->can_edit_institution($viewdata['institution'])) {
                throw new SystemException("View::_create: User $userid is not permitted to create a view for institution {$viewdata['institution']}");
            }
        }

        // Create the view
        $defaultdata = array(
            'numrows'       => 1,
            'template'      => 0,
            'type'          => 'portfolio',
            'title'         => (array_key_exists('title', $viewdata)) ? $viewdata['title'] : self::new_title(get_string('Untitled', 'view'), (object)$viewdata),
            'anonymise'     => 0,
            'lockblocks'    => 0,
        );

        $data = (object)array_merge($defaultdata, $viewdata);

        if ($data->type == 'portfolio' && (!isset($data->url) || is_null($data->url) || !strlen($data->url))) {
            $data->urlid = generate_urlid($data->title, get_config('cleanurlviewdefault'), 3, 100);
            $data->urlid = self::new_urlid($data->urlid, $data);
        }

        $view = new View(0, $data);
        $view->commit();

        if (isset($viewdata['group']) &&
            (empty($viewdata['type']) || (!empty($viewdata['type']) && $viewdata['type'] != 'grouphomepage'))
           ) {
            require_once('activity.php');

            // Although group views are owned by the group, the view creator is treated as owner here.
            // So we need to ignore them from the activity_occured email.
            $beforeusers[$userid] = get_record('usr', 'id', $userid);

            // By default, group views should be visible to the group
            $newaccess = (object) array(
                'view'  => $view->get('id'),
                'group' => $viewdata['group'],
                'ctime' => db_format_timestamp(time()),
            );
            $vaid = insert_record('view_access', $newaccess, 'id', true);
            handle_event('updateviewaccess', array(
                'id' => $vaid,
                'eventfor' => 'group',
                'parentid' => $view->get('id'),
                'parenttype' => 'view',
                'rules' => $newaccess)
            );
            // Notify group members
            $accessdata = new stdClass();
            $accessdata->view = $view->get('id');
            $accessdata->oldusers = $beforeusers;
            activity_occurred('viewaccess', $accessdata);
        }

        return new View($view->get('id')); // Reread to ensure defaults are set
    }

    /*
     * Returns the content we used to have in the view_layout_columns table
     * we need it to import Leap2a postfolios with old layout
     */
    public static function get_old_view_layout_columns() {
        $layout = new stdClass();
        $view_layout_columns = array();
        $id = 0;
        foreach (self::$basic_column_layouts as $column => $widths) {
            foreach ($widths as $width) {
                $id++;
                $layout = new stdClass();
                $layout->columns = $column;
                $layout->widths = $width;
                $layout->id = $id;
                $view_layout_columns[] = $layout;
            }
        }
        return $view_layout_columns;
    }


    public function get($field) {
        if (!property_exists($this, $field)) {
            throw new InvalidArgumentException("Field $field wasn't found in class " . get_class($this));
        }
        if ($field == 'tags') { // special case
            return $this->get_tags();
        }
        if ($field == 'categorydata') {
            return $this->get_category_data();
        }
        if ($field == 'collection') {
            return $this->get_collection();
        }
        if ($field == 'columnsperrow') {
            return $this->get_columnsperrow();
        }
        if ($field == 'coverimage') {
            return $this->get_coverimage();
        }
        return $this->{$field};
    }

    public function set($field, $value) {
        if (property_exists($this, $field)) {
            if ($this->{$field} != $value) {
                // only set it to dirty if it's changed
                $this->dirty = true;
            }
            $this->{$field} = $value;
            if ($field != 'atime') {
                // don't bother updating the modified time if we are
                // only wanting to update the accessed time
                $this->mtime = time();
            }
            return true;
        }
        throw new InvalidArgumentException("Field $field wasn't found in class " . get_class($this));
    }

    public function get_coverimage() {
        if ($this->coverimage && get_field('artefact', 'id', 'id', $this->coverimage)) {
            return $this->coverimage;
        }
        return null;
    }

    public function get_tags() {
        if (!isset($this->tags)) {
            $typecast = is_postgres() ? '::varchar' : '';
            $this->tags = get_column_sql("
            SELECT
                (CASE
                    WHEN t.tag LIKE 'tagid_%' THEN CONCAT(i.displayname, ': ', t2.tag)
                    ELSE t.tag
                END) AS tag
            FROM {tag} t
            LEFT JOIN {tag} t2 ON t2.id" . $typecast . " = SUBSTRING(t.tag, 7)
            LEFT JOIN {institution} i ON i.name = t2.ownerid
            WHERE t.resourcetype = ? AND t.resourceid = ?
            ORDER BY tag", array('view', $this->get('id')));
        }
        return $this->tags;
    }

    public function get_all_tags_for_view($limit = null) {
        $count = 0;
        $alltags = array();

        $artefactids = get_column_sql("
            SELECT artefact
            FROM {view_artefact}
            WHERE view = ?
            UNION
            SELECT id AS artefact
            FROM {artefact}
            WHERE parent IN (
                SELECT artefact
                FROM {view_artefact}
                WHERE view = ?)", array($this->id, $this->id));
        $blockids = get_column('block_instance', 'id', 'view', $this->id);
        $typecast = is_postgres() ? '::varchar' : '';
        $alltags = get_column_sql("
            SELECT (
                CASE
                   WHEN t.tag LIKE 'tagid_%' THEN CONCAT(i.displayname, ': ', t2.tag)
                   ELSE t.tag
                END) AS tag
            FROM {tag} t
            LEFT JOIN {tag} t2 ON t2.id" . $typecast . " = SUBSTRING(t.tag, 7)
            LEFT JOIN {institution} i ON i.name = t2.ownerid
            WHERE t.resourcetype = ? AND t.resourceid = ?
            UNION
            SELECT (
                CASE
                   WHEN t.tag LIKE 'tagid_%' THEN CONCAT(i.displayname, ': ', t2.tag)
                   ELSE t.tag
                END) AS tag
            FROM {tag} t
            LEFT JOIN {tag} t2 ON t2.id" . $typecast . " = SUBSTRING(t.tag, 7)
            LEFT JOIN {institution} i ON i.name = t2.ownerid
            WHERE t.resourcetype = ? AND t.resourceid IN ('" . join("','", $blockids) . "')
            GROUP BY 1
            UNION
            SELECT (
                CASE
                   WHEN t.tag LIKE 'tagid_%' THEN CONCAT(i.displayname, ': ', t2.tag)
                   ELSE t.tag
                END) AS tag
            FROM {tag} t
            LEFT JOIN {tag} t2 ON t2.id" . $typecast . " = SUBSTRING(t.tag, 7)
            LEFT JOIN {institution} i ON i.name = t2.ownerid
            WHERE t.resourcetype = ? AND t.resourceid IN ('" . join("','", $artefactids) . "')
            GROUP BY 1
            ORDER BY tag", array('view', $this->id, 'blocktype', 'artefact'));

        $count = sizeof($alltags);
        if ($limit && $count > $limit) {
            $alltags = array_slice($alltags, 0, $limit);
        }
        return array($count, $alltags);
    }

    public function get_collection() {
        if (!isset($this->collection)) {
            require_once(get_config('libroot') . 'collection.php');
            $this->collection = Collection::search_by_view_id($this->id);
        }
        return $this->collection;
    }

    public function get_columnsperrow() {
        if (!isset($this->columnsperrow)) {
            $this->columnsperrow = get_records_sql_assoc('SELECT "row", columns
                                                          FROM {view_rows_columns}
                                                          WHERE view = ?
                                                          ORDER BY "row" ASC', array($this->get('id')));
        }
        return $this->columnsperrow;
    }

    public function collection_id() {
        if ($collection = $this->get_collection()) {
            return $collection->get('id');
        }
        return false;
    }

    public function get_group_id_of_corresponding_group_task() {
        $collection = $this->get_collection();
        if ($collection) {
            $portfolioelement = $collection;
        }
        else {
            $portfolioelement = $this;
        }
        $portfolioelementtype = strtolower(get_class($portfolioelement));
        $portfolioelementid = $portfolioelement->get('id');

        $sql = 'SELECT * FROM {artefact} AS a '.
            'INNER JOIN {artefact_plans_task} AS gt ON gt.artefact = a.id '.
            'INNER JOIN {artefact_plans_task} AS ut ON ut.rootgrouptask = gt.artefact '.
            'WHERE ut.outcometype = ? AND ut.outcome = ?';

        $result = get_record_sql($sql, [$portfolioelementtype, $portfolioelementid]);

        if ($result && $result->group) {
            return $result->group;
        }
        return false;
    }

    /**
     * View destructor. Calls commit if necessary.
     *
     * A special case is when the object has just been deleted.  In this case,
     * we do nothing.
     */
    public function __destruct() {
        if ($this->deleted) {
            return;
        }

        if (!empty($this->dirty)) {
            return $this->commit();
        }
    }

    /**
     * This method updates the contents of the view table only.
     */
    public function commit() {
        global $USER;

        if (empty($this->dirty)) {
            return;
        }
        $fordb = new stdClass();
        foreach (get_object_vars($this) as $k => $v) {
            $fordb->{$k} = $v;
            if (in_array($k, array('mtime', 'ctime', 'atime', 'startdate', 'stopdate', 'submittedtime')) && !empty($v)) {
                $fordb->{$k} = db_format_timestamp($v);
            }
        }

        db_begin();
        if (empty($this->id)) {
            // users are only allowed one profile view
            if (!$this->template && $this->type == 'profile' && record_exists('view', 'owner', $this->owner, 'type', 'profile')) {
                throw new SystemException(get_string('onlonlyyoneprofileviewallowed', 'error'));
            }
            $this->id = insert_record('view', $fordb, 'id', true);
            handle_event('createview', array('id' => $this->id, 'eventfor' => 'view', 'viewtype' => $this->type));
        }
        else {
            update_record('view', $fordb, 'id');
            handle_event('saveview', array('id' => $this->id, 'eventfor' => 'view', 'viewtype' => $this->type));
        }

        if (isset($this->tags)) {
            if ($this->group) {
                $ownertype = 'group';
                $ownerid = $this->group;
            }
            else if ($this->institution) {
                $ownertype = 'institution';
                $ownerid = $this->institution;
            }
            else {
                $ownertype = 'user';
                $ownerid = $this->owner;
            }
            $this->tags = check_case_sensitive($this->tags, 'tag');
            delete_records('tag', 'resourcetype', 'view', 'resourceid', $this->get('id'));
            foreach (array_unique($this->get_tags()) as $tag) {
                //truncate the tag before insert it into the database
                $tag = substr($tag, 0, 128);
                $tag = check_if_institution_tag($tag);
                insert_record('tag',
                    (object)array(
                        'resourcetype' => 'view',
                        'resourceid' => $this->get('id'),
                        'ownertype' => $ownertype,
                        'ownerid' => $ownerid,
                        'tag' => $tag,
                        'ctime' => db_format_timestamp(time()),
                        'editedby' => $USER->get('id'),
                    )
                );
            }
        }

        if (isset($this->copynewgroups)) {
            delete_records('view_autocreate_grouptype', 'view', $this->get('id'));
            foreach ($this->copynewgroups as $grouptype) {
                insert_record('view_autocreate_grouptype', (object)array( 'view' => $this->get('id'), 'grouptype' => $grouptype));
            }
        }

        db_commit();

        $this->dirty = false;
        $this->deleted = false;
    }

    /**
     * Returns an array of all the artefacts on this page.
     *
     * @return array
     */
    public function get_artefact_instances() {
        $this->artefact_instances = array();

        $sql = 'SELECT a.*, i.name, i.plugin, va.block
                FROM {view_artefact} va
                JOIN {artefact} a ON va.artefact = a.id
                JOIN {artefact_installed_type} i ON a.artefacttype = i.name
                WHERE va.view = ?';
        $this->artefact_metadata = get_records_sql_array($sql, array($this->id));

        if ($instances = $this->artefact_metadata) {
            foreach ($instances as $instance) {
                safe_require('artefact', $instance->plugin);
                $classname = generate_artefact_class_name($instance->artefacttype);
                $i = new $classname($instance->id, $instance);
                $this->artefact_instances[] = $i;
            }
        }
        return $this->artefact_instances;
    }

    public function get_owner_object() {
        if (empty($this->owner)) {
            return false;
        }
        if (!isset($this->ownerobj)) {
            // $this->ownerobj = get_user_for_display($this->get('owner'));
            $user = new User();
            $user->find_by_id($this->get('owner'));
            $this->ownerobj = $user;
        }
        return $this->ownerobj;
    }

    public function get_group_object() {
        if (!isset($this->groupobj)) {
            $this->groupobj = get_group_by_id($this->get('group'), true);
        }
        return $this->groupobj;
    }

    public function get_institution_object() {
        if (!isset($this->institutionobj)) {
            $this->institutionobj = get_record('institution', 'name', $this->get('institution'));
        }
        return $this->institutionobj;
    }

    public function delete() {
        safe_require('artefact', 'comment');
        db_begin();
        ArtefactTypeComment::delete_view_comments($this->id);
        delete_records('view_access','view',$this->id);
        delete_records('view_autocreate_grouptype', 'view', $this->id);
        delete_records('tag', 'resourcetype', 'view', 'resourceid', $this->id);
        delete_records('view_visit','view',$this->id);
        delete_records('view_versioning', 'view', $this->id);
        delete_records('existingcopy', 'view', $this->id);
        delete_records('view_instructions_lock', 'view', $this->id);
        $eventdata = array('id' => $this->id, 'eventfor' => 'view');
        if ($collection = $this->get_collection()) {
            $eventdata['collection'] = $collection->get('id');
            $collection->remove_view($this->id);
        }
        delete_records('usr_watchlist_view','view',$this->id);
        //remove lock blocks, if they exist for this page
        set_field('view', 'lockblocks', 0, 'id', $this->id, 'lockblocks', 1);
        if ($blockinstanceids = get_column('block_instance', 'id', 'view', $this->id)) {
            require_once(get_config('docroot') . 'blocktype/lib.php');
            foreach ($blockinstanceids as $id) {
                $bi = new BlockInstance($id);
                $bi->delete();
            }
        }
        // Check if this view is being used as the custom landing page
        $homepageredirecturl = get_config('homepageredirecturl');
        if (get_config('homepageredirect') && !empty($homepageredirecturl)) {
            $landing = translate_landingpage_to_tags(array($homepageredirecturl));
            foreach ($landing as $land) {
                if ($land->type == 'view' && $land->typeid == $this->id) {
                    set_config('homepageredirecturl', null);
                    notify_landing_removed($land, true);
                }
            }
        }
        // Delete any submission history
        delete_records('module_assessmentreport_history', 'event', 'view', 'itemid', $this->id);

        handle_event('deleteview', $eventdata);
        delete_records('view_rows_columns', 'view', $this->id);
        if (is_plugin_active('lti', 'module')) {
            delete_records('lti_assessment_submission', 'viewid', $this->id);
        }
        delete_records('view','id',$this->id);
        if (!empty($this->owner) && $this->is_submitted()) {
            // There should be no way to delete a submitted view,
            // but unlock its artefacts just in case.
            ArtefactType::update_locked($this->owner);
        }
        require_once('embeddedimage.php');
        EmbeddedImage::delete_embedded_images('description', $this->id);
        EmbeddedImage::delete_embedded_images('instructions', $this->id);
        $this->deleted = true;
        db_commit();
    }

    /* Only retrieve access records that the owner can edit on the
     * view access page.  Some records are not visible there, such as
     * tutor access records for submitted views and objectionable
     * content access records (visible = 0) and token/secret url
     * records which are managed per-view, on another page.
     */
    public function get_access($timeformat=null) {
        if ($data = $this->get_access_records()) {
            return $this->process_access_records($data, $timeformat);
        }
        return array();
    }

    public function get_access_records() {
        $data = get_records_sql_array("
            SELECT accesstype, va.group, institution, role, usr, startdate, stopdate, allowcomments, approvecomments
            FROM {view_access} va
            WHERE view = ? AND visible = 1 AND token IS NULL
            ORDER BY
                accesstype IS NULL, accesstype DESC,
                va.group, role IS NOT NULL, role,
                institution, usr,
                startdate IS NOT NULL, startdate, stopdate IS NOT NULL, stopdate,
                allowcomments, approvecomments",
            array($this->id)
        );
        return $data ? $data : array();
    }

    public function process_access_records($data=array(), $timeformat=null) {
        $rolegroups = array();
        foreach ($data as &$item) {
            if (isset($item->group) && $item->role && !isset($roledata[$item->group])) {
                $rolegroups[$item->group] = 1;
            }
        }

        if ($rolegroups) {
            $grouptypes = get_records_sql_assoc('
                SELECT id, grouptype
                FROM {group}
                WHERE id IN (' . join(',', array_map('intval', array_keys($rolegroups))) . ')
                AND deleted = 0',
                array()
            );
        }

        foreach ($data as &$item) {
            $item = (array)$item;
            $item['locked'] = false; // Indicate if item is editable
            if ($item['usr']) {
                $item['type'] = 'user';
                $item['id'] = $item['usr'];
            }
            else if ($item['group']) {
                $item['type'] = 'group';
                $item['id'] = $item['group'];
            }
            else if ($item['institution']) {
                $item['type'] = 'institution';
                $item['id'] = $item['institution'];

                if ($this->type == 'profile') {
                    $myinstitutions = array_keys(load_user_institutions($this->owner));
                    if (in_array($item['id'], $myinstitutions) && empty($item['startdate']) && empty($item['stopdate'])) {
                        $item['locked'] = true;
                    }
                }
            }
            else {
                $item['type'] = $item['accesstype'];
                $item['id'] = null;
            }

            if ($this->type == 'profile' && $item['type'] == 'loggedin' && get_config('loggedinprofileviewaccess') && !is_isolated()) {
                $item['locked'] = true;
            }

            if ($item['role'] && isset($item['group'])) {
                $item['roledisplay'] = get_string($item['role'], 'grouptype.'.$grouptypes[$item['group']]->grouptype);
            }
            else if ($item['role']) {
                $item['roledisplay'] = get_string($item['role'], 'view');
            }
            if ($timeformat) {
                if ($item['startdate']) {
                    $item['startdate'] = strftime($timeformat, strtotime($item['startdate']));
                }
                if ($item['stopdate']) {
                    $item['stopdate'] = strftime($timeformat, strtotime($item['stopdate']));
                }
            }
        }
        return $data;
    }

    public static function update_view_access($config, $viewids) {
        global $SESSION;

        db_begin();

        // Use set_access() on the first view to get a hopefully consistent
        // and complete representation of the access list
        $firstview = new View($viewids[0]);
        $fullaccesslist = $firstview->set_access($config['accesslist'], $viewids, $config['allowcomments']);

        // Copy the first view's access records to all the other views
        $firstview->copy_access($viewids);

        // Check to see if the view is being used as a landing page url and if the access changes affect it
        $homepageredirecturl = get_config('homepageredirecturl');
        if (get_config('homepageredirect') && !empty($homepageredirecturl)) {
            $landing = translate_landingpage_to_tags(array($homepageredirecturl));
            foreach ($landing as $land) {
                if ($land->type == 'view' && in_array($land->typeid, $viewids)) {
                    $landingproblem = true;
                    foreach ($config['accesslist'] as $access) {
                        if (in_array($access['type'], array('loggedin', 'public'))) {
                            $landingproblem = false;
                        }
                    }
                    if ($landingproblem) {
                        set_config('homepageredirecturl', null);
                        notify_landing_removed($land);
                        $SESSION->add_error_msg(get_string('landingpagegone', 'admin', $land->text));
                    }
                }
            }
        }

        // Sort the full access list in the same order as the list
        // returned by get_access, so that views with the same set of
        // access records get grouped together
        usort(
            $fullaccesslist,
            static function ($a, $b) {
                if (($c = empty($a->accesstype) - empty($b->accesstype))
                    || ($c = strcmp($b->accesstype, $a->accesstype))
                    || ($c = $a->group - $b->group)
                    || ($c = !empty($a->role) - !empty($b->role))
                    || ($c = strcmp($a->role, $b->role))
                    || ($c = !empty($a->institution) - !empty($b->institution))
                    || ($c = strcmp($a->institution, $b->institution))
                    || ($c = $a->usr - $b->usr)
                    || ($c = !empty($a->startdate) - !empty($b->startdate))
                    || ($c = strcmp($a->startdate, $b->startdate))
                    || ($c = !empty($a->stopdate) - !empty($b->stopdate))
                    || ($c = strcmp($a->stopdate, $b->stopdate))
                    || ($c = $a->allowcomments - $b->allowcomments)) {
                    return $c;
                }
                return $a->approvecomments - $b->approvecomments;
            }
        );

        // Hash the config object so later on we can easily find
        // all the views with the same config/access rights
        $config['accesslist'] = $fullaccesslist;

        foreach ($viewids as $viewid) {
            $v = new View((int) $viewid);
            $v->set('startdate', $config['startdate']);
            $v->set('stopdate', $config['stopdate']);
            $v->set('template', $config['template']);
            $v->set('retainview', $config['retainview']);
            $v->set('allowcomments', $config['allowcomments']);
            $v->set('approvecomments', $config['approvecomments']);
            if (isset($config['copynewuser'])) {
                $v->set('copynewuser', $config['copynewuser']);
            }
            if (isset($config['copynewgroups'])) {
                $v->set('copynewgroups', $config['copynewgroups']);
            }
            $v->commit();
        }

        db_commit();
    }

    /**
     * Returns true if the view is currently marked as objectionable
     *
     * @param integer $reporter User id of the person who made the report
     * @param bool    $replied  Has an admin replied to the report
     *
     * @return boolean True if view is objectionable
     */
    public function is_objectionable($reporter=null, $replied=false) {
        $wheresql = "";
        if ($reporter) {
            $wheresql = " AND reportedby = ?";
        }
        else if ($replied) {
            require_once('objectionable.php');
            $wheresql = " AND status = ?";
        }

        $sql = "SELECT id FROM {objectionable}
                WHERE objecttype = ? AND objectid = ?
                AND (resolvedby IS NULL OR resolvedby <= 0)" . $wheresql . "
                UNION
                SELECT o.id FROM {objectionable} o
                JOIN {view_artefact} va ON va.artefact = o.objectid
                WHERE o.objecttype = ? AND va.view = ?
                AND (resolvedby IS NULL OR resolvedby <= 0)" . $wheresql;
        if ($reporter) {
            $params = array('view', $this->id, $reporter, 'artefact', $this->id, $reporter);
        }
        else if ($replied) {
            $params = array('view', $this->id, OBJECTIONABLE_CHANGE, 'artefact', $this->id, OBJECTIONABLE_CHANGE);
        }
        else {
            $params = array('view', $this->id, 'artefact', $this->id);
        }
        return record_exists_sql($sql, $params);
    }

    public function is_public() {
        $accessrecords = self::user_access_records($this->id, 0);
        if (!$accessrecords) {
            return false;
        }

        foreach($accessrecords as &$a) {
            if ($a->accesstype == 'public') {
                return true;
            }
        }
        return false;
    }

    /**
     * Set the view access rules
     * @param  $accessdata     array  For each view access row
                                      Can contain id, type, startdate, stopdate, allowcomments, approvecomments
     * @param  $viewids        array  Contains ids of the views getting the access rules
     * @param  $allowcomments  bool   Holding the view wide allowcomments option
                                      Needed when changing this and saving page at same time
                                      as the views are not saved at this point.
     *
     * @return  $accessdata_added  array  The added access rows
     */
    public function set_access($accessdata, $viewids = null, $allowcomments = true) {
        global $USER;
        require_once('activity.php');
        require_once('group.php');
        require_once('institution.php');

        $beforeusers = activity_get_viewaccess_users($this->get('id'));

        $select = 'view = ? AND visible = 1 AND token IS NULL';
        $beforerules = get_records_select_array('view_access', $select, array($this->id));
        if (get_config('searchplugin') == 'elasticsearch' && !empty($beforerules) && empty($accessdata) && $viewids != null) {
            // We are removing access rules and none are left so we need to let elasticsearch know
            // as it won't be picked up by the add_to_queue_access() function
            safe_require('search', 'elasticsearch');
            ElasticsearchIndexing::add_to_queue_access(null, null, $viewids);
        }
        db_begin();
        delete_records_select('view_access', $select, array($this->id));

        // View access
        $accessdata_added = array();
        if ($accessdata) {
            /*
             * There should be a cleaner way to do this
             * $accessdata_added ensures that the same access is not granted twice because the profile page
             * gets very grumpy if there are duplicate access rules
             *
             * Additional rules:
             * - Don't insert records with stopdate in the past
             * - Remove startdates that are in the past
             * - If view allows comments, access record comment permissions, don't apply, so reset them.
             * @todo: merge overlapping date ranges.
             */
            $time = time();
            foreach ($accessdata as $item) {

                if (!empty($item['stopdate']) && $item['stopdate'] < $time) {
                    continue;
                }
                if (!empty($item['startdate']) && $item['startdate'] < $time) {
                    unset($item['startdate']);
                }

                if ($allowcomments) {
                    unset($item['allowcomments']);
                    unset($item['approvecomments']);
                }

                $accessrecord = (object)array(
                    'accesstype'      => null,
                    'group'           => null,
                    'role'            => null,
                    'institution'     => null,
                    'usr'             => null,
                    'token'           => null,
                    'startdate'       => null,
                    'stopdate'        => null,
                    'allowcomments'   => 0,
                    'approvecomments' => 1,
                    'ctime'           => db_format_timestamp(time()),
                );

                switch ($item['type']) {
                case 'user':
                    $accessrecord->usr = $item['id'];
                    if (isset($item['role']) && strlen($item['role'])) {
                        $roleinfo = get_column('usr_access_roles', 'role');
                        foreach ($roleinfo as $key => $role) {
                            if ($role == $item['role']) {
                                $accessrecord->role = $item['role'];
                            }
                        }
                    }

                    break;
                case 'group':
                    $accessrecord->group = $item['id'];
                    if (isset($item['role']) && strlen($item['role'])) {
                        // Don't insert a record for a role the group doesn't have
                        $roleinfo = group_get_role_info($item['id']);
                        if (!isset($roleinfo[$item['role']])) {
                            break;
                        }
                        $accessrecord->role = $item['role'];
                    }
                    break;
                case 'institution':
                    $accessrecord->institution = $item['id'];
                    break;
                case 'friends':
                    if (!$this->owner) {
                        continue 2; // Don't add friend access to group, institution or system views
                    }
                case 'public':
                case 'loggedin':
                    $accessrecord->accesstype = $item['type'];
                }

                if (isset($item['allowcomments'])) {
                    $accessrecord->allowcomments = (int) !empty($item['allowcomments']);
                    if ($accessrecord->allowcomments) {
                        $accessrecord->approvecomments = (int) !empty($item['approvecomments']);
                    }
                }
                if (isset($item['startdate'])) {
                    $accessrecord->startdate = db_format_timestamp($item['startdate']);
                }
                if (isset($item['stopdate'])) {
                    $accessrecord->stopdate  = db_format_timestamp($item['stopdate']);
                }

                if (array_search($accessrecord, $accessdata_added) === false) {
                    $accessrecord->view = $this->get('id');
                    if (db_column_exists('view_access', 'id')) {
                        $vaid = insert_record('view_access', $accessrecord, 'id', true);
                        handle_event('updateviewaccess', array(
                            'id' => $vaid,
                            'eventfor' => $item['type'],
                            'parentid' => $accessrecord->view,
                            'parenttype' => 'view',
                            'rules' => $accessrecord)
                        );
                    }
                    else {
                        $vaid = insert_record('view_access', $accessrecord);
                    }
                    unset($accessrecord->view);
                    $accessdata_added[] = $accessrecord;
                }
            }
        }

        $data = new stdClass();
        $data->view = $this->get('id');
        $data->oldusers = $beforeusers;
        if (!empty($viewids) && sizeof($viewids) > 1) {
            $views = array();
            foreach ($viewids as $viewid) {
                $view = new View($viewid);
                $views[] = array('id' => $view->get('id'),
                                 'title' => $view->get('title'),
                                 'collection_id' => $view->get_collection() ? $view->get_collection()->get('id') : null,
                                 'collection_name' => $view->get_collection() ? $view->get_collection()->get('name') : null,
                                 'collection_url' => $view->get_collection() ? $view->get_collection()->get_url() : null,
                             );
            }
            $data->views = $views;
        }
        else if (!empty($viewids) && sizeof($viewids) == 1) {
            // dealing with a one page collection
            $view = new View($viewids[0]);
            if ($view->get_collection()) {
                $views [] = array('id' => $view->get('id'),
                                 'title' => $view->get('title'),
                                 'collection_id' => $view->get_collection() ? $view->get_collection()->get('id') : null,
                                 'collection_name' => $view->get_collection() ? $view->get_collection()->get('name') : null,
                                 'collection_url' => $view->get_collection() ? $view->get_collection()->get_url() : null,
                             );
                $data->views = $views;
            }
        }

        activity_occurred('viewaccess', $data);

        db_commit();
        return $accessdata_added;
    }

    /**
     * Apply all the access rules among a set of views to every view in
     * the set.
     */
    public static function combine_access($viewids) {
        if (empty($viewids)) {
            return;
        }

        $select = 'view IN (' . join(',', array_map('intval', $viewids)) . ') AND visible = 1';

        if (!$access = get_records_select_array('view_access', $select)) {
            return;
        }

        $unique = array();
        foreach ($access as &$a) {
            unset($a->id);
            unset($a->view);
            unset($a->ctime);
            $k = serialize($a);
            if (!isset($unique[$k])) {
                $unique[$k] = $a;
            }
        }

        db_begin();

        delete_records_select('view_access', $select);

        foreach ($unique as &$a) {
            foreach ($viewids as $id) {
                $a->view = $id;
                $a->ctime = db_format_timestamp(time());
                insert_record('view_access', $a);
            }
        }

        db_commit();
    }

    /**
     * Copy access records from one view to a set of other views
     */
    public function copy_access($to) {
        if (empty($this->id)) {
            return;
        }

        $toupdate = array();
        foreach ($to as $viewid) {
            if ($this->id != $viewid) {
                $toupdate[] = (int) $viewid;
            }
        }

        if (empty($toupdate)) {
            return;
        }

        $firstviewaccess = get_records_select_array(
            'view_access',
            'view = ? AND visible = 1 AND token IS NULL',
            array($this->id)
        );

        db_begin();
        delete_records_select(
            'view_access',
            'view IN (' . join(',', $toupdate) . ') AND visible = 1 AND token IS NULL'
        );

        if ($firstviewaccess) {
            foreach ($toupdate as $id) {
                foreach ($firstviewaccess as &$a) {
                    $a->view = $id;
                    $a->ctime = db_format_timestamp(time());
                    unset($a->id);
                    $vaid = insert_record('view_access', $a, 'id', true);
                    handle_event('updateviewaccess', array(
                        'id' => $vaid,
                        'eventfor' => self::eventfor($a),
                        'parentid' => $id,
                        'parenttype' => 'view',
                        'rules' => $a)
                    );
                }
            }
        }
        db_commit();
    }

    public static function eventfor($access) {
        // Work out what event this access is for
        if (!empty($access->token)) {
            return 'token';
        }
        if (!empty($access->institution)) {
            return 'institution';
        }
        if (!empty($access->group)) {
            return 'group';
        }
        if (!empty($access->usr)) {
            return 'user';
        }
        return $access->accesstype;
    }

    public function add_access($access) {
        if (!$this->id) {
            return false;
        }

        // Ensure view is correct
        $access->view = $this->id;
        $whereobject = clone $access;
        unset($whereobject->ctime);
        // Add ctime if needing to insert row
        if (!isset($access->ctime)) {
            $access->ctime = db_format_timestamp(time());
        }
        $vaid = ensure_record_exists('view_access', $whereobject, $access, 'id', true);
        handle_event('updateviewaccess', array(
            'id' => $vaid,
            'eventfor' => self::eventfor($access),
            'parentid' => $this->id,
            'parenttype' => 'view',
            'rules' => $access)
        );
    }

    public function add_owner_institution_access($instnames=array()) {
        if (!$this->id) {
            return false;
        }

        $institutions = empty($instnames) ? array_keys(load_user_institutions($this->owner)) : $instnames;
        if (!empty($institutions)) {
            db_begin();
            foreach ($institutions as $i) {
                $exists = record_exists_select(
                    'view_access',
                    'view = ? AND institution = ? AND startdate IS NULL AND stopdate IS NULL',
                    array($this->id, $i)
                );

                if (!$exists) {
                    $vaccess = new stdClass();
                    $vaccess->view = $this->id;
                    $vaccess->institution = $i;
                    $vaccess->startdate = null;
                    $vaccess->stopdate = null;
                    $vaccess->allowcomments = 0;
                    $vaccess->approvecomments = 1;
                    $vaccess->ctime = db_format_timestamp(time());

                    $vaid = insert_record('view_access', $vaccess, 'id', true);
                    handle_event('updateviewaccess', array(
                        'id' => $vaid,
                        'eventfor' => 'institution',
                        'parentid' => $this->id,
                        'parenttype' => 'view',
                        'rules' => $vaccess)
                    );
                }
            }
            db_commit();
        }

        return true;
    }

    public static function  get_user_access_roles() {
        $roles = get_records_array('usr_access_roles');
        $data =  array();
        foreach ($roles as $r) {
            $data[] = array('name' => $r->role, 'display' => get_string($r->role, 'view'));
        }
        return $data;
    }

    public function get_autocreate_grouptypes() {
        if (!isset($this->copynewgroups)) {
            $this->copynewgroups = get_column('view_autocreate_grouptype', 'grouptype', 'view', $this->id);
        }
        return $this->copynewgroups;
    }

    public function is_submitted() {
        return $this->get('submittedgroup') || $this->get('submittedhost');
    }

    public function submitted_to() {
        if ($group = $this->get('submittedgroup')) {
            return array('type' => 'group', 'id' => $group, 'name' => get_field('group', 'name', 'id', $group));
        }
        if ($host = $this->get('submittedhost')) {
            return array('type' => 'host', 'wwwroot' => $host, 'name' => get_field('host', 'name', 'wwwroot', $host));
        }
        return null;
    }

    public function pendingrelease($releaseuser=null, $externalid=null) {
        $submitinfo = $this->submitted_to();
        if (is_null($submitinfo)) {
            throw new ParameterException("View with id " . $this->get('id') . " has not been submitted");
        }
        db_begin();
        self::_db_pendingrelease(array($this->get('id')));
        require_once(get_config('docroot') . 'export/lib.php');
        add_submission_to_export_queue($this, $releaseuser, $externalid);
        db_commit();
    }

    public function release($releaseuser=null) {
        $submitinfo = $this->submitted_to();
        if (is_null($submitinfo)) {
            throw new ParameterException("View with id " . $this->get('id') . " has not been submitted");
        }
        $releaseuser = optional_userobj($releaseuser);

        self::_db_release(array($this->id), $this->get('owner'), $this->get('submittedgroup'));

        $ownerlang = get_user_language($this->get('owner'));
        $url = $this->get_url(false);

        handle_event('releasesubmission', array('releaseuser' => $releaseuser,
                                                'id' => $this->get('id'),
                                                'groupname' => $submitinfo['name'],
                                                'eventfor' => 'view'));

        $releaseuserid = ($releaseuser instanceof User) ? $releaseuser->get('id') : $releaseuser->id;
        if ((int)$releaseuserid !== (int)$this->get('owner')) {
            require_once('activity.php');
            activity_occurred('maharamessage',
                array(
                    'users' => array($this->get('owner')),
                    'subject' => get_string_from_language($ownerlang, 'viewreleasedsubject1', 'group', $this->get('title'),
                        $submitinfo['name'], display_name($releaseuser, $this->get_owner_object())),
                    'message' => get_string_from_language($ownerlang, 'viewreleasedmessage1', 'group', $this->get('title'),
                        $submitinfo['name'], display_name($releaseuser, $this->get_owner_object())),
                    'url' => $url,
                    'urltext' => $this->get('title'),
                )
            );
        }
    }

    public static function _db_pendingrelease(array $viewids) {
        $idstr = join(',', array_map('intval', $viewids));
        execute_sql("UPDATE {view}
                     SET submittedstatus = " . self::PENDING_RELEASE . "
                     WHERE id IN ($idstr)",
                     array()
        );
    }

    public static function _db_release(array $viewids, $owner, $group=null) {
        require_once(get_config('docroot') . 'artefact/lib.php');

        if (empty($viewids) || empty($owner)) {
            return;
        }
        $idstr = join(',', array_map('intval', $viewids));
        $owner = intval($owner);

        db_begin();
        execute_sql("
            UPDATE {view}
            SET submittedgroup = NULL,
                submittedhost = NULL,
                submittedtime = NULL,
                submittedstatus = " . self::UNSUBMITTED . "
            WHERE id IN ($idstr) AND owner = ?",
            array($owner)
        );
        if (!empty($group)) {
            // Remove hidden tutor view access records
            delete_records_select(
                'view_access',
                "view IN ($idstr) AND visible = 0 AND \"group\" = ?",
                array(intval($group))
            );
        }
        ArtefactType::update_locked($owner);
        db_commit();
    }

    /**
     * Returns HTML for the category list
     *
     * @param string $category The currently selected category
    */
    public function build_category_list($category, $new=0) {
        $categories = $this->get_category_data();
        $flag = false;
        foreach ($categories as $i => &$cat) {
            // The "shortcut" category should be treated special.
            if ($cat['name'] == 'shortcut') {
                unset($categories[$i]);
                continue;
            }
            $classes = array();
            if (!$flag) {
                $flag = true;
                $classes[] = 'first';
            }
            if ($category == $cat['name']) {
                $classes[] = 'current';
            }
            if (!empty($classes)) {
                $cat['class'] = hsc(implode(' ', $classes));
            }
        }

        // Because of the reference in the above loop, $cat refers to the last item
        $cat['class'] = (isset($cat['class'])) ? $cat['class'] . ' last' : 'last';
        $helplink = get_manual_help_link_array(array('blocktype', 'blocks'));
        $manualhelplink = $helplink['prefix'] . '/' . $helplink['language'] . '/' . $helplink['version'] . '/' .  $helplink['suffix'];
        $blocktypelist = $this->build_blocktype_list($category);
        $smarty = smarty_core();
        $smarty->assign('categories', $categories);
        $smarty->assign('selectedcategory', $category);
        $smarty->assign('blocktypelist', $blocktypelist);
        $smarty->assign('manualhelpblock', $manualhelplink);
        $smarty->assign('viewid', $this->get('id'));
        $smarty->assign('new', $new);
        return $smarty->fetch('view/blocktypecategorylist.tpl');
    }

    /**
     * Gets the name of the first blocktype category for this View.
     *
     * This can change based on what blocktypes allow themselves to be in what
     * types of View. For example, in a group View, blog blocktypes aren't
     * allowed (yet), so the first blocktype category shown won't be "blog"
     */
    public function get_default_category() {
        $data = $this->get_category_data();
        $first  = reset($data);
        return $first['name'];
    }

    /**
     * Gets information about blocktype categories for blocks that can be put
     * in this View
     *
     * For each category, returns its name, a localised title and the number of
     * blocktypes in the category that can be put in this View.
     *
     * If a category has no blocktypes that can be put in this View, it is not
     * returned
     */
    private function get_category_data() {
        if (isset($this->category_data)) {
            return $this->category_data;
        }

        require_once(get_config('docroot') . '/blocktype/lib.php');
        $categories = array();
        $sql = 'SELECT bic.*, bc.sort FROM {blocktype_installed_category} bic
            JOIN {blocktype_installed} bi ON (bic.blocktype = bi.name AND bi.active = 1)
            JOIN {blocktype_installed_viewtype} biv ON (bi.name = biv.blocktype AND biv.viewtype = ?)
            JOIN {blocktype_category} bc ON (bic.category = bc.name)
            ORDER BY bc.sort';
        if (function_exists('local_get_allowed_blocktype_categories')) {
            $localallowed = local_get_allowed_blocktype_categories($this);
        }
        $blockcategories = get_records_sql_array($sql, array($this->get('type')));
        foreach ($blockcategories as $blocktypecategory) {
            if (isset($localallowed) && is_array($localallowed) && !in_array($blocktypecategory->category, $localallowed)) {
                continue;
            }
            if (!safe_require_plugin('blocktype', $blocktypecategory->blocktype)) {
                continue;
            }
            if (call_static_method(generate_class_name("blocktype", $blocktypecategory->blocktype), "allowed_in_view", $this)) {
                if (!isset($categories[$blocktypecategory->sort])) {
                    $categories[$blocktypecategory->sort] = array(
                        'name'  => $blocktypecategory->category,
                        'title' => call_static_method("PluginBlocktype", "category_title_from_name", $blocktypecategory->category),
                        'description' => call_static_method("PluginBlocktype", "category_description_from_name", $blocktypecategory->category),
                    );
                }
            }
        }

        return $this->category_data = $categories;
    }

    /**
     * Returns HTML for the blocktype list for a particular category
     *
     * @param string $category   The category to build the blocktype list for
     * @param bool   $javascript Set to true if the caller is a json script,
     *                           meaning that nothing for the standard HTML version
     *                           alone should be output
     */
    public function build_blocktype_list($category, $javascript=false) {
        require_once(get_config('docroot') . 'blocktype/lib.php');
        $blocktypes = PluginBlockType::get_blocktypes_for_category($category, $this);
        $smarty = smarty_core();
        $smarty->assign('blocktypes', $blocktypes);
        $smarty->assign('javascript', $javascript);
        $smarty->assign('accessible', $this->get('accessibleview'));
        return $smarty->fetch('view/blocktypelist.tpl');
    }

    /**
     * Process view changes. This function is used both by the json stuff and
     * by normal posts
     */
    public function process_changes($category='', $new=0) {
        global $SESSION, $USER;

        // Security
        // TODO this might need to be moved below the requestdata check below, to prevent non owners of the view being
        // rejected
        if (!$USER->can_edit_view($this)) {
            throw new AccessDeniedException(get_string('canteditdontown', 'view'));
        }

        if (!count($_POST) && count($_GET) < 3) {
            return;
        }

        $action = '';
        foreach ($_POST as $key => $value) {
            if (substr($key, 0, 7) == 'action_') {
                $action = substr($key, 7);
                break;
            }
        }

        $viewtheme = param_variable('viewtheme', '');
        if ($viewtheme && $viewtheme != $this->get('theme')) {
            $action = 'changetheme';
            $values = array('theme' => $viewtheme);
        }

        if (empty($action)) {
            return;
        }

        form_validate(param_alphanum('sesskey', null));

        if (!isset($values)) {
            $actionstring = $action;
            $action = substr($action, 0, strpos($action, '_'));
            $actionstring  = substr($actionstring, strlen($action) + 1);

            // Actions from <input type="image"> buttons send an _x and _y
            if (substr($actionstring, -2) == '_x' || substr($actionstring, -2) == '_y') {
                $actionstring = substr($actionstring, 0, -2);
            }
            $values = self::get_values_for_action($actionstring);
        }

        $result = null;
        switch ($action) {
            // the view class method is the same as the action,
            // but I've left these here in case any additional
            // parameter handling has to be done.
            case 'addblocktype': // requires action_addblocktype  (blocktype in separate parameter)
                $values['blocktype'] = param_alpha('blocktype', null);
            break;
            case 'removeblockinstance': // requires action_removeblockinstance_id_\d
            break;
            case 'changeblockinstance': // requires action_changeblockinstance_id_\d_new_\d_blocktype_\s_title_\s
            case 'revertblockinstance': // requires action_revertblockinstance_id_\d_title_\s
            case 'configureblockinstance': // requires action_configureblockinstance_id_\d_column_\d_order_\d
            case 'acsearch': // requires action_acsearch_id_\d
            case 'moveblockinstance': // requires action_moveblockinstance_id_\d_row_\d_column_\d_order_\d
            case 'changetheme':
            break;
            default:
                throw new InvalidArgumentException(get_string('noviewcontrolaction', 'error', $action));
        }

        $message = '';
        $success = false;
        try {
            $values['returndata'] = defined('JSON');
            $returndata = $this->$action($values);

            // Tell the watchlist that the view changed
            $data = (object)array(
                'view' => $this->get('id'),
            );

            //is json ever not defined? When would this be?
            if (!defined('JSON')) {
                $message = get_string('success.' . $action, 'view');
                log_debug("message: " . $message);
            }
            $success = true;
        }
        catch (Exception $e) {
            // if we're in ajax land, just throw it
            // the handler will deal with the message.
            if (defined('JSON')) {
                throw $e;
            }
            $message = get_string('err.' . $action, 'view');
        }
        //and what about here?
        if (!defined('JSON')) {
            // set stuff in the session and redirect
            $fun = 'add_ok_msg';
            if (!$success) {
                $fun = 'add_error_msg';
            }
            $SESSION->{$fun}($message);
            redirect('/view/blocks.php?id=' . $this->get('id') . '&c=' . $category . '&new=' . $new);
        }
        return array('message' => $message, 'data' => $returndata);
    }

    /**
     * Parses the string and returns a hash of values
     *
     * @param string $action expects format name_value_name_value
     *                       where values are all numeric
     * @return array associative
    */
    private static function get_values_for_action($action) {
        $values = array();
        $bits = explode('_', $action);
        if ((count($bits) % 2) == 1) {
            throw new ParamOutOfRangeException(get_string('invalidviewaction', 'error', $action));
        }
        $lastkey = null;
        foreach ($bits as $index => $bit) {
            if ($index % 2 == 0) {
                $lastkey = $bit;
            }
            else {
                $values[$lastkey] = $bit;
            }
        }
        return $values;
    }

    /**
    * builds up the data structure for  this view
    * @param boolean $force force a re-read from the database
    *                       use this if a column is dirty
    * @private
    * @return void
    */
    private function build_column_datastructure($row, $force=false) {
        if (!empty($this->columns[$row]) && empty($force)) { // we've already built it up
            return;
        }

        $sql = 'SELECT bi.*
            FROM {block_instance} bi
            WHERE bi.view = ?
            AND bi.row = ?
            ORDER BY bi.column, bi.order';
        if (!$data = get_records_sql_array($sql, array($this->get('id'), $row))) {
            $data = array();
        }

        // fill up empty columns array keys
        $columnsperrow = $this->get('columnsperrow');
        $numcolumnsthisrow = $columnsperrow[$row]->columns;

        for ($i = 1; $i <= $numcolumnsthisrow; $i++) {
            $this->columns[$row][$i] = array('blockinstances' => array());
        }

        // Set column widths
        // This often returns the default layout for number of rows, as layout is often null at this point
        $layout = $this->get_layout();
        $i = 0;
        foreach (explode(',', $layout->rows[$row]['widths']) as $width) {
            $this->columns[$row][++$i]['width'] = $width;
        }
        foreach ($data as $block) {
            require_once(get_config('docroot') . 'blocktype/lib.php');
            $block->view_obj = $this;
            $b = new BlockInstance($block->id, (array)$block);
            $this->columns[$row][$block->column]['blockinstances'][] = $b;
        }

    }

    /*
     * Returns an array of blockinstances only, not rendering them for viewing or editing
     */
    public function get_blocks_datastructure() {
      $sql = '
            SELECT bi.id, bi.view, bi.row, bi.column, bi.order,
            positionx, positiony, width, height, blocktype, title, configdata
            FROM {block_instance_dimension} bd
            INNER JOIN {block_instance} bi
            ON bd.block = bi.id
            WHERE bi.view = ?
            ORDER BY positiony, positionx';
        $blocks = get_records_sql_array($sql, array($this->get('id')));
        $grid = array();
        if (is_array($blocks) || is_object($blocks)) {
            foreach ($blocks as $block) {
                require_once(get_config('docroot') . 'blocktype/lib.php');
                $block = (object)$block;
                $block->view = $this->get('id');
                $block->view_obj = $this;
                $blockid = $block->id;

                $b = new BlockInstance($blockid, (array)$block);

                $b->set('positionx', $block->positionx);
                $b->set('positiony', $block->positiony);
                $b->set('width', $block->width);
                $b->set('height', $block->height);

                $grid[]=$b;
            }
        }
        return $grid;
    }

    /**
    * Gets the view blocks in an array to be easily loaded in js gridstack
    * @param boolean $editing    whether we are in the edit more or not
    */
    public function get_blocks($editing=false, $exporting=false, $versioning=false) {
        if (!$versioning) {
            $sql = '
            SELECT bi.id, bi.view, bi.row, bi.column, bi.order,
            positionx, positiony, width, height, blocktype, title, configdata
            FROM {block_instance_dimension} bd
            INNER JOIN {block_instance} bi
            ON bd.block = bi.id
            WHERE bi.view = ?
            ORDER BY positiony, positionx';
            $blocks = get_records_sql_array($sql, array($this->get('id')));
        }
        else {
            $blocks = $versioning->blocks;
        }
        $this->grid = array();
        if (is_array($blocks) || is_object($blocks)) {
            foreach ($blocks as $block) {
                require_once(get_config('docroot') . 'blocktype/lib.php');
                $block = (object)$block;
                $block->view = $this->get('id');
                $block->view_obj = $this;
                if (!$versioning) {
                    $blockid = $block->id;
                }
                else {
                    $blockid = $block->originalblockid;
                }
                $b = new BlockInstance($blockid, (array)$block);
                if (isset($versioning->newlayout)) {
                    $b->set('positionx', $block->positionx);
                    $b->set('positiony', $block->positiony);
                    $b->set('width', $block->width);
                    // when editing there's extr height for the rezise handler
                    // when displaying the view this is not needed
                    $b->set('height', ($editing ? $block->height : $block->height-2));
                    $b->set('configdata', (array)$block->configdata);
                }
                else {
                    $b->set('row', $block->row);
                    $b->set('column', $block->column);
                    $b->set('order', $block->order);
                }
                $this->grid[]=$b;
            }
        }

        $blockcontent = array();
        foreach($this->grid as $blockinstance) {
            $block = array();
            if ($editing) {
                $result = $blockinstance->render_editing();
                $result = $result['html'];
                $configdata = $blockinstance->get('configdata');
                $block['draft'] = (isset($configdata['draft']) ? $configdata['draft'] : 0);
            }
            else {
                $result = $blockinstance->render_viewing($exporting, $versioning);
            }
            // check if the height needs to be defined when loading the block
            // this will happen when the block content in edit mode is different from
            // the block content in view mode
            $classname = generate_class_name('blocktype', $blockinstance->get('blocktype'));
            if (call_static_method($classname, 'set_block_height_on_load', $blockinstance)) {
                $block['height'] = 1;
            }
            else {
                $block['height'] = $blockinstance->get('height');
            }

            $block['content'] = $result;
            $block['width'] = $blockinstance->get('width');
            $block['positionx'] = $blockinstance->get('positionx');
            $block['positiony'] = $blockinstance->get('positiony');
            $block['row'] = $blockinstance->get('row');
            $block['column'] = $blockinstance->get('column');
            $block['order'] = $blockinstance->get('order');
            $block['id'] = $blockinstance->get('id');
            $blockcontent[] = $block;
        }
        return $blockcontent;
    }

    /*
    *
    * wrapper around get_column_datastructure
    * returns all rows
    * @return mixed array
    */
    public function get_row_datastructure() {
        $rowdata = array();
        // make sure we've already built up the structure
        for ($i = 1; $i <= $this->numrows; $i++) {
            $this->build_column_datastructure($i);
            $rowdata[$i] = $this->columns[$i];
        }
        return $rowdata;
    }

    /*
    *
    * @param int $column optional, defaults to returning all columns
    * @return mixed array
    */
    public function get_column_datastructure($row=1, $column=0) {

        $this->build_column_datastructure($row);

        if (empty($column)) {
            return $this->columns[$row];
        }

        if (!array_key_exists($column, $this->columns[$row])) {
            throw new ParamOutOfRangeException(get_string('invalidcolumn', 'view', $column));
        }


        return $this->columns[$row][$column];
    }

    // ******** functions to do with the view creation ui ************** //

    /**
     * Build_rows - for each row build_columms
     * @param boolean $editing    whether we are in the edit more or not
     * @param boolean $exporting  whether we are in the process of an export
     * @param boolean $versioning Whether we are in the process of view version
     * Returns the HTML for the rows of this view
     */
    public function build_rows($editing=false, $exporting=false, $versioning=false) {
        $numrows = $this->get('numrows');

        $result = '';

        for ($i = 1; $i <= $numrows; $i++) {
            $result .= $this->build_columns($i, $editing, $exporting, $versioning);
        }
        return $result;
    }

    /**
    * Checks if the view is using the new layout
    * A view uses the new layout if has data on the new layout tables
    * or if doesn't have any blocks
    */
    public function uses_new_layout() {
        $viewid = $this->get('id');

        $sql = "SELECT DISTINCT view FROM {block_instance} bi
            INNER JOIN {block_instance_dimension} bd
            ON bi.id = bd.block
            WHERE bi.view = ?";

        $usesnewlayout = get_field_sql($sql, array($viewid));

        $sql = "SELECT DISTINCT view
            FROM {block_instance}
            WHERE view = ? ";
        $hasblocks = get_field_sql($sql, array($viewid));
        return ($usesnewlayout || !$hasblocks);
    }

    /*
     * Checks if the block dimension heights of the page are set to default = 1
     * and they need to be reset when loading the page
     * This can happen when we copy a view that has an old layout
     */
    function needs_block_resize_on_load() {
        $viewid = $this->get('id');
        $sql = "SELECT * FROM {block_instance} bi
                JOIN {block_instance_dimension} bd
                ON bi.id = bd.block
                WHERE bi.view = ?";
        $hasblocks = record_exists_sql($sql, array($viewid));

        $sql = "SELECT * FROM {block_instance} bi
                JOIN {block_instance_dimension} bd
                ON bi.id = bd.block
                WHERE bd.height > 1 AND bi.view = ?";
        $blockshavedefaultheights = !record_exists_sql($sql, array($viewid));

        return ($hasblocks && $blockshavedefaultheights);
    }

    /**
     * Returns the HTML for the columns of this view
     */
    public function build_columns($row, $editing=false, $exporting=false, $versioning=false) {
        global $USER;
        $columnsperrow = $this->get('columnsperrow');
        $currentrownumcols = $columnsperrow[$row]->columns;

        $result = '';
        for ($i = 1; $i <= $currentrownumcols; $i++) {
            $result .= $this->build_column($row, $i, $editing, $exporting, $versioning);
        }

        $smarty = smarty_core();
        $smarty->assign('javascript',  defined('JSON'));
        $smarty->assign('row',         $row);
        $smarty->assign('numcolumns',  $currentrownumcols);
        $smarty->assign('rowcontent',  $result);

        if ($editing) {
            // TODO look into this - necessary?
            return $smarty->fetch('view/rowediting.tpl');
        }
        return $smarty->fetch('view/rowviewing.tpl');
    }

    /**
     * Returns the HTML for a particular column
     *
     * @param int $column   The column to build
     * @param boolean $editing    Whether the view is being built in edit mode
     * @param boolean $exporting  Whether the view is being built for export
     * @param boolean $versioning Whether the view is being built for versioning
     */
    public function build_column($row, $column, $editing=false, $exporting=false, $versioning=false) {
        global $USER;
        $data = $this->get_column_datastructure($row, $column);
        static $installed = array();
        if (empty($installed)) {
            $installed = plugins_installed('blocktype');
            $installed = array_map(function($a) { return $a->name; }, $installed);
        }

        $blockcontent = '';
        foreach($data['blockinstances'] as $blockinstance) {
            if (!in_array($blockinstance->get('blocktype'), $installed)) {
                continue; // this plugin has been disabled
            }
            if ($blockinstance->get('blocktype') == 'myfriends' && get_config('friendsnotallowed')) {
                continue; // if 'friendsnotallowed' then skip 'myfriends' block
            }
            if ($editing) {
                $result = $blockinstance->render_editing();
                $blockcontent .= $result['html'];
                // NOTE: build_column is always called in the context of column
                // operations, so the javascript returned, which is currently
                // for configuring block instances only, is not necessary
            }
            else {
                $result = $blockinstance->render_viewing($exporting, $versioning);
                $blockcontent .= $result;
            }
        }

        $columnsperrow = $this->get('columnsperrow');
        $thisrownumcolumns = $columnsperrow[$row]->columns;

        $smarty = smarty_core();
        $smarty->assign('javascript',  defined('JSON'));
        $smarty->assign('column',      $column);
        $smarty->assign('row',         $row);
        $smarty->assign('numcolumns',  $thisrownumcolumns);
        $smarty->assign('blockcontent', $blockcontent);

        if (isset($data['width'])) {
            $smarty->assign('width', $data['width']);
        }
        if (isset($data['positionx'])) {
            $smarty->assign('positionx', $data['positionx']);
        }
        if (isset($data['height'])) {
            $smarty->assign('height', $data['height']);
        }
        if (isset($data['positiony'])) {
            $smarty->assign('positiony', $data['positiony']);
        }

        if ($editing) {
            return $smarty->fetch('view/columnediting.tpl');
        }
        return $smarty->fetch('view/columnviewing.tpl');
    }

    /**
     * adds a block with the given type to a view
     *
     * @param array $values parameters for this function
     *                      blocktype  => string name of blocktype to add
     *                      column     => int column to add to
     *                      order      => position in column
     *                      returndata => return the rendered HTML for the block, or
     *                                    the id of the block if 'returndata' = 'id'
     *
     */
    public function addblocktype($values) {
        $requires = array('blocktype');
        foreach ($requires as $require) {
            if (!array_key_exists($require, $values) || empty($values[$require])) {
                throw new ParamOutOfRangeException(get_string('missingparam'. $require, 'error'));
            }
        }

        safe_require('blocktype', $values['blocktype']);
        if (!call_static_method(generate_class_name('blocktype', $values['blocktype']), 'allowed_in_view', $this)) {
            throw new UserException(get_string('cannotputblocktypeintoview', error, $values['blocktype']));
        }

        if (call_static_method(generate_class_name('blocktype', $values['blocktype']), 'single_only', $this)) {
            $count = count_records_select('block_instance', '"view" = ? AND blocktype = ?',
                                          array($this->id, $values['blocktype']));
            if ($count > 0) {
                $blocktitle = call_static_method(generate_class_name('blocktype', $values['blocktype']), 'get_title', $this);
                throw new UserException(get_string('onlyoneblocktypeperview', 'error', $blocktitle));
            }
        }

        $blocktypeclass = generate_class_name('blocktype', $values['blocktype']);
        $newtitle = method_exists($blocktypeclass, 'get_instance_title') ? '' : call_static_method($blocktypeclass, 'get_title');

        $bi = new BlockInstance(0,
            array(
                'blocktype'  => $values['blocktype'],
                'title'      => $newtitle,
                'view'       => $this->get('id'),
                'view_obj'   => $this,
                'row'        => (isset($values['row']) ? $values['row'] : 0),
                'column'     => (isset($values['column']) ? $values['column'] : 0),
                'order'      => (isset($values['order']) ? $values['order'] : 0),
                'positionx'  => $values['positionx'],
                'positiony'  => $values['positiony'],
                'width'      => $values['width'],
                'height'     => $values['height'],
            )
        );
        $bi->commit();

        if ($values['returndata'] === 'id') {
            return $bi->get('id');
        }
        else if ($values['returndata']) {
            // Return new block rendered in both configure mode and (editing) display mode

            $display = $bi->render_editing(false, true);

            $smarty = smarty_core();
            $smarty->assign('blockcontent', $display['html']);
            $smarty->assign('id', $bi->get('id'));
            $smarty->assign('width', $bi->get('width'));
            $smarty->assign('height', (empty($newtitle) ? $bi->get('height')-2 : $bi->get('height')));
            $smarty->assign('positionx', $bi->get('positionx'));
            $smarty->assign('positiony', $bi->get('positiony'));
            $display['html'] = $smarty->fetch('view/gridcell.tpl');

            $result = array(
                'display' => $display,
            );
            if (call_static_method(generate_class_name('blocktype', $values['blocktype']), 'has_instance_config')) {
                $result['configure'] = $bi->render_editing(true, true);
            }
            return $result;
        }
    }

    /**
     * adds a block instance to a view
     * @param array $values parameters for this function
     *                      block     => block to add
     */
    public function addblockinstance(BlockInstance $bi) {
        if ($this->uses_new_layout()) {
            if (!$bi->get('row')) {
                $bi->set('row', 1);
            }
            if (!$bi->get('column')) {
                $bi->set('column', 1);
            }
            if (!$bi->get('order')) {
                $bi->set('order', 1);
            }
        }
        if (!$bi->get('view')) {
            $bi->set('view', $this->get('id'));
        }

        $bi->commit();
    }

    /**
     * deletes a block instance from the view
     *
     * @param array $values parameters for this function
     *                      id => int id of blockinstance to remove
     */
    public function removeblockinstance($values) {
        if (!array_key_exists('id', $values) || empty($values['id'])) {
            throw new ParamOutOfRangeException(get_string('missingparamid', 'error'));
        }
        require_once(get_config('docroot') . 'blocktype/lib.php');
        $bi = new BlockInstance($values['id']); // get it so we can reshuffle stuff
        // Check if the block_instance belongs to this view
        if ($bi->get('view') != $this->get('id')) {
            throw new AccessDeniedException(get_string('blocknotinview', 'view', $bi->get('id')));
        }
        db_begin();
        $bi->delete();
        db_commit();
    }

    /**
     * Changes a placeholder block into the new type of block
     */
    public function changeblockinstance($values) {
        $currentblock = get_record('block_instance', 'id', $values['id']); // get direct from db as we want to change it
        $requires = array('blocktype');
        foreach ($requires as $require) {
            if (!array_key_exists($require, $values) || empty($values[$require])) {
                throw new ParamOutOfRangeException(get_string('missingparam'. $require, 'error'));
            }
        }

        safe_require('blocktype', $values['blocktype']);
        if (!call_static_method(generate_class_name('blocktype', $values['blocktype']), 'allowed_in_view', $this)) {
            throw new UserException(get_string('cannotputblocktypeintoview', error, $values['blocktype']));
        }

        if (call_static_method(generate_class_name('blocktype', $values['blocktype']), 'single_only', $this)) {
            $count = count_records_select('block_instance', '"view" = ? AND blocktype = ?',
                                          array($this->id, $values['blocktype']));
            if ($count > 0) {
                $blocktitle = call_static_method(generate_class_name('blocktype', $values['blocktype']), 'get_title', $this);
                throw new UserException(get_string('onlyoneblocktypeperview', 'error', $blocktitle));
            }
        }

        $blocktypeclass = generate_class_name('blocktype', $values['blocktype']);
        $newtitle = method_exists($blocktypeclass, 'get_instance_title') ? '' : call_static_method($blocktypeclass, 'get_title');

        if (!empty($values['title'])) {
            $newtitle = hsc(urldecode($values['title']));
            $newtitle = preg_replace('/\%2E/', '.', $newtitle); // Deal with . in title
        }
        $currentblocktags = get_records_sql_assoc("SELECT id, tag FROM {tag} WHERE resourcetype = ? AND resourceid = ?", array('blocktype', $currentblock->id));
        // Set up a dummy block instance of new blocktype with the data we need
        // So we can get the initial display and configure form data
        $bi = new BlockInstance(0,
            array(
                'id'         => $currentblock->id,
                'blocktype'  => $values['blocktype'],
                'title'      => $newtitle,
                'view'       => $this->get('id'),
                'view_obj'   => $this,
            )
        );
        $result = array('blockid' => $currentblock->id,
                        'viewid' => $currentblock->view,
                        'newblocktype' => $values['blocktype']);
        if ($currentblocktags) {
            // We need to decide what to do with placeholder block tags
            $droptags = true;
            $cform = method_exists($blocktypeclass, 'has_instance_config') ? call_static_method($blocktypeclass, 'instance_config_form', $bi) : false;
            if ($cform) {
                foreach ($cform as $element) {
                    if ($element['type'] == 'tags') {
                        $droptags = false;
                    }
                }
            }
            if ($droptags) {
                foreach ($currentblocktags as $t) {
                    execute_sql("DELETE FROM {tag} WHERE id = ?", array($t->id));
                }
            }
        }

        $newdata = array('title' => $newtitle, 'blocktype' => $values['blocktype'], 'configdata' => serialize(array()));
        $update = update_record('block_instance', (object) $newdata, (object) array('id' => $values['id']));
        if (!$update) {
            $result['returnCode'] = 1;
            $result['message'] = get_string('blockchangederror', 'view', $values['blocktype']);
        }
        else {
            // Return new block rendered in both configure mode and (editing) display mode
            $isnew = (bool)$values['new'];
            $result['display'] = $bi->render_editing(false, $isnew);
            if (call_static_method(generate_class_name('blocktype', $values['blocktype']), 'has_instance_config')) {
                $result['configure'] = $bi->render_editing(true, $isnew);
            }
            else {
                $result['configure'] = false;
            }
            $result['returnCode'] = 0;
            $result['message'] = get_string('blockchangedsuccess', 'view', $values['blocktype']);
            $result['isnew'] = $isnew;
            $result['oldtitle'] = $currentblock->title;
        }
        return $result;
    }

    /**
     * Changes a placeholder block into the new type of block
     */
    public function revertblockinstance($values) {
        $currentblock = get_record('block_instance', 'id', $values['id']); // get direct from db as we want to change it

        safe_require('blocktype', 'placeholder');
        $oldtitle = hsc(urldecode($values['title']));
        $oldtitle = preg_replace('/\%2E/', '.', $oldtitle); // Deal with . in title
        // Set up a dummy block instance of new blocktype with the data we need
        // So we can get the initial display and configure form data
        $bi = new BlockInstance(0,
            array(
                'id'         => $currentblock->id,
                'blocktype'  => 'placeholder',
                'title'      => $oldtitle,
                'view'       => $this->get('id'),
                'view_obj'   => $this,
            )
        );
        $result = array('blockid' => $currentblock->id,
                        'viewid' => $currentblock->view,
                        'newblocktype' => 'placeholder');

        $newdata = array('title' => $oldtitle, 'blocktype' => 'placeholder', 'configdata' => serialize(array()));
        $update = update_record('block_instance', (object) $newdata, (object) array('id' => $values['id']));
        if (!$update) {
            $result['returnCode'] = 1;
            $result['message'] = get_string('blockchangedbackerror', 'view', $values['blocktype']);
        }
        else {
            // Return new block rendered in both configure mode and (editing) display mode
            $result['display'] = $bi->render_editing(false, false);
            $result['returnCode'] = 0;
            $result['message'] = get_string('blockchangedbacksuccess', 'view');
        }
        return $result;
    }

    /**
    * moves a block instance to a specified location
    *
    * @param array $values parameters for this function
    *                      id     => int of block instance to move
    *                      newx   => int x position to move to
    *                      newy   => int y position to move to
    *                      newheight  => int height of the block
    *                      newwidth   => int width of the block
    */
    public function moveblockinstance($values) {
        $requires = array('id', 'newx', 'newy', 'newheight', 'newwidth');
        foreach ($requires as $require) {
            if (!array_key_exists($require, $values)) {
                throw new ParamOutOfRangeException(get_string('missingparam' . $require, 'error'));
            }
        }
        require_once(get_config('docroot') . 'blocktype/lib.php');
        $bi = new BlockInstance($values['id']);
        // Check if the block_instance belongs to this view
        if ($bi->get('view') != $this->get('id')) {
            throw new AccessDeniedException(get_string('blocknotinview', 'view', $bi->get('id')));
        }
        $bi->set('positionx', $values['newx']);
        $bi->set('positiony', $values['newy']);
        $bi->set('width', $values['newwidth']);
        $bi->set('height', $values['newheight']);
        $bi->commit();

        //TODO: check if code down here is still needed
        // Because embedly externalvideo blocks have their original content changed
        // by the cdn.embedly.com/widgets/platform.js file to use iframe data the info
        // is lost on block move so we need to referesh the block with its original content
        $configdata = $bi->get('configdata');
        $html = null;
        if ($bi->get('blocktype') == 'externalvideo' && isset($configdata['embed']) && $configdata['embed']['service'] == 'embedly') {
            $html = PluginBlocktypeExternalvideo::render_instance($bi, true);
        }
        return array('html' => $html);
    }

    /*
     * Get the position to place a block at the bottom of the page
     */
    public function bottomfreeposition() {
        // get y of blocks at the bottom
        $sql = 'SELECT MAX("positiony") FROM {block_instance_dimension} bid
            INNER JOIN {block_instance} bi ON bi.id = bid.block
            WHERE bi.view = ?';
        if ($maxy = get_field_sql($sql, array($this->get('id')))) {
            // get max height in last row blocks
            $sql = 'SELECT MAX("height") FROM {block_instance_dimension} bid
            INNER JOIN {block_instance} bi ON bi.id = bid.block
            WHERE bi.view = ? AND bid.positiony = ?';
            $maxheight = get_field_sql($sql, array($this->get('id'), $maxy));
            return ($maxy + $maxheight);
        }
        else {
            // the view has no blocks
            return 0;
        }
    }

    /*
     * Helper function to get the blockinstances from old layout pages
     * and from new grid layout pages
     */
    private function get_blockinstances() {
        $blockinstances = array();
        if (!$this->uses_new_layout()) {
            $view_data = $this->get_row_datastructure();
            foreach ($view_data as $row_data) {
                foreach($row_data as $column) {
                    foreach($column['blockinstances'] as $blockinstance) {
                        $blockinstances[] = $blockinstance;
                    }
                }
            }
        }
        else {
            $data = $this->get_blocks_datastructure();
            foreach ($data as $blockinstance) {
                $blockinstances[] = $blockinstance;
            }
        }
        return $blockinstances;
    }

    /**
     * Returns a list of required javascript files + initialization codes, based on
     * the blockinstances present in the view.
     */
    public function get_all_blocktype_javascript() {
        global $CFG;

        $javascriptfiles = array();
        $initjavascripts = array();

        $loadajax = false;
        $blockinstances = $this->get_blockinstances();

        if (!empty($blockinstances)) {
            foreach ($blockinstances as $blockinstance) {
                $pluginname = $blockinstance->get('blocktype');
                if (!safe_require_plugin('blocktype', $pluginname)) {
                  continue;
                }
                $classname = generate_class_name('blocktype', $pluginname);
                $instancejs = call_static_method(
                  $classname,
                  'get_instance_javascript',
                  $blockinstance
                );
                foreach($instancejs as $jsfile) {
                  if (is_array($jsfile) && isset($jsfile['file'])) {
                    $javascriptfiles[] = $this->add_blocktype_path($blockinstance, $jsfile['file']);
                    if (isset($jsfile['initjs'])) {
                      $initjavascripts[] = $jsfile['initjs'];
                    }
                    if (isset($jsfile['extrafilejs']) && is_array($jsfile['extrafilejs'])) {
                      foreach ($jsfile['extrafilejs'] as $extrafilejs) {
                        $javascriptfiles[] = $this->add_blocktype_path($blockinstance, $extrafilejs);
                      }
                    }
                  }
                  else if (is_string($jsfile)) {
                    $javascriptfiles[] = $this->add_blocktype_path($blockinstance, $jsfile);;
                  }
                }
                // Check to see if we need to include the block Ajax file.
                if (!$loadajax && $CFG->ajaxifyblocks && call_static_method($classname, 'should_ajaxify')) {
                  $loadajax = true;
                }
            }
        }

        if ($loadajax) {
            $javascriptfiles[] = 'ajaxblocks';
        }

        return array(
            'jsfiles' => array_unique($javascriptfiles),
            'initjs'  => array_unique($initjavascripts)
        );
    }

    /**
     * Returns a list of toolbar code based on the blockinstances present in the view.
     */
    public function get_all_blocktype_toolbar() {
        global $CFG;

        $buttons = array();
        $toolbarhtml = array();

        $loadajax = false;
        $blockinstances = $this->get_blockinstances();
        if (!empty($blockinstances)) {
            foreach ($blockinstances as $blockinstance) {
                $pluginname = $blockinstance->get('blocktype');
                if (!safe_require_plugin('blocktype', $pluginname)) {
                  continue;
                }
                $classname = generate_class_name('blocktype', $pluginname);
                $instanceinfo = call_static_method(
                  $classname,
                  'get_instance_toolbars',
                  $blockinstance
                );
                foreach($instanceinfo as $info) {
                  if (is_array($info)) {
                    if (isset($info['buttons'])) {
                      $buttons[] = $info['buttons'];
                    }
                    if (isset($info['toolbarhtml'])) {
                      $toolbarhtml[] = $info['toolbarhtml'];
                    }
                  }
                  else if (is_string($info)) {
                    $buttons[] = $info;
                  }
                }
            }
        }

        return array(
            'buttons' => array_unique($buttons), // @TODO - make a way to add in abutton to toolbar
            'toolbarhtml' => array_unique($toolbarhtml)
        );
    }

    /**
     * Returns a list of required css files.
     */
    public function get_all_blocktype_css() {
        global $THEME;
        $cssfiles = array();
        $checkedplugins = array();

        $blockinstances = $this->get_blockinstances();
        if (!empty($blockinstances)) {
            foreach ($blockinstances as $blockinstance) {
                $pluginname = $blockinstance->get('blocktype');
                if (!empty($checkedplugins[$pluginname]) ||
                !safe_require_plugin('blocktype', $pluginname)) {
                  continue;
                }
                $artefactdir = '';
                if ($blockinstance->get('artefactplugin') != '') {
                  $artefactdir = 'artefact/' . $blockinstance->get('artefactplugin') . '/';
                }
                $hrefs = $THEME->get_url('style/style.css', true, $artefactdir . 'blocktype/' . $pluginname);
                $hrefs = array_reverse($hrefs);
                $classname = generate_class_name('blocktype', $pluginname);
                $instancecss = call_static_method(
                  $classname,
                  'get_instance_css',
                  $blockinstance
                );
                $hrefs = array_merge($hrefs, $instancecss);
                foreach ($hrefs as $href) {
                  $cssfiles[] = '<link rel="stylesheet" type="text/css" href="' . append_version_number($href) . '">';
                }
                $checkedplugins[$pluginname] = 1;
            }
        }
        return array_unique($cssfiles);
    }

    /**
     * Returns the full path of a blocktype javascript file if it is internal
     */
    private function add_blocktype_path($blockinstance, $jsfilename) {
        $pluginname = $blockinstance->get('blocktype');
        if (stripos($jsfilename, 'http://') === false && stripos($jsfilename, 'https://') === false) {
            if ($blockinstance->get('artefactplugin')) {
                $jsfilename = 'artefact/' . $blockinstance->get('artefactplugin') . '/blocktype/' .
                    $pluginname . '/' . $jsfilename;
            }
            else {
                $jsfilename = 'blocktype/' . $blockinstance->get('blocktype') . '/' . $jsfilename;
            }
        }
        return $jsfilename;
    }

    /**
     * Returns a list of required javascript files, based on
     * the blockinstances present in the view.
     */
    public function get_blocktype_javascript() {
        $javascript = array();

        $blockinstances = $this->get_blockinstances();
        if (!empty($blockinstances)) {
            foreach ($blockinstances as $blockinstance) {
                $pluginname = $blockinstance->get('blocktype');
                safe_require('blocktype', $pluginname);
                $instancejs = call_static_method(
                  generate_class_name('blocktype', $pluginname),
                  'get_instance_javascript',
                  $blockinstance
                );
                foreach($instancejs as &$jsfile) {
                  if (stripos($jsfile, 'http://') === false && stripos($jsfile, 'https://') === false) {
                    if ($artefactplugin = get_field('blocktype_installed', 'artefactplugin', 'name', $pluginname)) {
                      $jsfile = 'artefact/' . $artefactplugin . '/blocktype/' .
                      $pluginname . '/' . $jsfile;
                    }
                    else {
                      $jsfile = 'blocktype/' . $blockinstance->get('blocktype') . '/' . $jsfile;
                    }
                  }
                }
                $javascript = array_merge($javascript, $instancejs);
            }
        }

        return array_unique($javascript);
    }

    /**
     * Configures a blockinstance
     *
     * @param array $values parameters for this function
     */
    public function configureblockinstance($values) {
        require_once(get_config('docroot') . 'blocktype/lib.php');
        $bi = new BlockInstance($values['id']);
        // Check if the block_instance belongs to this view
        if ($bi->get('view') != $this->get('id')) {
            throw new AccessDeniedException(get_string('blocknotinview', 'view', $bi->get('id')));
        }
        return $bi->render_editing(true);
    }

    /**
     * returns the current max block position within a column
     */
    public function get_current_max_order($row, $column) {
        return get_field('block_instance', 'max("order")', 'column', $column, 'view', $this->get('id'), 'row', $row);
    }

    private function changetheme($values) {
        if ($theme = $values['theme']) {
            $themes = get_user_accessible_themes();
            if (isset($themes[$theme])) {
                if ($theme == 'sitedefault') {
                    $theme = null;
                }
                $this->set('theme', $theme);
                $this->commit();
            }
        }
    }

    public function set_user_theme() {
        global $THEME;
        if ($this->theme && $THEME->basename != $this->theme) {
            $THEME = new Theme($this);
        }
        return $this->theme;
    }

    /**
     * This function formats the owner's name according to their view preference
     *
     * @param bool $includelink true if the result should be wrapped in an html anchor link
     * @return string formatted name
     */
    public function formatted_owner($includelink = false) {

        if ($this->get('owner')) {
            $user = $this->get_owner_object();
            $user = get_user_for_display($user);

            switch ($this->ownerformat) {
            case FORMAT_NAME_FIRSTNAME:
                $name = $user->firstname;
                break;
            case FORMAT_NAME_LASTNAME:
                $name = $user->lastname;
                break;
            case FORMAT_NAME_FIRSTNAMELASTNAME:
                $name = $user->firstname . ' ' . $user->lastname;
                break;
            case FORMAT_NAME_PREFERREDNAME:
                $name = $user->preferredname;
                break;
            case FORMAT_NAME_STUDENTID:
                $name = $user->studentid;
                break;
            case FORMAT_NAME_DISPLAYNAME:
            default:
                $name = display_name($user);
                break;
            }
            if ($includelink) {
                return get_string('link', 'mahara', get_config('wwwroot') . 'user/view.php?id=' . $user->id, $name);
            }
            else {
                return $name;
            }
        }
        else if ($this->get('group')) {
            $group = $this->get_group_object();
            if ($includelink) {
                return get_string('link', 'mahara', get_config('wwwroot') . 'group/view.php?id=' . $group->id, $group->name);
            }
            else {
                return $group->name;
            }
        }
        else if ($i = $this->get('institution')) {
            if ($i == 'mahara') {
                return get_config('sitename');
            }
            $institution = $this->get_institution_object();
            if ($includelink) {
                return get_string('link', 'mahara', get_config('wwwroot') . 'institution/index.php?institution=' .
                        $institution->name, $institution->displayname);
            }
            else {
                return $institution->displayname;
            }
        }
        return null;
    }

    /**
     * This function returns a boolean indicating whether the current page should be anonymised.
     */
    public function is_anonymous()
    {
      return get_config('allowanonymouspages') && $this->anonymise;
    }

    /**
      * This function returns a boolean indicating whether author information should be made
      * available in an ajax link if the page is anonymised.
      */
    public function is_staff_or_admin_for_page()
    {
        global $USER;

        return (($USER->get('id') === $this->get('owner')) || $USER->is_staff_for_user($this->get_owner_object()));
    }

    /**
     * Returns a record from the view_layout table matching the layout for this View.
     *
     * If the layout for the view is null, and there is only one row,
     * then this method returns the record for the default layout for
     * the number of columns the View has.
     *
     * It's not meaningful to have a default for multi-row layouts as there are so many possible permutations. (19,530 with equal column spacing alone.)
     * Therefore we allow for an empty id value to be returned in the absence of a matching layout,
     * if there is more than one row, but provide the default widths for the columns in each row.
     * This is to cater for the dynamic adding and removing of columns via AJAX. In this case,
     * new layouts may be created which do not yet exist. Adding them to the database would confuse the
     * layout options page. In such cases the view layout is set to null in any case.
     *
     *
     * @return object containing an id from the view_layout table, and an array of rows with column numbers and column widths per row.
     */
    public function get_layout() {

        $layout = new stdClass();
        $layout->rows = array();
        $layoutid = $this->get('layout');
        $numrows = $this->get('numrows');
        $columnsperrow = $this->get('columnsperrow');
        $owner = $this->get('owner');
        $queryarray = array($owner, $numrows);
        $group = $this->get('group');
        $institution = $this->get('institution');

        if (isset($owner)) {
            $andclause = '(ucl.usr = 0 OR ucl.usr = ?)';
        }
        else if (!empty($group)) {
            $andclause = '(ucl.usr = 0 OR ucl.group = ?)';
            $queryarray = array($group, $numrows);
        }
        else if (!empty($institution)) {
            $andclause = '(ucl.usr = 0 OR ucl.institution = ?)';
            $queryarray = array($institution, $numrows);
        }
        else {
            throw new SystemException("View::get_layout: No owner, group or institution set for view.");
        }

        // get all valid possible layout records
        $validlayouts = get_records_sql_assoc('
                SELECT * FROM {view_layout} vl
                JOIN {usr_custom_layout} ucl
                ON ((vl.id = ucl.layout) AND ' . $andclause . ')
                WHERE "rows" = ?', $queryarray);

        if ($layoutid) {
            $layout->id = $layoutid;
            if ($layoutsrowscols = get_records_select_array('view_layout_rows_columns', 'viewlayout = ?', array($layoutid))) {

                foreach ($layoutsrowscols as $layoutrowcol) {
                    $layout->rows[$layoutrowcol->row]['widths'] = self::$layoutcolumns[$layoutrowcol->columns]->widths;
                    $layout->rows[$layoutrowcol->row]['columns'] = self::$layoutcolumns[$layoutrowcol->columns]->columns;
                }
            }

        }
        else if (!$layoutid) {
            // view.layout is NULL, because the user hasn't chosen a stored layout or has been altering
            // the number of columns in each row using the "add/delete columns" buttons.

            // get widths for each row, based on equal spacing of columns
            $layoutid = 0;
            $layout->id = $layoutid;
            foreach ($columnsperrow as $row) {
                $numcolumns = $row->columns;
                $widths = self::$defaultcolumnlayouts[$numcolumns];
                $layout->rows[$row->row]['widths'] = $widths;
                $layout->rows[$row->row]['columns'] = $numcolumns;
            }
        }

        return $layout;
    }

    /**
     * Exports the view configuration as a data structure. This does not
     * include access rules or ownership information - only the information
     * required to rebuild the view's layout, blocks and other such info.
     *
     * This structure can then be imported again, using {@link import_from_config()}
     *
     * @return array The configuration for this view, try calling this to see
     *               what fields are available.
     */
    public function export_config($format='') {
        $config = array(
            'title'       => $this->get('title'),
            'description' => $this->get('description'),
            'type'        => $this->get('type'),
            'tags'        => $this->get('tags'),
            'ownerformat' => $this->get('ownerformat'),
            'instructions' => $this->get('instructions'),
            'coverimage'  => $this->get('coverimage'),
        );

        if (!$this->uses_new_layout()) {
            $config['layout'] = $this->get('layout');
            $config['numrows'] =  $this->get('numrows');

            // Export view content
            $data = $this->get_row_datastructure();
            foreach ($data as $rowkey => $row) {
              foreach ($row as $colkey => $column) {
                $config['rows'][$rowkey]['columns'][$colkey] = array();
                foreach ($column['blockinstances'] as $bi) {
                  safe_require('blocktype', $bi->get('blocktype'));
                  $classname = generate_class_name('blocktype', $bi->get('blocktype'));
                  $method = 'export_blockinstance_config';
                  if (method_exists($classname, $method . "_$format")) {
                    $method .= "_$format";
                  }
                  $config['rows'][$rowkey]['columns'][$colkey][] = array(
                    'id' => $bi->get('id'),
                    'blocktype' => $bi->get('blocktype'),
                    'title'     => $bi->get('title'),
                    'config'    => call_static_method($classname, $method, $bi),
                  );
                }
              } // cols
            } // rows
        }
        else {
            $config['newlayout'] = true;

            // Export view content
            $data = $this->get_blocks_datastructure();
            $config['grid'] = array();
            foreach ($data as $bi) {
                safe_require('blocktype', $bi->get('blocktype'));
                $classname = generate_class_name('blocktype', $bi->get('blocktype'));
                $method = 'export_blockinstance_config';
                if (method_exists($classname, $method . "_$format")) {
                  $method .= "_$format";
                }
                $config['grid'][] = array(
                    'blocktype' => $bi->get('blocktype'),
                    'title'     => $bi->get('title'),
                    'positionx' => $bi->get('positionx'),
                    'positiony' => $bi->get('positiony'),
                    'height'    => $bi->get('height'),
                    'width'     => $bi->get('width'),
                    'config'    => call_static_method($classname, $method, $bi),
                );
            }
        }

        return $config;
    }

    /**
     * Returns embedded image artefact IDs in the description of given views
     *
     * @param array $viewids
     * @return array artefact IDs
     */
    public static function get_embedded_artefacts(array $viewids) {
        if (!$aids = get_column_sql("
            SELECT fileid
            FROM {artefact_file_embedded}
            WHERE resourcetype IN (?,?)
                AND resourceid IN (" . join(',', array_map('intval', $viewids)) . ')'
            , array('description', 'instructions'))) {
            return array();
        }
        return $aids;

    }
    /**
     * Given a data structure like the one created by {@link export_config},
     * creates and returns a View object representing the config.
     *
     * @param array $config The config, as generated by export_config. Note
     *                      that if you miss fields, this method will throw
     *                      warnings.
     * @param int $userid   The user who issued the command to do the import
     *                      (defaults to the logged in user)
     * @return View The created view
     */
    public static function import_from_config(array $config, $userid=null, $format='') {
        $viewdata = array(
            'title'       => $config['title'],
            'description' => $config['description'],
            'type'        => $config['type'],
            'tags'        => $config['tags'],
            'ownerformat' => $config['ownerformat'],
            'instructions' => $config['instructions'],
        );
        if (isset($config['layout'])) {
            $viewdata['layout'] = $config['layout'];
        }
        if (isset($config['numrows'])) {
            $viewdata['numrows'] = $config['numrows'];
        }

        $viewdata['newlayout'] = true;

        if (isset($config['owner'])) {
            $viewdata['owner'] = $config['owner'];
        }
        if (isset($config['group'])) {
            $viewdata['group'] = $config['group'];
        }

        if (isset($config['institution'])) {
            $viewdata['institution'] = $config['institution'];
        }
        $view = View::create($viewdata, $userid);
        if (isset($config['grid'])) {
            foreach ($config['grid'] as $blockinstance) {
                safe_require('blocktype', $blockinstance['type']);
                $classname = generate_class_name('blocktype', $blockinstance['type']);
                $method = 'import_create_blockinstance';
                if (method_exists($classname, $method . "_$format")) {
                    $method .= "_$format";
                }
                $bi = call_static_method($classname, $method, $blockinstance, $config);
                if ($bi) {
                    $bi->set('title',  $blockinstance['title']);
                    $bi->set('positionx', $blockinstance['positionx']);
                    $bi->set('positiony', $blockinstance['positiony']);
                    $bi->set('width', $blockinstance['width']);
                    $bi->set('height', $blockinstance['height']);
                    if (isset($blockinstance['row'])) {
                        // if we are importing and the layout is not a grid one,
                        // we'll need this values whn updating the heights of the blocks
                        $bi->set('row', $blockinstance['row']);
                        $bi->set('column', $blockinstance['column']);
                        $bi->set('order', $blockinstance['order']);
                    }
                    $view->addblockinstance($bi);
                }
                else {
                    log_debug("Blocktype {$blockinstance['type']}'s import_create_blockinstance did not give us a blockinstance, so not importing this block");
                }
            }
        }

        if ($viewdata['type'] == 'profile') {
            $view->set_access(array(
                array(
                    'type'      => 'loggedin',
                    'startdate' => null,
                    'stopdate'  => null,
                ),
            ));
        }

        return $view;
    }


    /**
     * Makes a URL for a view block editing page
     */
    public static function make_base_url() {
        static $allowed_keys = array('id', 'change', 'c', 'new', 'search');
        $baseurl = '?';
        foreach ($_POST + $_GET as $key => $value) {
            if (in_array($key, $allowed_keys) || preg_match('/^action_.*(_x)?$/', $key)) {
                $baseurl .= hsc($key) . '=' . hsc($value) . '&';
            }
        }
        $baseurl = substr($baseurl, 0, -1);
        return $baseurl;
    }

    /**
     * Builds data for the artefact chooser.
     *
     * This builds three pieces of information:
     *
     * - HTML containing table rows
     * - Pagination HTML and Javascript
     * - The total number of artefacts found
     * - Artefact fields to return
     */
    public static function build_artefactchooser_data($data, $group=null, $institution=null) {
        global $USER;
        // If lazyload is set, immediately return an empty resultset
        // In the case of forms using lazyload, lazyload is set to false by subsequent requests via ajax,
        // for example in views/artefactchooser.json.php, at which time the full resultset is returned.
        if (isset($data['lazyload']) && $data['lazyload']) {
            $result =  '';
            $pagination = build_pagination(array(
                'id' => $data['name'] . '_pagination',
                'class' => 'ac-pagination',
                'url' => View::make_base_url() . (param_boolean('s') ? '&s=1' : ''),
                'count' => 0,
                'limit' => 0,
                'offset' => 0,
                'datatable' => $data['name'] . '_data',
                'jsonscript' => 'view/artefactchooser.json.php',
                'firsttext' => '',
                'previoustext' => '',
                'nexttext' => '',
                'lasttext' => '',
                'numbersincludefirstlast' => false,
                'extradata' => array(
                    'value'       => $data['defaultvalue'],
                    'blocktype'   => $data['blocktype'],
                    'group'       => $group,
                    'institution' => $institution,
                ),
            ));
            return array($result, $pagination, 0, 0, array());
        }

        $data['search'] = param_variable('search', '');
        $data['offset'] -= $data['offset'] % $data['limit'];

        safe_require('blocktype', $data['blocktype']);
        $blocktypeclass = generate_class_name('blocktype', $data['blocktype']);

        $data['sortorder'] = array(array('fieldname' => 'title', 'order' => 'ASC'));
        if (method_exists($blocktypeclass, 'artefactchooser_get_sort_order')) {
            $data['sortorder'] = call_static_method($blocktypeclass, 'artefactchooser_get_sort_order');
        }

        list($artefacts, $totalartefacts) = self::get_artefactchooser_artefacts($data, $USER, $group, $institution);

        $selectone     = $data['selectone'];
        $value         = $data['defaultvalue'];
        $elementname   = $data['name'];
        $template      = $data['template'];
        $returnfields  = isset($data['returnfields']) ? $data['returnfields'] : null;

        $returnartefacts = array();
        $result = '';
        if ($artefacts) {

            if (!empty($data['ownerinfo'])) {
                require_once(get_config('docroot') . 'artefact/lib.php');
                $userid = ($group || $institution) ? null : $USER->get('id');
                if (artefact_get_owner_info(array_keys($artefacts))) {
                    foreach (artefact_get_owner_info(array_keys($artefacts)) as $k => $v) {
                        if ($artefacts[$k]->owner !== $userid
                            || $artefacts[$k]->group !== $group
                            || $artefacts[$k]->institution !== $institution) {
                            $artefacts[$k]->ownername = $v->name;
                            $artefacts[$k]->ownerurl = $v->url;
                        }
                    }
                }
            }

            foreach ($artefacts as &$artefact) {
                safe_require('artefact', get_field('artefact_installed_type', 'plugin', 'name', $artefact->artefacttype));

                if (method_exists($blocktypeclass, 'artefactchooser_get_element_data')) {
                    $artefact = call_static_method($blocktypeclass, 'artefactchooser_get_element_data', $artefact);
                }

                $artefact->blockcount = 0;
                if ($blocks = get_column('view_artefact', 'block', 'artefact', $artefact->id)) {
                    $blocks = array_unique($blocks);
                    $artefact->blockcount = count($blocks);
                }

                // Build the radio button or checkbox for the artefact
                $formcontrols = '';
                if ($selectone) {
                    $formcontrols .= '<input type="radio" class="radio" data-count="' . $artefact->blockcount . '" id="' . hsc($elementname . '_' . $artefact->id)
                        . '" name="' . hsc($elementname) . '" value="' . hsc($artefact->id) . '"';
                    if ($value == $artefact->id) {
                        $formcontrols .= ' checked="checked"';
                    }
                    $formcontrols .= '>';
                }
                else {
                    $formcontrols .= '<input type="checkbox" id="' . hsc($elementname . '_' . $artefact->id) . '" name="' . hsc($elementname) . '[' . hsc($artefact->id) . ']"';
                    if ($value && in_array($artefact->id, $value)) {
                        $formcontrols .= ' checked="checked"';
                    }
                    $formcontrols .= ' class="artefactid-checkbox checkbox">';
                    $formcontrols .= '<input type="hidden" name="' . hsc($elementname) . '_onpage[]" value="' . hsc($artefact->id) . '" class="artefactid-onpage">';
                }
                if (!empty($artefact->group)) {
                    $groupobj = get_group_by_id($artefact->group, true);
                    $artefact->groupname = $groupobj->name;
                    $artefact->groupurl = get_config('wwwroot') . 'group/view.php?id=' . $groupobj->id;
                }
                else if (!empty($artefact->institution)) {
                    $institutionobj = new Institution($artefact->institution);
                    if ($institutionobj->name == 'mahara') {
                        $artefact->institutionname = get_config('sitename');
                    }
                    else {
                        $artefact->institutionname = $institutionobj->displayname;
                    }
                    $artefact->institutionurl = get_config('wwwroot') . 'institution/index.php?institution=' . $institutionobj->name;
                }
                $smarty = smarty_core();
                $smarty->assign('artefact', $artefact);
                $smarty->assign('elementname', $elementname);
                $smarty->assign('formcontrols', $formcontrols);
                $result .= $smarty->fetch($template) . "\n";

                if ($returnfields) {
                    $returnartefacts[$artefact->id] = array();
                    foreach ($returnfields as $f) {
                        if ($f == 'safedescription') {
                            $returnartefacts[$artefact->id]['safedescription'] = clean_html($artefact->description);
                            continue;
                        }
                        if ($f == 'attachments') {
                            // Check if the artefact has attachments - we need to update the instance config form
                            // to have those attachments selected.
                            $attachment_ids = get_column('artefact_attachment','attachment', 'artefact', $artefact->id);
                            $returnartefacts[$artefact->id]['attachments'] = $attachment_ids;
                            continue;
                        }
                        $returnartefacts[$artefact->id][$f] = $artefact->$f;
                    }
                }
            }

            if ($returnfields && !empty($data['getblocks'])) {
                // Get ids of the blocks containing these artefacts
                $blocks = get_records_select_array(
                    'view_artefact',
                    'artefact IN (' . join(',', array_fill(0, count($artefacts), '?')) . ')',
                    array_keys($artefacts)
                );

                if (!empty($blocks)) {
                    // For each artefact, attach a list of block ids of all the blocks
                    // that contain it.
                    foreach ($blocks as $block) {
                        if (empty($returnartefacts[$block->artefact]['blocks'])) {
                            $returnartefacts[$block->artefact]['blocks'] = array();
                        }
                        $returnartefacts[$block->artefact]['blocks'][] = $block->block;
                    }
                }
            }
        }

        $pagination = build_pagination(array(
            'id' => $elementname . '_pagination',
            'class' => 'ac-pagination',
            'url' => View::make_base_url() . (param_boolean('s') ? '&s=1' : ''),
            'count' => $totalartefacts,
            'limit' => $data['limit'],
            'offset' => $data['offset'],
            'datatable' => $elementname . '_data',
            'jsonscript' => 'view/artefactchooser.json.php',
            'firsttext' => '',
            'previoustext' => '',
            'nexttext' => '',
            'lasttext' => '',
            'numbersincludefirstlast' => false,
            'extradata' => array(
                'value'       => $value,
                'blocktype'   => $data['blocktype'],
                'group'       => $group,
                'institution' => $institution,
            ),
        ));

        return array($result, $pagination, $totalartefacts, $data['offset'], $returnartefacts);
    }

    /**
     * Return artefacts available for inclusion in a particular block
     *
     */
    public static function get_artefactchooser_artefacts($data, $owner=null, $group=null, $institution=null, $short=false) {
        // If this is in a blocktemplate we just want to return all possible options
        if (isset($data['blocktemplate']) && !empty($data['blocktemplate'])) {
            $artefacts = array();
            $totalartefacts = count($data['artefacttypes']);
            foreach ($data['artefacttypes'] as $key => $type) {
                $a = new stdClass();
                $a->id = $key;
                $a->artefacttype = $type;
                $a->title = '';
                $a->description = null;
                $artefacts[$key] = $a;
            }
            list($customprofile, $customtotals) = self::get_custom_profiles($data['artefacttypes']);
            $artefacts = array_merge($artefacts, $customprofile);
            $totalartefacts += $customtotals;
            return array($artefacts, $totalartefacts);
        }

        if ($owner === null) {
            global $USER;
            $user = $USER;
        }
        else if ($owner instanceof User) {
            $user = $owner;
        }
        else if (intval($owner) != 0) {
            $user = new User();
            $user->find_by_id(intval($owner));
        }
        else {
            throw new SystemException("Invalid argument type " . gettype($owner) . " passed to View::get_artefactchooser_artefacts");
        }

        $offset        = !empty($data['offset']) ? $data['offset'] : null;
        $limit         = !empty($data['limit']) ? $data['limit'] : null;

        $sortorder = '';
        if (!empty($data['sortorder'])) {
            foreach ($data['sortorder'] as $field) {
                if (!preg_match('/^[a-zA-Z_0-9"]+$/', $field['fieldname'])) {
                    continue; // skip this item (it fails validation)
                }

                $order = 'ASC';
                if (!empty($field['order']) && ('DESC' == strtoupper($field['order']))) {
                    $order = 'DESC';
                }

                if (empty($sortorder)) {
                    $sortorder .= ' ORDER BY ';
                }
                else {
                    $sortorder .= ', ';
                }
                $fieldname = 'a.' . $field['fieldname'];
                if (!empty($field['fieldvalue'])) {
                    $fieldname .= " = '" . $field['fieldvalue'] . "'";
                }
                $sortorder .= $fieldname . ' ' . $order;
            }
        }

        $extraselect = '';
        if (isset($data['extraselect'])) {
            foreach ($data['extraselect'] as $field) {
                if (!preg_match('/^[a-zA-Z_0-9"]+$/', $field['fieldname'])) {
                    continue; // skip this item (it fails validation)
                }

                // Sanitise all values
                $values = $field['values'];
                foreach ($values as &$val) {
                    if ($field['type'] == 'int') {
                        $val = (int)$val;
                    }
                    elseif ($field['type'] == 'string') {
                        $val = db_quote($val);
                    }
                    else {
                        throw new SystemException("Unsupported field type '" . $field['type'] . "' passed to View::get_artefactchooser_artefacts");
                    }
                }

                $extraselect .= ' AND ';

                if (count($values) > 1) {
                    $extraselect .= 'a.' . $field['fieldname'] . ' IN (' . implode(', ', $values) . ')';
                }
                else {
                    $extraselect .= 'a.' . $field['fieldname'] . ' = ' . reset($values);
                }
            }
        }
        $blogrelated = isset($data['blocktype']) &&
            ($data['blocktype'] == 'blog' || $data['blocktype'] == 'blogpost'
             || $data['blocktype'] == 'recentposts');

        $from = ' FROM {artefact} a ';
        // To also check tags
        $typecast = is_postgres() ? '::varchar' : '';
        $from .= " LEFT JOIN {tag} t ON t.resourcetype = 'artefact' AND a.id" . $typecast . " = t.resourceid ";

        if ($group) {
            // Get group-owned artefacts that the user has view
            // permission on, and site-owned artefacts.
            // For blogs, blogposts and recentposts
            // group owned and personal artefacts
            $from .= '
            LEFT OUTER JOIN (
                SELECT
                    r.artefact, r.can_view, r.can_edit, m.group
                FROM
                    {group_member} m
                    JOIN {artefact} aa ON aa.group = m.group
                    JOIN {artefact_access_role} r ON aa.id = r.artefact AND r.role = m.role
                WHERE
                    m.group = ?
                    AND m.member = ?
                    AND r.can_view = 1
            ) ga ON (ga.group = a.group AND a.id = ga.artefact)';

            if ($blogrelated) {
                $select = "(ga.can_view = 1";
            }
            else {
                $select = "(a.institution = 'mahara' OR ga.can_view = 1";
            }

            if (is_string($group)) {
                $ph = array((int)$group, $user->get('id'));
            }
            else {
                $class = get_class($group);
                switch($class) {
                    case 'stdClass':
                          $ph = array((int)$group->id, $user->get('id'));
                        break;
                    case 'Array':
                        $ph = array((int)$group['id'], $user->get('id'));
                        break;
                }
            }

            if (!empty($data['userartefactsallowed']) || $blogrelated) {
                $select .= ' OR a.owner = ?';
                $ph[] = $user->get('id');
            }
            $select .= ')';
        }
        else if ($institution) {
          if ($blogrelated) {
                $select = "(a.institution = ?)";
            }
            else {
                // Site artefacts & artefacts owned by this institution
                $select = "(a.institution = 'mahara' OR a.institution = ?)";
            }
            $ph = array($institution);
        }
        else { // The view is owned by a normal user
            // Get artefacts owned by the user, group-owned artefacts
            // the user has republish permission on, artefacts owned
            // by the user's institutions.
            safe_require('artefact', 'file');

            $public = (int) ArtefactTypeFolder::admin_public_folder_id();
            $select = ' a.id IN (
                    SELECT id
                    FROM {artefact}
                        WHERE owner = ?
                    UNION
                    SELECT id
                    FROM {artefact}
                        WHERE (path = ? OR path LIKE ?) AND institution = \'mahara\'
                    UNION
                    SELECT aar.artefact
                    FROM {group_member} m
                        JOIN {artefact} aa ON m.group = aa.group
                        JOIN {artefact_access_role} aar ON aar.role = m.role AND aar.artefact = aa.id
                    WHERE m.member = ? AND aar.can_republish = 1 AND artefacttype NOT IN (\'blog\', \'blogpost\', \'recentposts\')
                    UNION
                    SELECT artefact FROM {artefact_access_usr} WHERE usr = ? AND can_republish = 1';

            $ph = array($user->get('id'), "/$public", db_like_escape("/$public/") . '%', $user->get('id'), $user->get('id'));

            $institutions = array_keys($user->get('institutions'));

            if ($user->get('admin')) {
                $institutions[] = 'mahara';
            }

            if ($institutions && !$blogrelated) {
                $select .= '
                    UNION
                    SELECT id FROM {artefact} WHERE institution IN (' . join(',', array_fill(0, count($institutions), '?')) . ')';
                $ph = array_merge($ph, $institutions);
            }

            $select .= "
                )";
        }

        if (!empty($data['artefacttypes']) && is_array($data['artefacttypes'])) {
            $select .= ' AND artefacttype IN(' . join(',', array_fill(0, count($data['artefacttypes']), '?')) . ')';
            $ph = array_merge($ph, $data['artefacttypes']);
        }

        if (!empty($data['search'])) {
            $search = db_quote('%' . str_replace('%', '%%', $data['search']) . '%');
            $select .= 'AND (title ' . db_ilike() . '(' . $search . ')
                             OR description ' . db_ilike() . '(' . $search . ')
                             OR t.tag ' . db_ilike() . '(' . $search . ')
                        )';
        }

        $select .= $extraselect;

        $selectph = $countph = $ph;

        if ($short) {
            // We just want to know which artefact ids are allowed for inclusion in a view,
            // but get_records_sql_assoc wants > 1 column
            $cols = 'a.id, a.id AS b';
        }
        else {
            $cols = 'a.*';

            // We also want to know which artefacts can be edited by the logged-in user within
            // the context of the view.  For an institution view, all artefacts from the same
            // institution are editable.  For an individual view, artefacts with the same 'owner'
            // are editable.  For group views, only those artefacts with the can_edit permission
            // out of artefact_access_role are editable.

            if ($group) {
                $expr = 'ga.can_edit IS NOT NULL AND ga.can_edit = 1';
            }
            else if ($institution) {
                $expr = 'a.institution = ?';
                array_unshift($selectph, $institution);
            }
            else {
                $expr = 'a.owner IS NOT NULL AND a.owner = ?';
                array_unshift($selectph, $user->get('id'));
            }
            if (is_mysql()) {
                $cols .= ", ($expr) AS editable";
            }
            else {
                $cols .= ", CAST($expr AS INTEGER) AS editable";
            }
        }

        if (isset($data['blocktype']) && $data['blocktype'] == 'blogpost') {
                $from .= ' INNER JOIN {artefact_blog_blogpost} abb on a.id = abb.blogpost ';
                $select .= ' AND abb.published = 1 ';
        }

        $artefacts = get_records_sql_assoc(
            'SELECT DISTINCT agg.* FROM (SELECT ' . $cols . $from . ' WHERE ' . $select . $sortorder . ') AS agg', $selectph, $offset, $limit
        );
        $totalartefacts = count_records_sql('SELECT COUNT(DISTINCT agg.id) FROM (SELECT a.* ' . $from . ' WHERE ' . $select . ') AS agg', $countph);

        if (!empty($data['artefacttypes'])) {
            $artefacts = (!$artefacts) ? array() : $artefacts;
            list($customprofile, $customtotals) = self::get_custom_profiles($data['artefacttypes']);
            $artefacts = array_merge($artefacts, $customprofile);
            $totalartefacts += $customtotals;
        }

        return array($artefacts, $totalartefacts);
    }

    public static function get_custom_profiles($artefacttypes) {
        // If our profile artefact is saving it's data to a special place
        safe_require('artefact', 'internal');
        $customprofiles = array();
        $customtotals = 0;
        foreach ($artefacttypes as $type) {
            $classname = 'ArtefactType' . ucfirst($type);
            if (is_callable(array($classname, 'get_special_data'))) {
                $customprofile = call_static_method($classname, 'get_special_data', $user);
                if ($customprofile) {
                    $customprofile->artefacttype = $type;
                    $customprofile->title = $customprofile->{$type};
                    $customprofiles[] = $customprofile;
                    $customtotals++;
                }
            }
        }
        return array($customprofiles, $customtotals);
    }

    public static function owner_name($ownerformat, $user) {

        switch ($ownerformat) {
            case FORMAT_NAME_FIRSTNAME:
                return $user->firstname;
            case FORMAT_NAME_LASTNAME:
                return $user->lastname;
            case FORMAT_NAME_FIRSTNAMELASTNAME:
                return $user->firstname . ' ' . $user->lastname;
            case FORMAT_NAME_PREFERREDNAME:
                return $user->preferredname;
            case FORMAT_NAME_STUDENTID:
                return $user->studentid;
            case FORMAT_NAME_DISPLAYNAME:
            default:
                return display_name($user);
        }
    }

    public static function can_remove_viewtype($viewtype) {
        $cannotremove = array('profile', 'dashboard', 'grouphomepage');
        if (in_array($viewtype, $cannotremove)) {
            return false;
        }
        // allow local custom code to make 'sticky' view types
        if (function_exists('local_can_remove_viewtype')) {
            return local_can_remove_viewtype($viewtype);
        }
        return true;
    }

    public function can_edit_title() {
        return self::can_remove_viewtype($this->type);
    }

    /**
     * Given a query text and fields where to search in,
     * returns the list of views owned by the user (group or institution) matching those parameters.
     *
     * @param int $limit Sets the LIMIT value in sql query
     * @param int $offset Sets the OFFSET value in sql query
     * @param string $query Text to search for (treated as comma separated list when $searchin = tagsonly)
     * @param string $tag Text to search for in the tags. Not used anymore, we use $query instead
     * @param int $groupid Contains the group, if searching in group pages ans collections
     * @param string $institution Contains the institution, if searching in institution pages and collections
     * @param string $searchin Fields to search in
     *        values: tagsonly, titleanddescription, titleanddescriptionandtags
     * @param string $orderby Sets the order of the results
     *        values: latestcreated, latestmodified, latestviewed, mostvisited, mostcomments
     * @param bool $alltags Used in conjunction with $serachin = tagsonly. When true it only returns results with all supplied tags
     * @return object containing views matching the query and their count
     */
    public static function get_myviews_data($limit=12, $offset=0, $query=null, $tag=null, $groupid=null, $institution=null, $orderby=null, $searchin=null, $alltags=false) {
        global $USER;
        $userid = (!$groupid && !$institution) ? $USER->get('id') : null;
        $haslti = is_plugin_active('lti', 'module') ? true : false;

        $select = '
            SELECT v.id, v.id AS vid, v.title, v.title AS vtitle, v.description, v.type,  v.ctime as vctime, v.mtime as vmtime, v.atime as vatime,
            v.owner, v.group, v.institution, v.locked, v.ownerformat, v.urlid, v.visits AS vvisits, 1 AS numviews, NULL AS collid, v.coverimage';
        $collselect = '
            UNION
            SELECT (SELECT view FROM {collection_view} cvid WHERE cvid.collection = c.id AND displayorder = 0) as id,
            null AS vid, c.name as title, c.name AS vtitle, c.description, null as type, c.ctime as vctime, c.mtime as vmtime, c.mtime as vatime,
            c.owner, c.group, c.institution, null as locked, null as ownerformat, null as urlid, null AS vvisits,
                   (SELECT COUNT(*) FROM {collection_view} cv WHERE cv.collection = c.id) AS numviews, c.id AS collid, c.coverimage';
        $emptycollselect = '
            UNION
            SELECT null as id, null as vid, c.name as title, c.name AS vtitle, c.description, null as type, c.ctime as vctime, c.mtime as vmtime, c.mtime as vatime,
            c.owner, c.group, c.institution, null as locked, null as ownerformat, null as urlid, null as vvisits, 0 AS numviews, c.id AS collid, c.coverimage';

        $from = '
            FROM {view} v
            LEFT OUTER JOIN {collection_view} cv on cv.view = v.id';
        $collfrom = '
            FROM {view} v
            LEFT OUTER JOIN {collection_view} cv ON cv.view = v.id
            LEFT OUTER JOIN {collection} c ON cv.collection = c.id';
        $emptycollfrom = '
            FROM {collection} c';

        $where = '
            WHERE cv.collection IS NULL AND v.' . self::owner_sql((object) array('owner' => $userid, 'group' => $groupid, 'institution' => $institution));
        $collwhere = '
            WHERE cv.collection IS NOT NULL AND v.' . self::owner_sql((object) array('owner' => $userid, 'group' => $groupid, 'institution' => $institution)) . '
            AND v.id IN (
              SELECT view FROM {collection_view} WHERE collection = c.id
            )';
        $emptycollwhere = '
            WHERE c.' . self::owner_sql((object) array('owner' => $userid, 'group' => $groupid, 'institution' => $institution)) . '
            AND NOT EXISTS (SELECT * FROM {collection_view} cv WHERE c.id = cv.collection)';

        // We use institution='mahara' and template=2 for the default site template
        if (isset($institution) && $institution === 'mahara') {
            $where .= ' AND v.template != ' . self::SITE_TEMPLATE;
            $collwhere .= ' AND v.template != ' . self::SITE_TEMPLATE;
        }

        $order = $groupby = $collgroupby = $emptycollgroupby = '';
        if (!empty($orderby)) {
            switch($orderby) {
                case 'latestcreated':
                    $order = 'vctime DESC,';
                    break;
                case 'latestmodified':
                    $order = 'vmtime DESC,';
                    break;
                case 'latestviewed':
                    $order = 'vatime DESC,';
                    break;
                case 'mostvisited':
                    $order = 'vvisits DESC,';
                    break;
                case 'mostcomments':
                    $mcstr = ', COUNT(DISTINCT acc.artefact) AS commentcount';
                    $select .= $mcstr;
                    $collselect .= $mcstr;
                    $emptycollselect .= ', 0 AS commentcount';
                    $mcfromstr = '
                        LEFT OUTER JOIN {artefact_comment_comment} acc ON (v.id = acc.onview AND acc.hidden=0)';
                    $from .= $mcfromstr;
                    $collfrom .= $mcfromstr;
                    $groupby = ' GROUP BY v.id';
                    $collgroupby = ' GROUP BY c.id';
                    $emptycollgroupby = ' GROUP BY c.id';
                    $order = 'commentcount DESC,';
                    break;
                default:
                    $order = '';
            }
        }

        $sort = '
            ORDER BY ' . $order . ' vtitle, vid';

        $values = $collvalues = $emptycollvalues = array();
        $typecast = is_postgres() ? '::varchar' : '';

        if (!empty($searchin) && $query != '') {
            switch($searchin) {
                case 'tagsonly':
                    $tagstr = "
                        LEFT JOIN {tag} vt ON (vt.resourcetype = 'view' AND vt.resourceid = v.id" . $typecast . ")";
                    $colltagstr = "
                        LEFT JOIN {tag} ct ON (ct.resourcetype = 'collection' AND ct.resourceid = c.id" . $typecast . ")";
                    $query_arr = array_map('trim', explode(',', $query));

                    if ($alltags && count($query_arr) > 1) {
                        $tagwhere = "vt.tag = ? ";
                        foreach ($query_arr as $qk => $qv) {
                            if ($qk > 0) {
                                $tagstr .= " LEFT JOIN {tag} vt" . $qk . " ON (vt" . $qk . ".resourcetype = 'view' AND vt" . $qk . ".resourceid = v.id" . $typecast . ")";
                                $tagwhere .= " AND vt" . $qk . ".tag = ? ";
                            }
                        }
                        $where .= " AND " . $tagwhere;
                        $colltagwhere = "ct.tag = ? ";
                        reset($query_arr);
                        foreach ($query_arr as $qk => $qv) {
                            if ($qk > 0) {
                                $colltagstr .= " LEFT JOIN {tag} ct" . $qk . " ON (ct" . $qk . ".resourcetype = 'collection' AND ct" . $qk . ".resourceid = c.id" . $typecast .")";
                                $colltagwhere .= " AND ct" . $qk . ".tag = ? ";
                            }
                        }
                        $collwhere .= " AND ((" . $tagwhere . ") OR (" . $colltagwhere . "))";
                        $collvalues = array_merge($query_arr, $query_arr);
                        $emptycollwhere .= " AND " . $colltagwhere;
                        $emptycollvalues = $query_arr;
                    }
                    else {
                        $tagwhere = "vt.tag IN(" . implode(',', array_fill(0, count($query_arr), '?')) . ")";
                        $colltagwhere = "ct.tag IN(" . implode(',', array_fill(0, count($query_arr), '?')) . ")";
                        $where .= "
                            AND " . $tagwhere;
                        $collwhere .= "
                            AND (" . $tagwhere . " OR " . $colltagwhere . ")";
                        $emptycollwhere .= "
                            AND " . $colltagwhere;
                        $collvalues = array_merge($query_arr, $query_arr);
                        $emptycollvalues = $query_arr;
                    }

                    $from .= $tagstr;
                    $collfrom .= $tagstr . $colltagstr;
                    $emptycollfrom .= $colltagstr;
                    $values = $query_arr;

                    break;
                case 'titleanddescription':
                    // Include matches on the title or description
                    $like = db_ilike();
                    $tagwhere = "
                        (v.title $like '%' || ? || '%' OR v.description $like '%' || ? || '%')";
                    $colltagwhere = "
                        (c.name $like '%' || ? || '%' OR c.description $like '%' || ? || '%')";
                    $where .= ' AND ' . $tagwhere;
                    $collwhere .= ' AND (' . $tagwhere .' OR '. $colltagwhere . ')';
                    $emptycollwhere .= ' AND ' . $colltagwhere;
                    array_push($collvalues, $query, $query,$query, $query, $query, $query, $query, $query);
                    break;
                case 'titleanddescriptionandtags':
                    // Include matches on the title, description or tag
                    $tagstr = "
                        LEFT JOIN {tag} vt ON (vt.resourcetype = 'view' AND vt.resourceid = v.id" . $typecast . " AND vt.tag = ?)";
                    $colltagstr = "
                        LEFT JOIN {tag} ct ON (ct.resourcetype = 'collection' AND ct.resourceid = c.id" . $typecast . " AND ct.tag = ?)";
                    $from .= $tagstr;
                    $collfrom .= $tagstr . $colltagstr;
                    $emptycollfrom .= $colltagstr;
                    $like = db_ilike();
                    $tagwhere = "
                        (v.title $like '%' || ? || '%' OR v.description $like '%' || ? || '%' OR vt.tag = ?)";
                    $colltagwhere = "
                        (c.name $like '%' || ? || '%' OR c.description $like '%' || ? || '%' OR ct.tag = ?)";
                    $where .= ' AND ' . $tagwhere;
                    $collwhere .= ' AND (' . $tagwhere . ' OR ' . $colltagwhere . ')';
                    $emptycollwhere .= ' AND ' . $colltagwhere;
                    array_push($values, $query, $query, $query, $query);
                    array_push($collvalues, $query, $query, $query, $query,$query, $query, $query, $query);
                    array_push($collvalues, $query, $query, $query, $query);
            }
        }

        if ($groupid && group_user_access($groupid) != 'admin') {
            $groupstr = " AND v.type != 'grouphomepage'";
            $where .= $groupstr;
            $collwhere .= $groupstr;
        }
        if ($userid) {
            $select .= ',v.submittedtime, v.submittedstatus,
                g.id AS submitgroupid, g.name AS submitgroupname, g.urlid AS submitgroupurlid,
                v.submittedhost AS submithostwwwroot, h.name AS submithostname' . ($haslti ? ', a.id AS ltiassessment' : '');
            $collselect .= ', c.submittedtime, c.submittedstatus,
                g.id AS submitgroupid, g.name AS submitgroupname, g.urlid AS submitgroupurlid,
                c.submittedhost AS submithostwwwroot, h.name AS submithostname' . ($haslti ? ', a.id AS ltiassessment' : '');
            $emptycollselect .= ', c.submittedtime, c.submittedstatus,
                NULL AS submitgroupid, NULL AS submitgroupname, NULL AS submitgroupurlid,
                NULL AS submithostwwwroot, NULL AS submithostname' . ($haslti ? ', NULL AS ltiassessment' : '');

            $fromstr = '
                LEFT OUTER JOIN {group} g ON (v.submittedgroup = g.id AND g.deleted = 0)
                LEFT OUTER JOIN {host} h ON (v.submittedhost = h.wwwroot)';
            if ($haslti) {
                $fromstr .= ' LEFT JOIN {lti_assessment} a ON g.id = a.group ';
            }

            $from .= $fromstr;
            $collfrom .= $fromstr;

            if (!empty($groupby)) {
                // Adding groupby condition for lti_assessment id column.
                $groupby .= ', g.id, h.wwwroot' . ($haslti ? ', a.id' : '');
                $collgroupby .= ', g.id, h.wwwroot' . ($haslti ? ', a.id' : '');
            }
            $sort = '
                ORDER BY ' . $order . ' vtitle, vid';
        }

        $values = array_merge($values, $collvalues, $emptycollvalues);

        // When using group by we need to get the count of how many rows are returned
        // and not the count value of the first row returned.
        if (!empty($groupby)) {
            $count = get_records_sql_array('SELECT SUM(count) AS count FROM (
                                                SELECT COUNT(*) AS count FROM (
                                                    SELECT COUNT(v.id) ' . $from . $where . $groupby . ') t1
                                                UNION ALL
                                                SELECT COUNT(*) AS count FROM (
                                                    SELECT COUNT(c.id) ' . $collfrom . $collwhere . $collgroupby . ') t2
                                                UNION ALL
                                                SELECT COUNT(*) AS count FROM (
                                                    SELECT COUNT(c.id) ' . $emptycollfrom . $emptycollwhere . $emptycollgroupby . ') t3
                                            ) t4', $values);
            $viewdata = get_records_sql_array($select . $from . $where . $groupby .
                                              $collselect . $collfrom . $collwhere . $collgroupby .
                                              $emptycollselect . $emptycollfrom . $emptycollwhere . $emptycollgroupby .
                                              $sort, $values, $offset, $limit);
        }
        else {
            $count = get_records_sql_array('SELECT SUM(t.numresults) AS count FROM (
                                                SELECT COUNT(v.id) AS numresults ' . $from . $where . '
                                                UNION ALL
                                                SELECT COUNT(id) AS numresults FROM (
                                                  SELECT DISTINCT c.id ' . $collfrom . $collwhere . ') t1
                                                UNION ALL
                                                SELECT COUNT(c.id) AS numresults ' . $emptycollfrom . $emptycollwhere . ') t', $values);
            $viewdata = get_records_sql_array($select . $from . $where .
                                              $collselect . $collfrom . $collwhere .
                                              $emptycollselect . $emptycollfrom . $emptycollwhere . $groupby .
                                              $sort, $values, $offset, $limit);
        }
        $count = !empty($count[0]->count) ? $count[0]->count : false;

        View::get_extra_view_info($viewdata, false);
        View::get_extra_collection_info($viewdata, false, 'collid');

        require_once('collection.php');
        require_once('group.php');
        if ($viewdata) {
            foreach ($viewdata as $id => &$data) {
                $data['uniqueid'] = 'u' . $data['id'] . '_' . $data['collid'];
                if (!empty($data['collid'])) {
                    $collobj = new Collection($data['collid']);
                    $data['displaytitle'] = $collobj->get('name');
                    $data['collviews'] = $collobj->views();
                    if ($collobj->has_framework()) {
                        $data['framework'] = $collobj->collection_nav_framework_option();
                    }
                    if ($collobj->has_progresscompletion()) {
                        $data['progresscompletion'] = $collobj->collection_nav_progresscompletion_option();
                    }
                }

                $data['removable'] = self::can_remove_viewtype($data['type']);
                if (!empty($data['submittedstatus'])) {
                    $status = $data['submittedstatus'];
                    if (!empty($data['submitgroupid'])) {
                        if ($haslti && $data['ltiassessment']) {
                            $url = '#';
                        }
                        else {
                            $url = group_homepage_url((object) array('id' => $data['submitgroupid'], 'urlid' => $data['submitgroupurlid']));
                        }
                        $name = hsc($data['submitgroupname']);
                    }
                    else if (!empty($data['submithostwwwroot'])) {
                        $url = $data['submithostwwwroot'];
                        $name = !empty($data['submithostname']) ? hsc($data['submithostname']) : $data['submithostwwwroot'];
                    }

                    $time = (!empty($data['submittedtime'])) ? format_date(strtotime($data['submittedtime'])) : null;

                    if (!empty($status) && !empty($time)) {
                        $data['submittedto'] = get_string('viewsubmittedtogroupon1', 'view', $url, $name, $time);
                    }
                    else if (!empty($status)) {
                        $data['submittedto'] = get_string('viewsubmittedtogroup1', 'view', $url, $name);
                    }
                    if ($status == self::PENDING_RELEASE) {
                        $data['submittedto'] .= ' ' . get_string('submittedpendingrelease', 'view');
                    }
                }

                // get the access rules for this view/collection
                if (!empty($data['id']) && $data['type'] != 'dashboard') {
                    $ua = new stdClass();
                    $ua->displayname = get_string('manageaccess', 'view');
                    $ua->accessibilityname = get_string('manageaccessfor', 'view', $data['vtitle']);
                    $ua->accesstype = 'managesharing';

                    $data['manageaccess'] = array($ua);
                    $data['manageaccesssuspended'] = self::access_override_pending(array('id' => $data['vid'])) ? true : false;

                    if ($accesslist = get_records_sql_array('
                        SELECT va.*, g.name AS groupname, g.grouptype, i.displayname AS institutionname
                        FROM {view_access} va
                        LEFT JOIN {group} g ON g.id = va.group
                        LEFT JOIN {institution} i on i.name = va.institution
                        WHERE va.view = ?
                        ORDER BY token DESC, accesstype IS NULL ASC, accesstype ASC, usr, "group", institution', array($data['id']))) {
                        // Use the special sorting so that we get all the 'public' access rules at the top of the list
                        // then access rules where some groups of people can see it and lastly specified users/friends
                        foreach ($accesslist as $ak => $av) {
                            // remove 'Registered users' from the list if isolated institutions are enabled
                            if ($av->accesstype == 'loggedin' && is_isolated()) {
                                continue;
                            }
                            // remove 'Friends' from the list if friendsnotallowed is enabled
                            if ($av->accesstype == 'friends' && get_config('friendsnotallowed')) {
                                continue;
                            }
                            if ($av->usr) {
                                $av->displayname = display_name($av->usr);
                                if (!empty($av->role)) {
                                    $av->roledisplay = get_string($av->role, 'view');
                                }
                            }
                            else if ($av->group) {
                                $av->displayname = $av->groupname;
                                // A submitted view/collection adds 'admin' role access to group
                                if (!empty($data['submittedstatus']) && $av->group == $data['submitgroupid'] && $av->role == 'admin') {
                                    $av->displayname .= ' (' . get_string('submitted', 'group') . ')';
                                }
                                else if (!empty($av->role)) {
                                    $av->displayname .= ' (' . get_string($av->role, 'grouptype.' . $av->grouptype) . ')';
                                }
                            }
                            else if ($av->institution) {
                                $av->displayname = $av->institutionname;
                            }
                            $data['accesslist'][$ak] = $av;
                        }
                    }
                    else {
                        $data['accesslist'] = false;
                    }
                }
            }
            $viewdata = array_values($viewdata);
        }

        return (object) array(
            'data'  => $viewdata,
            'count' => $count,
        );
    }

    public static function get_myviews_url($group=null, $institution=null, $query=null, $searchin=null, $orderby=null, $matchalltags=false) {
        $queryparams = array();

        if ($query != '') {
            $queryparams[] =  'query=' . urlencode($query);
        }
        if (!empty($searchin)) {
            $queryparams[] = 'searchin=' . urldecode($searchin);
        }
        if (!empty($orderby)) {
            $queryparams[] = 'orderby=' . urlencode($orderby);
        }
        if (!empty($matchalltags)) {
            $queryparams[] = 'matchalltags=' . urldecode($matchalltags);
        }
        if ($group) {
            $url = get_config('wwwroot') . 'view/groupviews.php';
            $queryparams[] = 'group=' . $group;
        }
        else if ($institution) {
            if ($institution == 'mahara') {
                $url = get_config('wwwroot') . 'admin/site/views.php';
                $queryparams[] = 'institution=' . $institution;
            }
            else {
                $url = get_config('wwwroot') . 'view/institutionviews.php';
                $queryparams[] = 'institution=' . $institution;
            }
        }
        else {
            $url = get_config('wwwroot') . 'view/index.php';
        }

        if (!empty($queryparams)) {
            $url .= '?' . join('&', $queryparams);
        }

        return $url;
    }

    public static function views_by_owner($group=null, $institution=null) {
        global $USER;

        // 'Show more' pagination configuration
        $limit = param_integer('limit', 12);
        $offset = param_integer('offset', 0);
        // load default page order from user settings as default and overwrite, if changed
        $usersettingorderby = get_account_preference($USER->get('id'), 'orderpagesby');
        $orderby = param_variable('orderby', $usersettingorderby);
        if ($usersettingorderby !== $orderby) {
            set_account_preference($USER->get('id'), 'orderpagesby', $orderby);
        }

        $usersettingsearchin = get_account_preference($USER->get('id'), 'searchinfields');
        $searchin = param_variable('searchin', $usersettingsearchin);
        if ($usersettingsearchin !== $searchin) {
            set_account_preference($USER->get('id'), 'searchinfields', $searchin);
        }
        $matchalltags = param_boolean('matchalltags', false);
        $query  = param_variable('query', null);

        $searchoptions = array(
            'titleanddescriptionandtags' => get_string('titleanddescription', 'view'),
            'titleanddescription' => get_string('titleanddescriptionnotags', 'view'),
            'tagsonly' => get_string('tagsonly1', 'view'),
        );

        $searchdefault = $query;

        $searchform = array(
            'name' => 'searchviews',
            'checkdirtychange' => false,
            'class' => 'with-heading form-inline',
            'elements' => array(
                'searchwithin' => array (
                    'type' => 'fieldset',
                    'class' => 'dropdown-group js-dropdown-group',
                    'elements' => array(
                        'query' => array(
                            'type' => 'text',
                            'title' => get_string('search') . ': ',
                            'class' => 'with-dropdown js-with-dropdown',
                            'defaultvalue' => $searchdefault,
                        ),
                        'type' => array(
                            'title'        => get_string('searchwithin'). ': ',
                            'class' => 'dropdown-connect js-dropdown-connect searchviews-type',
                            'type'         => 'select',
                            'options'      => $searchoptions,
                            'defaultvalue' => $searchin,
                        )
                    )
                ),
                'orderbygroup' => array (
                    'type' => 'fieldset',
                    'class' => 'input-group',
                    'elements' => array(
                         'orderby' => array(
                            'type' => 'select',
                            'class' => 'input-small',
                            'title' => get_string('sortby'),
                            'options' => array('atoz' => get_string('defaultsort', 'view'),
                                               'latestcreated' => get_string('latestcreated', 'view'),
                                               'latestmodified' => get_string('latestmodified', 'view'),
                                               'latestviewed' => get_string('latestviewed', 'view'),
                                               'mostvisited' => get_string('mostvisited', 'view'),
                                               'mostcomments' => get_string('mostcomments1', 'view'),
                                               ),
                            'defaultvalue' => $orderby,
                        ),
                         'submit' => array(
                            'type' => 'button',
                            'usebuttontag' => true,
                            'class' => 'btn-secondary input-group-append no-label',
                            'value' => get_string('search')
                        )
                    )
                ),
                'matchalltags' => array (
                    'type' => 'checkbox',
                    'class' => 'matchalltags d-none',
                    'title' => get_string('matchalltags', 'view'),
                    'defaultvalue' => $matchalltags,
                    'description' => get_string('matchalltagsdesc', 'view'),
                )
            )
        );

        if ($group) {
            $searchform['elements']['group'] = array('type' => 'hidden', 'name' => 'group', 'value' => $group);
        }
        else if ($institution) {
            $searchform['elements']['institution'] = array('type' => 'hidden', 'name' => 'institution', 'value' => $institution);
        }

        $searchform = pieform($searchform);

        $data = self::get_myviews_data($limit, $offset, $query, null, $group, $institution, $orderby, $searchin, $matchalltags);

        $url = self::get_myviews_url($group, $institution, $query, $searchin, $orderby, $matchalltags);

        $pagination = build_showmore_pagination(array(
            'count'  => $data->count,
            'limit'  => $limit,
            'offset' => $offset,
            'orderby' => $orderby,
            'extra' => array('group' => $group,
                             'institution' => $institution),
            'databutton' => 'showmorebtn',
            'jsonscript' => 'json/viewlist.php',
        ));

        return array($searchform, $data, $pagination);
    }


    public function get_site_template_views() {
        $views = get_records_sql_array(
            "SELECT v.*
               FROM {view} v
              WHERE v.institution = 'mahara' AND v.template = " . self::SITE_TEMPLATE . "
              ORDER BY v.title, v.id", array());
        $results = array();
        if ($views) {
            foreach ($views as $view) {
                $view->displaytitle = get_string('template' . $view->type, 'view');
                $view->issitetemplate = true;
                $results[] = (array)$view;
            }
        }
        return $results;
    }

    /**
     * Returns an SQL snippet that can be used in a where clause to get views
     * with the given owner.
     *
     * @param object $ownerobj An object that has view ownership information -
     *                         either the institution, group or owner fields set
     * @return string
     */
    private static function owner_sql($ownerobj) {
        if (isset($ownerobj->institution)) {
            return 'institution = ' . db_quote($ownerobj->institution);
        }
        if (isset($ownerobj->group) && is_numeric($ownerobj->group)) {
            return '"group" = ' . (int)$ownerobj->group;
        }
        if (isset($ownerobj->owner) && is_numeric($ownerobj->owner)) {
            return 'owner = ' . (int)$ownerobj->owner;
        }
        throw new SystemException("View::owner_sql: Passed object did not have an institution, group or owner field");
    }

    /**
     * Returns an SQL snippet that can be used in a where clause to get views
     * with the given set of owners.
     *
     * Only one of the owner, group, institution fields is used.
     *
     * @param object $ownerobj An object that has view ownership information -
     *                         either the institution, group or owner fields set
     * @return array containing an sql string and an array of placeholder values
     */
    private static function multiple_owner_sql($ownerobj) {
        foreach (get_object_vars($ownerobj) as $column => $values) {
            if (is_array($values) && !empty($values)) {
                return array(
                    '"' . $column . '" IN (' . join(',', array_fill(0, count($values), '?')) . ')',
                    $values,
                );
            }
        }
        return array(self::owner_sql($ownerobj), array());
    }

    /**
     * Check if the view is copyable by the user.
     *
     * @return bool
     */
    public function is_copyable() {
        global $USER;

        $search = new stdClass();
        $search->copyableby = (object) array('group' => null, 'institution' => null, 'owner' => $USER->get('id'));
        $results = self::view_search('', '', null, $search->copyableby, null, null, true, null, null, false, null, null, $this->id);
        // Check that the this view is one the user is allowed to copy
        if (!empty($results->count)) {
            return true;
        }
        // Check if view has a secret url and is also a template
        if (count_records_sql("SELECT COUNT(*) FROM {view} v
                               JOIN {view_access} va ON va.view = v.id
                               WHERE (va.token IS NOT null AND va.token !='')
                               AND v.template = ?
                               AND v.id = ?", array(self::USER_TEMPLATE, $this->id))) {
            return true;
        }
        return false;
    }

    /**
     * Get all views visible to a user.  Complicated because a view v
     * is visible to a user u at time t if any of the following are
     * true:
     *
     * - u is a site admin
     * - v is owned by u
     * - v is owned by a group g, and u has a role (within g) with view editing permission
     * - v is publically visible at t (in view_access)
     * - v is visible to logged in users at t (in view_access)
     * - v is visible to friends at t, and u is a friend of the view owner (in view_access)
     * - v is visible to institution at t, and u is a member of the institution (in view_access)
     * - v is visible to u at t (in view_access_usr)
     * - v is visible to all roles of group g at t, and u is a member of g (view_access_group)
     * - v is visible to users with role r of group g at t, and u is a member of g with role r (view_access_group)
     *
     * @param string   $query       Search string
     * @param string   $ownerquery  Search string for owner
     * @param stdClass $ownedby     Only return views owned by this owner (owner, group, institution)
     * @param stdClass $copyableby  Only return views copyable by this owner (owner, group, institution)
     * @param integer  $limit
     * @param integer  $offset
     * @param bool     $extra       Return full set of properties on each view including an artefact list
     * @param array    $sort        Order by, each element of the array is an array containing "column" (string) and "desc" (boolean)
     * @param array    $types       List of view types to filter by
     * @param bool     $collection  Use query against collection names and descriptions
     * @param array    $accesstypes Only return views visible due to the given access types
     * @param array    $tag         Only return views with this tag
     * @param integer  $viewid      Only return a particular view (find by view id)
     * @param integer  $excludeowner Only return views not owned by this owner id
     * @param boolean  $groupbycollection Return one record for each collection, and one record for each view that's not in a collection
     * @return array
     */
    public static function view_search($query=null, $ownerquery=null, $ownedby=null, $copyableby=null, $limit=null, $offset=0,
                                       $extra=true, $sort=null, $types=null, $collection=false, $accesstypes=null, $tag=null,
                                       $viewid=null, $excludeowner=null, $groupbycollection=null) {
        global $USER;
        $admin = $USER->get('admin');
        $loggedin = $USER->is_logged_in();
        $viewerid = $USER->get('id');

        // Query parameters
        $fromparams = array();
        $whereparams = array();

        $from = '
            FROM {view} v
            LEFT OUTER JOIN {collection_view} cv ON cv.view = v.id
            LEFT OUTER JOIN {collection} c ON cv.collection = c.id
            LEFT OUTER JOIN {usr} qu ON (v.owner = qu.id)
            LEFT OUTER JOIN {group} sg ON sg.id = v.group
        ';
        $typecast = is_postgres() ? '::varchar' : '';
        $where = '';
        if ($excludeowner) {
            $where .= ' WHERE (v.owner IS NULL OR (v.owner > 0 AND v.owner != ?))';
            $whereparams[] = $excludeowner;
        }
        else {
            $where .= ' WHERE (v.owner IS NULL OR v.owner > 0)';
        }
        $where .= ' AND v.template != ' . self::SITE_TEMPLATE;
        $where .= '
                AND (v.group IS NULL OR (v.group > 0 AND sg.deleted <> 1))
                AND (qu.suspendedctime is null OR v.owner = ?)';

        $whereparams[] = $viewerid;

        if (is_array($types) && !empty($types)) {
            $where .= ' AND v.type IN (';
        }
        else {
            $where .= ' AND v.type NOT IN (';
            if ($admin) {
                $types = array('profile', 'dashboard');
            }
            else {
                $types = array('profile', 'dashboard', 'grouphomepage');
            }
        }
        $where .= join(',', array_map('db_quote', $types)) . ')';

        if ($ownedby) {
            if (is_array($ownedby)) {
                // we have an array of owner objects
                $ownerwherestr = '(';
                foreach ($ownedby as $key => $ownedbyobject) {
                    if ($key !== 0) {
                        $ownerwherestr .= ' OR ';
                    }
                    $ownerwherestr .= 'v.' . self::owner_sql($ownedbyobject);
                }
                $ownerwherestr .= ')';
                $where .= ' AND ' . $ownerwherestr;
            }
            else {
                $where .= ' AND v.' . self::owner_sql($ownedby);
            }
        }

        if ($copyableby) {
            $where .= '
                AND (v.template = 1 OR (v.' . self::owner_sql($copyableby) . '))';
        }

        $like = db_ilike();

        if ($query) { // Include matches on the title, description, tag or user

            // Include the group and institution tables to query on the owners's name.
            // Not same as $ownerquery as that 'AND's the owners.
            // Please note that these tables are joined again below
            // when $ownerquery is specified. Hence, the extra 'q' on the
            // table alias.
            $from .= "
                LEFT JOIN {tag} vt ON (vt.resourcetype = 'view' AND vt.resourceid = v.id" . $typecast . " AND vt.tag = ?)
                LEFT OUTER JOIN {group} qqg ON (v.group = qqg.id)
                LEFT OUTER JOIN {institution} qqi ON (v.institution = qqi.name)";
            if (strpos(strtolower(get_config('sitename')), strtolower($query)) !== false) {
                $sitequery = " OR qqi.name = 'mahara'";
            }
            else {
                $sitequery = '';
            }

            $where .= "
                AND (v.title $like '%' || ? || '%'
                    OR v.description $like '%' || ? || '%'
                    OR vt.tag = ?
                    OR qu.preferredname $like '%' || ? || '%'
                    OR qu.firstname $like '%' || ? || '%'
                    OR qu.lastname $like '%' || ? || '%'
                    OR qqg.name $like '%' || ? || '%'
                    OR qqi.displayname $like '%' || ? || '%'
                    $sitequery
                    ";
            array_push($fromparams, $query);
            array_push($whereparams, $query, $query, $query, $query, $query, $query, $query, $query);
            if ($collection) {
                $where .= "
                    OR c.name $like '%' || ? || '%' OR c.description $like '%' || ? || '%' ";
                array_push($whereparams, $query, $query);
            }
            if ($admin || $USER->get('staff') || !get_config('nousernames')) {
                // If the site setting 'Never display usernames' is disabled, allow searching by username.
                $where .= "
                    OR qu.username $like '%' || ? || '%' ";
                array_push($whereparams, $query);
            }
            if ($groupbycollection) {
                $where .= "OR EXISTS (
                    SELECT 1
                    FROM
                        {view} v2
                        INNER JOIN {collection_view} cv2
                            ON v2.id=cv2.view
                        INNER JOIN {collection} c2
                            ON c2.id = cv2.collection
                        LEFT OUTER JOIN {tag} vt
                            ON (vt.resourcetype = 'view' AND vt.resourceid = v2.id" . $typecast . " AND vt.tag = ?)
                        LEFT OUTER JOIN {tag} ct
                            ON (ct.resourcetype = 'collection' AND ct.resourceid = cv2.collection" . $typecast . " AND ct.tag = ?)
                    WHERE
                        cv2.collection = cv.collection
                        AND (
                            v2.title $like '%' || ? || '%'
                            OR v2.description $like '%' || ? || '%'
                            OR c2.name $like '%' || ? || '%'
                            OR c2.description $like '%' || ? || '%'
                            OR vt.tag = ?
                            OR ct.tag = ?
                        )
                )";
                array_push(
                    $whereparams,
                    $query,
                    $query,
                    $query,
                    $query,
                    $query,
                    $query,
                    $query,
                    $query
                );
            }
            $where .= ")";
        }
        else if ($tag) { // Filter by the tag
            $from .= "
                INNER JOIN {tag} vt ON (vt.resourcetype = 'view' AND vt.resourceid = v.id" . $typecast . " AND vt.tag = ?)";
            $fromparams[] = $tag;
        }

        if ($groupbycollection) {
            $where .= '
                AND (cv.displayorder = 0 OR cv.displayorder IS NULL)
            ';
        }

        if (is_array($accesstypes)) {
            $editableviews = in_array('editable', $accesstypes);
        }
        else if ($loggedin) {
            $editableviews = true;
            $accesstypes = array('public', 'loggedin', 'friend', 'user', 'group', 'institution');
        }
        else {
            $editableviews = false;
            $accesstypes = array('public');
        }

        if ($editableviews) {
            $editablesql = "v.owner = ?      -- user owns the view
                    OR EXISTS (  -- group view, editable by the user
                        SELECT m.group
                        FROM {group_member} m
                        WHERE
                            sg.id IS NOT NULL
                            AND m.group = sg.id
                            AND m.member = ?
                            AND (
                                m.role = 'admin'
                                OR sg.editroles = 'all'
                                OR (sg.editroles != 'admin' AND m.role != 'member')
                            )
                    )";
            $whereparams[] = $viewerid;
            $whereparams[] = $viewerid;
        }
        else {
            $editablesql = 'FALSE';
        }

        $accesssql = array();

        foreach ($accesstypes as $t) {
            if ($t == 'public') {
                $accesssql[] = "-- public access
                                SELECT va.view
                                FROM {view_access} va
                                WHERE va.accesstype = 'public'
                                    AND (va.startdate IS NULL OR va.startdate < current_timestamp)
                                    AND (va.stopdate IS NULL OR va.stopdate > current_timestamp)
                            ";
            }
            else if ($t == 'loggedin') {
                $accesssql[] = "-- loggedin access
                                SELECT va.view
                                FROM {view_access} va
                                WHERE va.accesstype = 'loggedin'
                                    AND (va.startdate IS NULL OR va.startdate < current_timestamp)
                                    AND (va.stopdate IS NULL OR va.stopdate > current_timestamp)
                            ";
            }
            else if ($t == 'friend') {
                $accesssql[] = "-- friend access
                                SELECT va.view
                                FROM {view_access} va
                                    JOIN {view} vf ON va.view = vf.id AND vf.owner IS NOT NULL
                                    JOIN {usr_friend} f ON ((f.usr1 = ? AND f.usr2 = vf.owner) OR (f.usr1 = vf.owner AND f.usr2 = ?))
                                WHERE va.accesstype = 'friends'
                                    AND (va.startdate IS NULL OR va.startdate < current_timestamp)
                                    AND (va.stopdate IS NULL OR va.stopdate > current_timestamp)
                            ";
                $whereparams[] = $viewerid;
                $whereparams[] = $viewerid;
            }
            else if ($t == 'user') {
                $accesssql[] = "-- user access
                                SELECT va.view
                                FROM {view_access} va
                                WHERE va.usr = ?
                                    AND (va.startdate IS NULL OR va.startdate < current_timestamp)
                                    AND (va.stopdate IS NULL OR va.stopdate > current_timestamp)
                            ";
                $whereparams[] = $viewerid;
            }
            else if ($t == 'group') {
                $accesssql[] = "-- group access
                                SELECT va.view
                                FROM {view_access} va
                                    JOIN {group_member} m ON va.group = m.group AND (va.role = m.role OR va.role IS NULL)
                                WHERE
                                    m.member = ?
                                    AND (va.startdate IS NULL OR va.startdate < current_timestamp)
                                    AND (va.stopdate IS NULL OR va.stopdate > current_timestamp)
                            ";
                $whereparams[] = $viewerid;
            }
            else if ($t == 'institution') {
                $accesssql[] = "-- institution access
                                SELECT va.view
                                FROM {view_access} va
                                    JOIN {usr_institution} ui ON va.institution = ui.institution
                                WHERE
                                    ui.usr = ?
                                    AND (va.startdate IS NULL OR va.startdate < current_timestamp)
                                    AND (va.stopdate IS NULL OR va.stopdate > current_timestamp)
                            ";
                $whereparams[] = $viewerid;
            }
        }

        if (!empty($accesssql)) {
            $accesssql = '( -- user has permission to see the view
                        (v.startdate IS NULL OR v.startdate < current_timestamp)
                        AND (v.stopdate IS NULL OR v.stopdate > current_timestamp)
                        AND (v.id IN (' . join(' UNION ', $accesssql) . ')))';
        }
        else {
            $accesssql = 'FALSE';
        }

        $where .= "
                AND ($editablesql
                    OR $accesssql)";

        if (!$ownedby && $ownerquery) {
            $from .= '
            LEFT OUTER JOIN {group} qg ON (v.group = qg.id)
            LEFT OUTER JOIN {institution} qi ON (v.institution = qi.name)';
            if (strpos(strtolower(get_config('sitename')), strtolower($ownerquery)) !== false) {
                $sitequery = " OR qi.name = 'mahara'";
            }
            else {
                $sitequery = '';
            }
            $where .= "
                AND (
                    qu.preferredname $like '%' || ? || '%'
                    OR qu.firstname $like '%' || ? || '%'
                    OR qu.lastname $like '%' || ? || '%'
                    OR qg.name $like '%' || ? || '%'
                    OR qi.displayname $like '%' || ? || '%'
                    $sitequery
                )";
            $whereparams = array_merge($whereparams, array($ownerquery,$ownerquery,$ownerquery,$ownerquery,$ownerquery));
        }

        $orderby = 'title ASC';
        if (!empty($sort)) {
            $orderby = '';
            foreach ($sort as $item) {
                if (!preg_match('/^[a-zA-Z_0-9\'="]+$/', $item['column'])) {
                    continue; // skip this item (it fails validation)
                }

                if (!empty($orderby)) {
                    $orderby .= ', ';
                }

                if ($item['column'] == 'lastchanged') {
                    // We need the date of the last comment on each view
                    $from .= 'LEFT OUTER JOIN (
                SELECT c.onview, MAX(a.mtime) AS lastcomment
                FROM {artefact_comment_comment} c JOIN {artefact} a ON c.artefact = a.id AND c.deletedby IS NULL AND c.private = 0 AND c.hidden=0
                GROUP BY c.onview
            ) l ON v.id = l.onview
            ';
                    $orderby .= 'GREATEST(lastcomment, v.mtime)';
                }
                else if ($item['column'] == 'ownername') {
                    // Join on usr, group, and institution and order by name
                    $from .= 'LEFT OUTER JOIN {usr} su ON su.id = v.owner
            LEFT OUTER JOIN {institution} si ON si.name = v.institution
            ';
                    $orderby .= "COALESCE(sg.name, si.displayname, CASE WHEN su.preferredname IS NOT NULL AND su.preferredname != '' THEN su.preferredname ELSE su.firstname || ' ' || su.lastname END)";
                }
                // in case we are grouping by collection and there is no tablealias
                // we should not force the alias to be 'v'
                else if (empty($item['tablealias']) && $groupbycollection) {
                    $orderby .= $item['column'];
                }
                else {
                    $orderby .= (!empty($item['tablealias']) ? $item['tablealias'] : 'v') . '.' . $item['column'];
                }

                if (!empty($item['desc'])) {
                    $orderby .= ' DESC';
                }
                else {
                    $orderby .= ' ASC';
                }
            }
        }
        // if we need to just check one view
        if (!empty($viewid)) {
            $where .= " AND v.id = ?";
            $whereparams = array_merge($whereparams, array($viewid));
        }
        $ph = array_merge($fromparams, $whereparams);
        $count = count_records_sql('SELECT COUNT(*) ' . $from . $where, $ph);

        if ($groupbycollection) {
            $select = '
                v.id AS viewid,
                -- generic id column needed by get_extra___info methods
                (CASE
                    WHEN c.id IS NOT NULL
                    THEN c.id
                    ELSE v.id
                END) AS id,
                (CASE
                    WHEN c.id IS NOT NULL
                    THEN c.name
                    ELSE v.title
                END) AS title,
                (CASE
                    WHEN c.id IS NOT NULL
                    THEN c.description
                    ELSE v.description
                END) AS description,
                (CASE
                    WHEN c.id IS NOT NULL
                    THEN (SELECT COUNT(*) FROM {collection_view} cv2 WHERE cv2.collection=c.id)
                    ELSE 0
                END) AS numpages,
            ';
        }
        else {
            $select = '
                    v.id, v.title, v.description,
            ';
        }
        $select .= '
                v.owner, v.ownerformat, v.group, v.institution, v.template, v.mtime, v.ctime,
                c.id as collid, c.name, c.framework, v.type, v.urlid, v.submittedtime, v.submittedgroup, v.submittedhost
        ';

        $viewdata = get_records_sql_assoc(
            'SELECT ' . $select . $from . $where . '
            ORDER BY ' . $orderby . ', v.id ASC',
            $ph, $offset, $limit
        );

        if ($viewdata) {
            if ($extra) {
                if (!$groupbycollection) {
                    View::get_extra_view_info($viewdata, false);
                }
                else {
                    // Split the collections and views into separate lists so
                    // we can send each to its bulk data-gathering method
                    $viewlist = array();
                    $collectionlist = array();
                    foreach ($viewdata as $k=>$v) {
                        if ($v->collid) {
                            $collectionlist[$v->collid] = $v;
                        }
                        else {
                            $viewlist[$v->viewid] = $v;
                        }
                    }

                    View::get_extra_collection_info($collectionlist);
                    View::get_extra_view_info($viewlist, false);

                    // Now update the data in $viewdata (we do this instead
                    // of using array_merge, in order to preserve the sortorder
                    // in $viewdata
                    foreach ($viewdata as $k=>$v) {
                        if ($v->collid) {
                            $viewdata[$k] = $collectionlist[$v->collid];
                        }
                        else {
                            $viewdata[$k] = $viewlist[$k];
                        }
                    }
                }
            }
        }
        else {
            $viewdata = array();
        }

        if (!empty($limit)) {
            return (object) array(
                'ids'   => array_keys($viewdata),
                'data'  => array_values($viewdata),
                'count' => $count,
                'limit' => $limit,
                'offset' => $offset,
            );
        }
        else {
            return (object) array(
                'ids'   => array_keys($viewdata),
                'data'  => array_values($viewdata),
                'count' => $count,
            );
        }

    }
    /**
     * Get views that have been shared with a user using the given
     * access types.
     *
     * @param string   $query       Search string for title/description
     * @param string   $tag         Return only views with this tag
     * @param integer  $limit
     * @param integer  $offset
     * @param string   $sort        Either 'lastchanged', 'ownername', or a column of the view table
     * @param string   $sortdir     Ascending/descending
     * @param array    $accesstypes Types of view access
     * @param integer  $userid      Exclude this user from the results
     *
     */
    public static function shared_to_user($query=null, $tag=null, $limit=null, $offset=0, $sort='lastchanged', $sortdir='desc',
        $accesstypes=null, $userid=null) {

        $sort = array(
            array(
                'column' => $sort,
                'desc'   => $sortdir == 'desc',
            )
        );

        $result = self::view_search(
            $query, null, null, null, $limit, $offset,
            true, $sort, array('portfolio'), false, $accesstypes, $tag,
            null, $userid, true
        );

        if (!$result->count) {
            return $result;
        }

        $viewids = array();
        $collids = array();
        foreach ($result->data as $rec) {
            if ($rec['collid']) {
                $collids[] = $rec['collid'];
            }
            else {
                $viewids[] = $rec['viewid'];
            }
        }

        // Get additional data: number of comments, last commenter
        $viewcommentdata = array();
        if ($viewids) {
            $viewcommentdata = get_records_sql_assoc(
                '
                SELECT
                    acc.onview AS id,
                    a.mtime AS lastcommenttime,
                    a.author AS commentauthor,
                    a.authorname AS commentauthorname,
                    a.id AS commentid,
                    a.description AS commenttext,
                    acc.onview AS lastcommentviewid,
                    (SELECT COUNT(*) FROM {artefact_comment_comment} c WHERE c.onview = acc.onview AND c.deletedby IS NULL AND c.private=0 AND c.hidden=0) AS commentcount
                FROM
                    {artefact_comment_comment} acc
                    inner join {artefact} a
                        on acc.artefact = a.id
                WHERE
                    acc.artefact = (
                        -- Get ID of most recently updated comment on this view
                        -- (NOTE: This will not work in Oracle)
                        SELECT acc2.artefact
                        FROM
                            {artefact_comment_comment} acc2
                            INNER JOIN {artefact} a3
                                ON acc2.artefact = a3.id
                        WHERE
                            acc2.onview = acc.onview
                            AND acc2.deletedby IS NULL
                            AND acc2.private = 0
                            AND acc2.hidden = 0
                        ORDER BY a3.mtime DESC, acc2.artefact ASC
                        LIMIT 1
                    )
                    AND acc.onview IN (' . join(',', array_fill(0, count($viewids), '?')) . ')
                ',
                $viewids
            );
        }
        // Get additional data about comments on collections
        // (Splitting this into a separate query to make the code simpler)
        $collectioncommentdata = array();
        if ($collids) {
            $collectioncommentdata = get_records_sql_assoc(
                '
                -- Get the full information about the artefact that
                -- matches the subquery
                SELECT
                    cv.collection AS collectionid,
                    a.mtime AS lastcommenttime,
                    a.author AS commentauthor,
                    a.authorname AS commentauthorname,
                    a.id AS commentid,
                    a.description AS commenttext,
                    acc.onview as lastcommentviewid,
                    (
                        SELECT COUNT(*)
                        FROM
                            {artefact_comment_comment} c
                            INNER JOIN {collection_view} cv2
                                ON c.onview = cv2.view
                        WHERE
                            cv2.collection = cv.collection
                            AND c.deletedby IS NULL
                            AND c.private=0
                            AND c.hidden=0
                    ) AS commentcount
                FROM
                    {artefact_comment_comment} acc
                    INNER JOIN {artefact} a
                        ON acc.artefact = a.id
                    LEFT OUTER JOIN {collection_view} cv
                        ON cv.view = acc.onview
                WHERE
                    acc.artefact = (
                        -- Get all the comments on all the views that are in the same collection as this artefact
                        -- order them by mtime, and limit to 1
                        SELECT acc2.artefact
                        FROM
                            {artefact_comment_comment} acc2
                            INNER JOIN {artefact} a3
                                ON acc2.artefact = a3.id
                            INNER JOIN {collection_view} cv2
                                ON acc2.onview = cv2.view
                        WHERE
                            acc2.deletedby IS NULL
                            AND acc2.private = 0
                            AND acc2.hidden = 0
                            AND cv2.collection = cv.collection
                        ORDER BY a3.mtime DESC, acc2.artefact ASC
                        LIMIT 1
                    )
                    AND cv.collection IN (' . join(',', array_fill(0, count($collids), '?')) . ')
                ',
                $collids
            );
        }

        // Now that we've retrieved comments counts & last comment data for each collection/view
        // pop it into the data set
        $fields = array('lastcommentviewid', 'lastcommenttime', 'commentauthor', 'commentauthorname', 'commenttext', 'commentid', 'commentcount');
        foreach ($result->data as &$v) {
            $fill = false;
            if ($v['collid']) {
                if (isset($collectioncommentdata[$v['collid']])) {
                    $fill = $collectioncommentdata[$v['collid']];
                }
            }
            else {
                if (isset($viewcommentdata[$v['viewid']])) {
                    $fill = $viewcommentdata[$v['viewid']];
                }
            }

            if ($fill) {
                foreach ($fields as $f) {
                    $v[$f] = $fill->$f;
                }
            }
        }

        return $result;
    }


    /**
     * Get views which have been explicitly shared to a group and are
     * not owned by the group excluding the view in collections
     *
     * @param int $limit
     * @param int $offset
     * @param int $groupid
     * @param boolean $membersonly Only return pages owned by members of the group
     * @param string $orderby Columns to sort by (defaults to (title, id) if empty)
     * @param boolean $hidesubmitted Do not return pages submitted to the group
     * @throws AccessDeniedException
     */
    public static function get_sharedviews_data($limit=10, $offset=0, $groupid, $membersonly = false, $orderby = null, $hidesubmitted = false) {
        global $USER;
        $userid = $USER->get('id');
        require_once(get_config('libroot') . 'group.php');
        if (!group_user_access($groupid)) {
            throw new AccessDeniedException();
        }
        $from = '
            FROM {view} v
            INNER JOIN {view_access} a ON (a.view = v.id)
            INNER JOIN {group_member} m ON (a.group = m.group AND (a.role = m.role OR a.role IS NULL))
        ';
        $where = 'WHERE a.group = ? AND m.member = ? AND (v.group IS NULL OR v.group != ?)
               AND (a.startdate <= CURRENT_TIMESTAMP OR a.startdate IS NULL)
               AND (a.stopdate > CURRENT_TIMESTAMP OR a.stopdate IS NULL)
               AND (v.startdate <= CURRENT_TIMESTAMP OR v.startdate IS NULL)
               AND (v.stopdate > CURRENT_TIMESTAMP OR v.stopdate IS NULL)
               AND NOT EXISTS (SELECT 1 FROM {collection_view} cv WHERE cv.view = v.id)';
        $ph = array($groupid, $userid, $groupid);
        if ($hidesubmitted) {
            $where .= 'AND (v.submittedgroup IS NULL OR v.submittedgroup != ?)';
            $ph[] = $groupid;
        }

        if ($membersonly) {
            $from .= ' INNER JOIN {group_member} m2 ON m2.member = v.owner ';
            $where .= ' AND m2.group = ? ';
            $ph[] = $groupid;
        }

        $count = count_records_sql('SELECT COUNT(DISTINCT(v.id)) ' . $from . $where, $ph);
        if ($orderby === null) {
            $ordersql = ' ORDER BY v.title, v.id';
        }
        else {
            $ordersql = ' ORDER BY ' . $orderby . ', v.id';
        }
        $viewdata = get_records_sql_assoc('
            SELECT DISTINCT v.id, v.title, v.startdate, v.stopdate, v.description, v.group, v.owner, v.ownerformat, v.institution, v.urlid, v.ctime, v.mtime '
                . $from
                . $where
                . $ordersql,
            $ph,
            $offset,
            $limit
        );
        if ($viewdata) {
            View::get_extra_view_info($viewdata, false);
        }
        else {
            $viewdata = array();
        }

        return (object) array(
            'data'   => array_values($viewdata),
            'count'  => $count,
            'limit'  => $limit,
            'offset' => $offset,
        );
    }

    private static function _get_participation_sql($type) {
        $sql = "
            SELECT CASE WHEN coll IS NOT NULL THEN c.name ELSE v.title END AS title,
            CASE WHEN coll IS NOT NULL THEN (SELECT view FROM {collection_view} WHERE collection = coll ORDER BY displayorder ASC LIMIT 1) ELSE vc END AS vid,
            coll AS collid,
            SUM(mc) AS membercommentcount,
            SUM(nmc) AS nonmembercommentcount
            FROM (
                SELECT c.id AS coll,                                                     -- count comments and group by collection or view id
                CASE WHEN c.id IS NULL THEN v.id ELSE c.id END AS vc,
                COUNT(artefact) AS mc, 0 as nmc
                FROM {view} v
                LEFT JOIN (                                                                   -- Get all comment artefacts
                    SELECT acc.artefact, acc.onview FROM {artefact_comment_comment} acc
                    JOIN {artefact} a ON a.id = acc.artefact
                    WHERE EXISTS (                                                       -- Where author is a group member
                        SELECT 1 FROM {group_member} m2
                         WHERE m2.group = ? AND m2.member = a.author
                    )
                ) AS sub ON v.id = sub.onview
                LEFT JOIN {collection_view} cv ON cv.view = v.id
                LEFT JOIN {collection} c ON c.id = cv.collection
                GROUP BY CASE WHEN c.id IS NULL THEN v.id ELSE c.id END, coll
                UNION
                SELECT c.id AS coll,                                                     -- count comments and group by collection or view id
                CASE WHEN c.id IS NULL THEN v.id ELSE c.id END AS vc,
                0 as mc, COUNT(artefact) AS nmc
                FROM {view} v
                LEFT JOIN (                                                                   -- Get all comment artefacts
                    SELECT acc.artefact, acc.onview FROM {artefact_comment_comment} acc
                    JOIN {artefact} a ON a.id = acc.artefact
                    WHERE NOT EXISTS (                                                   -- Where author is not a group member
                        SELECT 1 FROM {group_member} m2
                        WHERE m2.group = ? AND m2.member = a.author
                    )
                ) AS sub ON v.id = sub.onview
                LEFT JOIN {collection_view} cv ON cv.view = v.id
                LEFT JOIN {collection} c ON c.id = cv.collection
                GROUP BY CASE WHEN c.id IS NULL THEN v.id ELSE c.id END, coll
            ) AS foo
            LEFT JOIN {collection} c ON c.id = coll                                      -- group together member and non member results as one row per collection / view
            LEFT JOIN {view} v ON v.id = vc";
        if ($type == 'groupview') {
            $sql .= " WHERE (v.group = ? OR c.group = ?) ";
        }
        else if ($type == 'sharedview') {
            $sql .= " JOIN {view_access} va ON (va.view = CASE WHEN coll IS NOT NULL THEN (
                          SELECT view FROM {collection_view} WHERE collection = coll ORDER BY displayorder ASC LIMIT 1
                      ) ELSE vc END AND va.group = ?)";
            $sql .= " WHERE ((v.group IS NULL OR v.group != ?) AND (c.group IS NULL OR c.group != ?))";
        }
        $sql .= " GROUP BY coll, vc, c.name, v.title";
        return $sql;
    }

    /**
     * Get all group views and its participation info excluding the view in collections
     *
     * @param int $groupid ID of the group
     * @param string $sort in ('title', 'owner', 'membercommentcount', 'nonmembercommentcount')
     * @param string $direction = 'asc' or 'desc'
     * @param int $limit
     * @param int $offset
     * @throws AccessDeniedException if the logged-in user is not the group admin or member
     * @return array(
            'data'   => array(),
            'count'  => $count,
            'limit'  => $limit,
            'offset' => $offset,
        );
     */
    public static function get_participation_groupviews_data($groupid, $sort, $direction, $limit=10, $offset=0) {
        global $USER;
        $userid = $USER->get('id');
        require_once(get_config('libroot') . 'group.php');
        if (!group_user_access($groupid)) {
            throw new AccessDeniedException();
        }

        // Get the count of member and non-member comments for both collections and stand alone pages
        $selectsql = self::_get_participation_sql('groupview');

        $where = array($groupid, $groupid, $groupid, $groupid);
        $count = count_records_sql("SELECT COUNT(*) FROM (" . $selectsql . ") as ct", $where);
        if (in_array($sort, array('title', 'membercommentcount', 'nonmembercommentcount'))
             && in_array($direction, array('asc', 'desc'))) {
            $ordersql = " ORDER BY $sort $direction";
        }
        else {
            $ordersql = " ORDER BY title DESC";
        }

        $viewdata = get_records_sql_assoc($selectsql . $ordersql, $where, $offset, $limit);

        if (!empty($viewdata)) {
            foreach ($viewdata as &$view) {
                $view->id = $view->vid;
                $view->collection = $view->collid;
                $viewobj = new View($view->id);
                $view->url = $viewobj->get_url();
                self::get_view_comment_info($view, $groupid);
            }
        }
        else {
            $viewdata = array();
        }

        return array(
            'data'   => array_values($viewdata),
            'count'  => $count,
            'limit'  => $limit,
            'offset' => $offset,
        );
    }

    /**
     * Get (views + their participation info) which have been explicitly shared to a group and are
     * not owned by the group excluding the view in collections
     *
     * @param int $groupid ID of the group
     * @param string $sort in ('title', 'owner', 'membercommentcount', 'nonmembercommentcount')
     * @param string $direction = 'asc' or 'desc'
     * @param int $limit
     * @param int $offset
     * @throws AccessDeniedException if the logged-in user is not the group admin or member
     * @return array(
            'data'   => array(),
            'count'  => $count,
            'limit'  => $limit,
            'offset' => $offset,
        );
     */
    public static function get_participation_sharedviews_data($groupid, $sort, $direction, $limit=10, $offset=0) {
    global $USER;
        $userid = $USER->get('id');
        require_once(get_config('libroot') . 'group.php');
        if (!group_user_access($groupid)) {
            throw new AccessDeniedException();
        }

        // Get the count of member and non-member comments for both collections and stand alone pages
        $selectsql = self::_get_participation_sql('sharedview');
        $where = array($groupid, $groupid, $groupid, $groupid, $groupid);
        $count = count_records_sql("SELECT COUNT(*) FROM (" . $selectsql . ") as ct", $where);
        if (in_array($sort, array('title', 'membercommentcount', 'nonmembercommentcount'))
             && in_array($direction, array('asc', 'desc'))) {
            $ordersql = " ORDER BY $sort $direction";
        }
        else {
            $ordersql = " ORDER BY title DESC";
        }

        $viewdata = get_records_sql_assoc($selectsql . $ordersql, $where, $offset, $limit);

        if ($viewdata) {
            // Get more info about view comments
            foreach ($viewdata as &$view) {
                $view->id = $view->vid;
                $view->collection = $view->collid;
                $viewobj = new View($view->id);
                $view->url = $viewobj->get_url();
                $view->owner = $viewobj->owner;
                $view->group = $viewobj->group;
                $view->institution = $viewobj->institution;
                if (!empty($view->group)) {
                    $view->groupname = get_field('group', 'name', 'id', $view->group);
                }

                self::get_view_comment_info($view, $groupid);
            }
        }
        else {
            $viewdata = array();
        }

        return array(
            'data'   => array_values($viewdata),
            'count'  => $count,
            'limit'  => $limit,
            'offset' => $offset,
        );
    }

    /**
     * Add comment info to a group view
     *  - mcommenters: number of group member comments
     *  - ecommenters: number of nonmember comments
     *  - mcomments: list of group member comments
     *  - ecomments: list of group nonmember comments
     *  - comments: list of all comments
     *
     * @param $view a view object with $view->id
     * @param $groupid a ID of the group that the view is shared with
     */
    public static function get_view_comment_info(&$view, $groupid) {
        if (isset($view->collection)) {
            $views = get_column('collection_view', 'view', 'collection', $view->collid);
        }
        else {
            $views = array($view->id);
        }

        $extcommenters = 0;
        $membercommenters = 0;
        $extcomments = 0;
        $membercomments = 0;
        $commenters = array();

        foreach ($views as $v) {
            $viewcomments = get_records_sql_array('
                SELECT
                    a.id, a.author, a.authorname, a.ctime, a.mtime, a.description, a.group,
                    c.private, c.deletedby, c.requestpublic, c.rating, c.lastcontentupdate,
                    u.username, u.firstname, u.lastname, u.preferredname, u.email, u.staff, u.admin,
                    u.deleted, u.profileicon, u.urlid
                FROM {artefact} a
                INNER JOIN {artefact_comment_comment} c ON a.id = c.artefact
                    LEFT JOIN {usr} u ON a.author = u.id
                WHERE c.onview = ?'
                , array($v));

            if ($viewcomments && is_array($viewcomments)) {
                foreach ($viewcomments as $c) {
                    if (empty($c->author)) {
                        if (!isset($commenters[$c->authorname])) {
                            $commenters[$c->authorname] = array();
                        }
                        $commenters[$c->authorname]['commenter'] = $c->authorname;
                        $commenters[$c->authorname]['count'] = (isset($commenters[$c->authorname]['count']) ? $commenters[$c->authorname]['count'] + 1 : 1);
                        if ($commenters[$c->authorname]['count'] == 1) {
                            $extcommenters++;
                        }
                        $extcomments++;
                    }
                    else {
                        if (!isset($commenters[$c->author])) {
                            $commenters[$c->author] = array();
                        }
                        $commenters[$c->author]['commenter'] = (int) $c->author;
                        $commenters[$c->author]['member'] = group_user_access($groupid, $c->author);
                        $commenters[$c->author]['count'] = (isset($commenters[$c->author]['count']) ? $commenters[$c->author]['count'] + 1 : 1);
                        if (empty($commenters[$c->author]['member'])) {
                            if ($commenters[$c->author]['count'] == 1) {
                                $extcommenters++;
                            }
                            $extcomments++;
                        }
                        else {
                            if ($commenters[$c->author]['count'] == 1) {
                                $membercommenters++;
                            }
                            $membercomments++;
                        }
                    }
                }
            }
        }

        $view->mcommenters = $membercommenters;
        $view->ecommenters = $extcommenters;
        $view->mcomments = $membercomments;
        $view->ecomments = $extcomments;
        $view->comments = $commenters;
        $view->viewcount = count($views);
    }

    /**
     * This function renders a list of participation shared views as html
     *
     * @param array views = array(
                        'data'   => array of view objects,
                        'count'  => $count,
                        'limit'  => $limit,
                        'offset' => $offset,
                    )
     * @param string template
     * @param array options
     * @param array pagination
     */
    public function render_participation_views($views, $template, $pagination) {
        $smarty = smarty_core();
        $smarty->assign('itemcount', (!empty($views['data']) ? count($views['data']) : false));
        $smarty->assign('items', $views['data']);

        $views['tablerows'] = $smarty->fetch($template);

        if ($views['limit'] && $pagination) {
            $pagination = build_pagination(array(
                'id' => $pagination['id'],
                'class' => 'center',
                'datatable' => $pagination['datatable'],
                'url' => $pagination['baseurl'],
                'jsonscript' => $pagination['jsonscript'],
                'setlimit' => $pagination['setlimit'],
                'count' => $views['count'],
                'limit' => $views['limit'],
                'offset' => $views['offset'],
                'numbersincludefirstlast' => false,
                'resultcounttextsingular' => $pagination['resultcounttextsingular'] ? $pagination['resultcounttextsingular'] : get_string('result'),
                'resultcounttextplural' => $pagination['resultcounttextplural'] ? $pagination['resultcounttextplural'] :get_string('results'),
            ));
            $views['pagination'] = $pagination['html'];
            $views['pagination_js'] = $pagination['javascript'];
        }
        return $views;
    }

    /**
     * Get collections which have been explicitly shared to a group and are
     * not owned by the group
     * @param integer $limit
     * @param integer $offset
     * @param integer $groupid
     * @param boolean $membersonly Only return collections owned by members of the gorup
     * @param array $sort Columns to sort by (defaults to (title, id) if empty)
     * @param boolean $hidesubmitted Do not return collections submitted to the group
     * @return array of collections
     */
    public static function get_sharedcollections_data($limit=10, $offset=0, $groupid, $membersonly = false, $sort = null, $hidesubmitted = false) {
        global $USER;

        $userid = $USER->get('id');
        require_once(get_config('libroot') . 'group.php');

        if (!group_user_access($groupid)) {
            throw new AccessDeniedException();
        }

        $from = '
            FROM {collection} c
                INNER JOIN {collection_view} cv ON (cv.collection = c.id)
                INNER JOIN {view_access} a ON (a.view = cv.view)
                INNER JOIN {view} v ON (cv.view = v.id)
                INNER JOIN {group_member} m ON (a.group = m.group AND (a.role = m.role OR a.role IS NULL)) ';
        $where = ' WHERE
                a.group = ?
                AND m.member = ?
                AND (c.group IS NULL OR c.group != ?)
                AND (a.startdate <= CURRENT_TIMESTAMP OR a.startdate IS NULL)
                AND (a.stopdate > CURRENT_TIMESTAMP OR a.stopdate IS NULL)
                AND (v.startdate <= CURRENT_TIMESTAMP OR v.startdate IS NULL)
                AND (v.stopdate > CURRENT_TIMESTAMP OR v.stopdate IS NULL)
                AND (cv.displayorder = 0 OR cv.displayorder IS NULL)
        ';
        $ph = array($groupid, $userid, $groupid);
        if ($membersonly) {
            $from .= ' INNER JOIN {group_member} m2 ON m2.member = c.owner ';
            $where .= ' AND m2.group = ? ';
            $ph[] = $groupid;
        }

        if ($hidesubmitted) {
            $where .= 'AND (v.submittedgroup IS NULL OR v.submittedgroup != ?)';
            $ph[] = $groupid;
        }

        $count = count_records_sql('SELECT COUNT(DISTINCT c.id) ' . $from . $where, $ph);
        // NOTE: If you change the number of columns here you may need to change the numeric
        // column identifier (10) in the sortorder section
        // ALSO NOTE: Some columns have been duplicated or added here, in order to make
        // this method return the same fields as get_sharedviews_data, so that they can
        // use the same templates.
        $select = 'SELECT c.id, c.name, c.name as title,
                c.ctime, c.description, c.owner, c.group, c.institution,
                0 as template, ';
        $select .= 'GREATEST(
                c.mtime,
                (
                    SELECT MAX(v.mtime)
                    FROM
                        {view} v
                        INNER JOIN {collection_view} cv
                            ON v.id=cv.view
                    WHERE cv.collection=c.id
                )
        ) as mtime';
        $orderby = ' ORDER BY ';

        if (!is_array($sort)) {
            $sort = array(array('type'=>'name'));
        }
        foreach ($sort as $sortitem) {
            if (isset($sortitem['type'])) {
                switch (strtolower($sortitem['type'])) {
                    case 'lastchanged':
                        $sortcol = '10'; // The "most recently changed view" mtime
                        break;
                    case 'name':
                    default:
                        $sortcol = 'c.name';
                        break;
                }
            }
            else {
                $sortcol = $sortitem['column'];
            }

            $orderby .= "$sortcol";
            if (!empty($sortitem['desc'])) {
                $orderby .= " DESC";
            }
            $orderby .= ', ';
        }
        // Lastly order by ID to break any ties
        $orderby .= 'c.id';

        $collectiondata = get_records_sql_assoc(
            $select
            . $from
            . $where
            . $orderby,
            $ph, $offset, $limit
        );

        if ($collectiondata) {
            View::get_extra_collection_info($collectiondata);
        }
        else {
            $collectiondata = array();
        }

        return (object) array(
            'data'   => array_values($collectiondata),
            'count'  => $count,
            'limit'  => $limit,
            'offset' => $offset,
        );
    }

    public static function get_extra_view_info(&$viewdata, $getartefacts=true, $gettags=true) {
        if ($viewdata) {
            // Get view owner details for display
            $owners = array();
            $groups = array();
            $institutions = array();
            $viewids = array();

            foreach ($viewdata as $k=> $v) {
                if (!is_object($v)) {
                    $v = (object) $v;
                }
                if (is_null($v->id)) {
                    continue;
                }
                else {
                    $viewid = (isset($v->viewid) && !empty($v->viewid)) ? $v->viewid : $v->id;
                    $viewids[] = $viewid;
                }
                if (!empty($v->owner) && !isset($owners[$v->owner])) {
                    $owners[$v->owner] = (int)$v->owner;
                }
                else if (!empty($v->group) && !isset($groups[$v->group])) {
                    $groups[$v->group] = (int)$v->group;
                }
                else if (!empty($v->institution) && !isset($institutions[$v->institution])) {
                    $institutions[$v->institution] = $v->institution;
                }
            }

            $viewidlist = join(',', array_map('intval', $viewids));
            if ($getartefacts) {
                $artefacts = get_records_sql_array('SELECT va.view, va.artefact, a.title, a.artefacttype, t.plugin
                    FROM {view_artefact} va
                    INNER JOIN {artefact} a ON va.artefact = a.id
                    INNER JOIN {artefact_installed_type} t ON a.artefacttype = t.name
                    WHERE va.view IN (' . $viewidlist . ')
                    GROUP BY va.view, va.artefact, a.title, a.artefacttype, t.plugin
                    ORDER BY a.title, va.artefact', array());
                if ($artefacts) {
                    foreach ($artefacts as $artefactrec) {
                        safe_require('artefact', $artefactrec->plugin);
                        $classname = generate_artefact_class_name($artefactrec->artefacttype);
                        $artefactobj = new $classname(0, array('title' => $artefactrec->title));
                        $artefactobj->set('dirty', false);
                        if (!$artefactobj->in_view_list()) {
                            continue;
                        }
                        $artname = $artefactobj->display_title(30);
                        if (strlen($artname)) {
                            $viewdata[$artefactrec->view]->artefacts[] = array('id'    => $artefactrec->artefact,
                                                                               'title' => $artname);
                        }
                    }
                }
            }
            if ($gettags && !empty($viewidlist)) {
                $tags = get_records_select_array('tag', "resourcetype = 'view' AND resourceid IN ('" . join("','", array_map('intval', $viewids)) . "')");
                if ($tags) {
                    foreach ($tags as &$tag) {
                        if (isset($viewdata[$tag->resourceid])) {
                            $viewdata[$tag->resourceid]->tags[] = $tag->tag;
                        }
                        else {
                            // Need to find the views to add it to
                            foreach ($viewdata as $k => $v) {
                                if (is_object($v)) {
                                    if (!isset($v->id) || is_null($v->id)) {
                                        continue;
                                    }
                                    $viewid = (isset($v->viewid) && !empty($v->viewid)) ? $v->viewid : $v->id;
                                    if ($viewid == $tag->resourceid) {
                                        $viewdata[$k]->tags[] = $tag->tag;
                                    }
                                }
                                else {
                                    if (!isset($v['id'])) {
                                        continue;
                                    }
                                    $viewid = (isset($v['viewid']) && !empty($v['viewid'])) ? $v['viewid'] : $v['id'];
                                    if ($viewid == $tag->resourceid) {
                                        $viewdata[$k]['tags'][] = $tag->tag;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if (!empty($owners)) {
                global $USER;
                $userid = $USER->get('id');
                $fields = array(
                    'id', 'username', 'firstname', 'lastname', 'preferredname', 'admin', 'staff', 'studentid', 'email',
                    'profileicon', 'urlid', 'suspendedctime',
                );
                if (count($owners) == 1 && isset($owners[$userid])) {
                    $owners = array($userid => new stdClass());
                    foreach ($fields as $f) {
                        $owners[$userid]->$f = $USER->get($f);
                    }
                }
                else {
                    $owners = get_records_select_assoc(
                        'usr', 'id IN (' . join(',', array_fill(0, count($owners), '?')) . ')', $owners, '',
                        join(',', $fields)
                    );
                }
            }
            if (!empty($groups)) {
                require_once('group.php');
                $groups = get_records_select_assoc('group', 'id IN (' . join(',', $groups) . ')', null, '', 'id,name,urlid');
            }
            if (!empty($institutions)) {
                $institutions = get_records_assoc('institution', '', '', '', 'name,displayname');
                $institutions['mahara']->displayname = get_config('sitename');
            }

            $wwwroot = get_config('wwwroot');
            $needsubdomain = get_config('cleanurlusersubdomains');

            foreach ($viewdata as &$v) {
                if (!is_object($v)) {
                    $v = (object) $v;
                }
                if (is_null($v->id)) {
                    $v = (array)$v;
                    continue;
                }
                $viewid = (isset($v->viewid) && !empty($v->viewid)) ? $v->viewid : $v->id;
                $v->anonymous = FALSE;
                if (!empty($v->owner)) {
                    $v->sharedby = View::owner_name($v->ownerformat, $owners[$v->owner]);
                    $v->user = $owners[$v->owner];

                    // Get a real view object so we can do the checks.
                    $view_obj = new View($viewid);
                    $v->anonymous = $view_obj->is_anonymous();
                    $v->staff_or_admin = $view_obj->is_staff_or_admin_for_page();
                }
                else if (!empty($v->group)) {
                    $v->sharedby = $groups[$v->group]->name;
                    $v->groupdata = $groups[$v->group];
                    $v->groupdata->homeurl = group_homepage_url($v->groupdata);
                }
                else if (!empty($v->institution)) {
                    $v->sharedby = $institutions[$v->institution]->displayname;
                }
                $v = (array)$v;

                // Now that we have the owner & group records, create a temporary View object
                // so that we can use display_title_editing and get_url methods.
                $view = new View($viewid);
                $view->set('dirty', false);
                $v['displaytitle'] = $view->display_title_editing();
                $v['url'] = $view->get_url(false);
                $v['fullurl'] = $needsubdomain ? $view->get_url(true) : ($wwwroot . $v['url']);
                if ($view->id) {
                    $v['collection'] = $view->get_collection();
                }
                if ($view->get('coverimage') && ($coverimage = get_record('artefact', 'id', $view->get('coverimage')))) {
                    safe_require('artefact', 'file');
                    $v['coverimageurl'] = ArtefactTypeImage::get_coverimage_url(array('id' => $coverimage->id));
                    if ($coverimage->description) {
                        $v['coverimagedescription'] = $coverimage->description;
                    }
                }
            }
        }
    }

    /**
     * Get more info for the collections: owner, url, tags, views
     *
     * @param array a list of collections $collectiondata
     * @return array updated collection data
     */
    public static function get_extra_collection_info(&$collectiondata, $gettags=true, $useid = 'id') {
        if ($collectiondata) {
            // Get view owner details for display
            $owners = array();
            $groups = array();
            $institutions = array();

            foreach ($collectiondata as $c) {
                if (!is_object($c)) {
                    $c = (object) $c;
                }
                if (empty($c->{$useid})) {
                    continue;
                }
                if (!empty($c->owner) && !isset($owners[$c->owner])) {
                    $owners[$c->owner] = (int)$c->owner;
                }
                else if (!empty($c->group) && !isset($groups[$c->group])) {
                    $groups[$c->group] = (int)$c->group;
                }
                else if (!empty($c->institution) && !isset($institutions[$c->institution])) {
                    $institutions[$c->institution] = $c->institution;
                }
            }
            if ($gettags) {
                $collectionidlist = join("','", array_map('intval', array_keys($collectiondata)));
                $tags = get_records_select_array('tag', "resourcetype = 'collection' AND resourceid IN ('" . $collectionidlist . "')");
                if ($tags) {
                    foreach ($tags as &$tag) {
                        $collectiondata[$tag->resourceid]->tags[] = $tag->tag;
                    }
                }
            }
            if (!empty($owners)) {
                global $USER;
                $userid = $USER->get('id');
                $fields = array(
                    'id', 'username', 'firstname', 'lastname', 'preferredname', 'admin', 'staff', 'studentid', 'email',
                    'profileicon', 'urlid', 'suspendedctime',
                );
                if (count($owners) == 1 && isset($owners[$userid])) {
                    $owners = array($userid => new stdClass());
                    foreach ($fields as $f) {
                        $owners[$userid]->$f = $USER->get($f);
                    }
                }
                else {
                    $owners = get_records_select_assoc(
                        'usr', 'id IN (' . join(',', array_fill(0, count($owners), '?')) . ')', $owners, '',
                        join(',', $fields)
                    );
                }
            }
            if (!empty($groups)) {
                $groups = get_records_select_assoc('group', 'id IN (' . join(',', $groups) . ')', null, '', 'id,name,urlid');
            }
            if (!empty($institutions)) {
                $institutions = get_records_assoc('institution', '', '', '', 'name,displayname');
                $institutions['mahara']->displayname = get_config('sitename');
            }

            $wwwroot = get_config('wwwroot');
            $needsubdomain = get_config('cleanurlusersubdomains');

            foreach ($collectiondata as &$c) {
                if (!is_object($c)) {
                    $c = (object) $c;
                }
                if (empty($c->{$useid})) {
                    $c = (array)$c;
                    continue;
                }
                if (!empty($c->owner)) {
                    $c->sharedby = display_name($owners[$c->owner]);
                    $c->user = $owners[$c->owner];
                }
                else if (!empty($c->group)) {
                    $c->sharedby = $groups[$c->group]->name;
                    $c->groupdata = $groups[$c->group];
                    $c->groupdata->homeurl = group_homepage_url($c->groupdata);
                }
                else if (!empty($c->institution)) {
                    $c->sharedby = $institutions[$c->institution]->displayname;
                }

                $c = (array)$c;

                // Now that we have the owner & group records, create a temporary Collection object
                // so that we can use get_url method.
                require_once(get_config('libroot') . 'collection.php');

                if (!empty($c[$useid])) {
                    $collection = new Collection($c[$useid]);
                }
                else {
                    $collection = new Collection(0, $c);
                }
                if ($collection->get('coverimage') && ($coverimage = get_record('artefact', 'id', $collection->get('coverimage')))) {
                    safe_require('artefact', 'file');
                    $c['coverimageurl'] = ArtefactTypeImage::get_coverimage_url(array('id' => $coverimage->id));
                    if ($coverimage->description) {
                        $c['coverimagedescription'] = $coverimage->description;
                    }
                }

                $views = $collection->views();
                if (!empty($views)) {
                    $c['url'] = $collection->get_url(false);
                    $c['fullurl'] = $needsubdomain ? $collection->get_url(true, false, $firstview) : ($wwwroot . $c['url']);

                    // HACK: Find out whether this collection is anonymous
                    // (based on whether its first view is anonymous)
                    if (!empty($c->owner)) {
                        $c['anonymous'] = $firstview->anonymous;
                        $c['staff_or_admin'] = $firstview->is_staff_or_admin_for_page();
                    }
                }
            }
        }
    }

    public static function set_nav($group, $institution, $share=false, $collection=false, $submenu=true) {
        if ($group) {
            define('MENUITEM', 'engage/index');
            if ($submenu) {
                define('MENUITEM_SUBPAGE', $share ? 'share' : 'views');
                define('GROUP', $group);
            }
        }
        else if ($institution == 'mahara') {
            define('ADMIN', 1);
            define('MENUITEM', $share ? 'configsite/share' : 'configsite/siteviews');
        }
        else if ($institution) {
            define('INSTITUTIONALADMIN', 1);
            define('MENUITEM', $share ? 'manageinstitutions/share' : 'manageinstitutions/institutionviews');
        }
        else if ($collection) {
            define('MENUITEM', 'create/views');
        }
        else {
            define('MENUITEM', $share ? 'share/sharedbyme' : 'create/views');
        }
    }

    public function set_edit_nav() {
        if ($this->group) {
            // Don't display the group nav; 5 levels of menu is too many
            define('MENUITEM', 'engage/index');
            define('MENUITEM_SUBPAGE', 'views');
            define('GROUP', $this->group);
            define('NOGROUPMENU', 1);
        }
        else if ($this->institution == 'mahara') {
            define('ADMIN', 1);
            define('MENUITEM', 'configsite/siteviews');
        }
        else if ($this->institution) {
            define('INSTITUTIONALADMIN', 1);
            define('MENUITEM', 'manageinstitutions/institutionviews');
        }
        else {
            define('MENUITEM', 'create/views');
        }
    }

    public function ownership() {
        if ($this->group) {
            return array('type' => 'group', 'id' => $this->group);
        }
        if ($this->owner) {
            return array('type' => 'user', 'id' => $this->owner);
        }
        if ($this->institution) {
            return array('type' => 'institution', 'id' => $this->institution);
        }
        return null;
    }


    public function copy_contents($template, &$artefactcopies) {

        $this->set('lockblocks', $template->get('lockblocks'));
        if ($template->get('template') == self::SITE_TEMPLATE
            && $template->get('type') == 'portfolio') {
            $this->set('description', '');
            $this->set('instructions', '');
        }
        else {
            require_once('embeddedimage.php');
            $this->set('description', EmbeddedImage::prepare_embedded_images($this->copy_setting_info($template, $artefactcopies, 'description'), 'description', $this->get('id')));
            $this->set('instructions', EmbeddedImage::prepare_embedded_images($this->copy_setting_info($template, $artefactcopies, 'instructions'), 'instructions', $this->get('id')));
        }
        if ($template->get('coverimage')) {
            $this->set('coverimage', $this->copy_setting_coverimage($template, $artefactcopies));
        }
        $this->set('tags', $template->get('tags'));

        // If the template uses the gridstack layout
        if ($template->uses_new_layout()) {
            // then recover info from block_instance_dimension too
            $sql = "
            SELECT * FROM {block_instance} bi
            INNER JOIN {block_instance_dimension} bd
            ON bi.id = bd.block
            WHERE bi.view = ?";

            $blocks = get_records_sql_array($sql, array($template->get('id')));
        }
        else {
            // check if description needs to be moved to a text block
            $newdescriptionblock = 0;
            if ($description = $this->get('description')) {
                $simpletextdescription = can_extract_description_text($description);
                if ($simpletextdescription) {
                    // remove tags from description text
                    $this->set('description', $simpletextdescription);
                }
                else {
                    // add a text block with description
                    $newdescriptionblock = 1;
                    $this->description_to_block();
                    $this->set('description', '');
                }
            };

            require_once(get_config('libroot') . 'gridstacklayout.php');
            // get blocks in old layout
            $blocks = get_records_array('block_instance', 'view', $template->get('id'));
            // translate layout
            $oldlayoutcontent = get_blocks_in_old_layout($template->get('id'));
            $newlayoutcontent = translate_to_new_layout($oldlayoutcontent, $newdescriptionblock);
            foreach ($newlayoutcontent as $block) {
                $dimensions[$block['block']] = $block;
            }
            foreach ($blocks as $block) {
                $block->positionx = $dimensions[$block->id]['positionx'];
                $block->positiony = $dimensions[$block->id]['positiony'];
                $block->width = $dimensions[$block->id]['width'];
                $block->height = $dimensions[$block->id]['height'];
            }
        }

        $numcopied = array('blocks' => 0);

        if ($blocks) {
            foreach ($blocks as $b) {
                if (safe_require('blocktype', $b->blocktype, 'lib.php', 'require_once', true) !== false) {
                    $oldblock = new BlockInstance($b->id, $b);
                    if ($oldblock->copy($this, $template, $artefactcopies)) {
                        $numcopied['blocks']++;
                    }
                }
            }
        }
        // Go back and fix up artefact references in the new artefacts so
        // they also point to new artefacts.
        if ($artefactcopies) {
            foreach ($artefactcopies as $oldid => $copyinfo) {
                $a = artefact_instance_from_id($copyinfo->newid);
                $a->update_artefact_references($this, $template, $artefactcopies, $oldid);
                $a->commit();
            }
        }
        $numcopied['artefacts'] = count($artefactcopies);
        return $numcopied;
    }

    /**
     * Generates a title for a newly created View
     */
    private static function new_title($title, $ownerdata) {
        $extText = get_string('version.', 'mahara');
        $temptitle = preg_split('/ '. $extText . '[0-9]$/', $title);
        $title = $temptitle[0];

        $taken = get_column_sql('
            SELECT title
            FROM {view}
            WHERE ' . self::owner_sql($ownerdata) . "
                AND title LIKE ? || '%'", array($title));

        $ext = '';
        $i = 1;
        if ($taken) {
            while (in_array($title . $ext, $taken)) {
                $ext = ' ' . $extText . ++$i;
            }
        }
        return $title . $ext;
    }

    /**
     * Copy the description/instructions field of the view template
     * and its embedded image artefacts
     *
     * @param View $template the view template
     * @param array &$artefactcopies the artefact mapping
     * @param string $type contains 'description' or 'instructions'
     * @return string updated field
     */
    private function copy_setting_info(View $template, array &$artefactcopies, $type) {
        safe_require('artefact', 'file');
        $new_setting_field = $template->get($type);
        if (!empty($new_setting_field)
            && strpos($new_setting_field, 'artefact/file/download.php?file=') !== false) {
            // Get all possible embedded artefacts
            $artefactids = array_unique(artefact_get_references_in_html($new_setting_field));
            // Copy these image artefacts
            foreach ($artefactids as $aid) {
                try {
                    $a = artefact_instance_from_id($aid);
                }
                catch (Exception $e) {
                    continue;
                }
                if ($a instanceof ArtefactTypeImage) {
                    $artefactcopies[$aid] = (object) array(
                        'oldid' => $aid,
                        'oldparent' => $a->get('parent')
                    );
                    $artefactcopies[$aid]->newid = $a->copy_for_new_owner(
                        $this->get('owner'),
                        $this->get('group'),
                        $this->get('institution')
                    );
                }
            }
            // Update the image urls in the settings field
            if (!empty($artefactcopies)) {
                $regexp = array();
                $replacetext = array();
                foreach ($artefactcopies as $oldaid => $newobj) {
                    // Change the old image id to the new one
                    $regexp[] = '#<img([^>]+)src=("|\\")'
                            . preg_quote(
                                    get_config('wwwroot')
                                    . 'artefact/file/download.php?file=' . $oldaid
                            )
                            . '(&|&amp;)embedded=1([^"]*)"#';
                    $replacetext[] = '<img$1src="'
                            . get_config('wwwroot')
                            . 'artefact/file/download.php?file=' . $newobj->newid
                            . '&embedded=1"';
                }
                $new_setting_field = preg_replace($regexp, $replacetext, $new_setting_field);
            }
        }
        return $new_setting_field;
    }

    /**
     * Copy the cover image of the view template
     *
     * @param View $template the view template
     * @param array &$artefactcopies the artefact mapping
     * @return int new image artefact id
     */
    private function copy_setting_coverimage(View $template, array &$artefactcopies) {
        safe_require('artefact', 'file');
        $coverimageid = $template->get('coverimage');
        if ($coverimageid) {
            try {
                $a = artefact_instance_from_id($coverimageid);
                if ($a instanceof ArtefactTypeImage) {
                $artefactcopies[$coverimageid] = (object) array(
                  'oldid' => $coverimageid,
                  'oldparent' => $a->get('parent')
                );
                $artefactcopies[$coverimageid]->newid = $a->copy_for_new_owner(
                  $this->get('owner'),
                  $this->get('group'),
                  $this->get('institution')
                );
                }
                return $artefactcopies[$coverimageid]->newid;
            }
            catch (Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Get a simplified name for this view for use in a url, which must be unique for a
     * a given owner.
     */
    public static function new_urlid($desired, $ownerdata) {
        $maxlen = 100;
        $desired = strtolower(substr($desired, 0, $maxlen));
        $taken = get_column_sql('
            SELECT urlid
            FROM {view}
            WHERE urlid LIKE ?
                AND ' . self::owner_sql($ownerdata),
            array(substr($desired, 0, $maxlen - 6) . '%')
        );
        if (!$taken) {
            return $desired;
        }

        $i = 1;
        $newname = substr($desired, 0, $maxlen - 2) . '-1';
        while (in_array($newname, $taken)) {
            $i++;
            $newname = substr($desired, 0, $maxlen - strlen($i) - 1) . '-' . $i;
        }
        return $newname;
    }

    public static function get_templatesearch_data(&$search) {
        $search->sort = (isset($search->sort)) ? $search->sort : null; // for backwards compatibility
        $results = self::view_search($search->query, $search->ownerquery, null, $search->copyableby, $search->limit, $search->offset, true, $search->sort, null, true);
        $oldcollid = null;
        foreach ($results->data as &$r) {
            if (!empty($r['groupdata'])) {
                $r['groupdata']->homeurl = group_homepage_url($r['groupdata'], true, true);
            }
            if (!empty($search->sort)) {
                $collid = ($r['collid'] == $oldcollid) ? null : $r['collid'];
            }
            else {
                $collid = $r['collid'];
            }
            $r['form'] = pieform(create_view_form($search->copyableby->group, $search->copyableby->institution, $r['id'], $collid));
            $oldcollid = $r['collid'];
        }

        $params = array();
        if (isset($search->query) && ($search->query != '')) {
            $params['viewquery'] = $search->query;
        }
        if (isset($search->ownerquery) && ($search->ownerquery != '')) {
            $params['ownerquery'] = $search->ownerquery;
        }
        if (!empty($search->group)) {
            $params['group'] = $search->group;
        }
        if (!empty($search->institution)) {
            $params['institution'] = $search->institution;
        }
        if (!empty($search->collection)) {
            $params['searchcollection'] = $search->collection;
        }
        $params['limit'] = $search->limit;

        $smarty = smarty_core();
        $smarty->assign('results', $results->data);
        $search->html = $smarty->fetch('view/templatesearchresults.tpl');
        $search->count = $results->count;

        $search->pagination = build_pagination(array(
            'id' => 'templatesearch_pagination',
            'class' => 'center',
            'url' => get_config('wwwroot') . 'view/choosetemplate.php' . (!empty($params) ? ('?' . http_build_query($params)) : ''),
            'count' => $results->count,
            'limit' => $search->limit,
            'offset' => $search->offset,
            'jumplinks' => 6,
            'numbersincludeprevnext' => 2,
            'offsetname' => 'viewoffset',
            'firsttext' => '',
            'previoustext' => '',
            'nexttext' => '',
            'lasttext' => '',
            'setlimit' => true,
            'resultcounttextsingular' => get_string('view', 'view'),
            'resultcounttextplural' => get_string('views', 'view'),
        ));
    }

    public static function new_token($viewid, $visible=1) {
        if (!$visible) {
            // Currently it only makes sense to have one invisible key per view.
            // They are only used during view submission, and a view can only be
            // submitted to one group or remote host at any one time.
            delete_records_select('view_access', 'view = ? AND token IS NOT NULL AND visible = 0', array($viewid));
        }

        $data = new stdClass();
        $data->view    = $viewid;
        $data->visible = (int) $visible;
        $data->token   = get_random_key(20);
        $data->ctime   = db_format_timestamp(time());

        while (record_exists('view_access', 'token', $data->token)) {
            $data->token = get_random_key(20);
        }
        $vaid = insert_record('view_access', $data, 'id', true);
        handle_event('updateviewaccess', array(
            'id' => $vaid,
            'eventfor' => 'token',
            'parentid' => $viewid,
            'parenttype' => 'view',
            'rules' => $data)
        );
        return $data;
    }

    /**
     * Retrieve the invisible key for this view, if there is one. (A view can only have one
     * invisible key, because it can only be submitted to one place at a time.)
     * @param int $viewid
     * @return mixed Returns a boolean FALSE if there is no invisible token, a data object if there is one
     */
    public static function get_invisible_token($viewid) {
        return get_record_select('view_access', 'view = ? AND token IS NOT NULL AND visible = 0', array($viewid), 'view, visible, token, ctime');
    }

    public function owner_link() {
        if ($this->owner) {
            return profile_url($this->get_owner_object());
        }
        else if ($this->group) {
            return group_homepage_url($this->get_group_object());
        }
        else if ($this->institution) {
            return get_config('wwwroot') . 'institution/index.php?institution=' . $this->institution;
        }
        return null;
    }

    public function display_title($long=true, $titlelink=true, $includeowner=true) {
        if ($this->type == 'profile') {
            $title = hsc(display_name($this->owner, null, true));
            if ($long) {
                return get_string('usersprofile', 'mahara', $title);
            }
            return $title;
        }
        if ($this->type == 'dashboard') {
            return get_string('dashboardviewtitle', 'view');
        }

        $ownername = hsc($this->formatted_owner());

        if ($this->type == 'grouphomepage') {
            return get_string('aboutgroup', 'group', $ownername);
        }

        $ownerlink = null;
        if ($includeowner) {
            $ownerlink = $this->owner_link();
        }

        if ($titlelink) {
            $title = '<a href="' . $this->get_url() . '">' . hsc($this->title) . '</a>';
        }
        else {
            $title = hsc($this->title);
        }

        if (isset($ownerlink)) {
            return get_string('viewtitleby', 'view', $title, $ownerlink, $ownername);
        }

        return $title;
    }

    public function display_author() {
        $view = null;

        if (!empty($this->owner)) {
            $userobj = new User();
            $userobj->find_by_id($this->owner);
            $view = $userobj->get_profile_view();

            // Hide author if profile isn't visible to user
            if (!$view || !can_view_view($view)) {
                return null;
            }
        }
        else if (!empty($this->group)) {
            $view = group_get_homepage_view($this->group);

            // Hide author if profile isn't visible to user
            if (!$view || !can_view_view($view)) {
                return null;
            }
        }
        else if (!empty($this->institution)) {
            global $USER;
            if (!$USER->is_logged_in() || (
                    !get_field('institution', 'registerallowed', 'name', $this->institution) &&
                    !$USER->in_institution($this->institution) &&
                    !$USER->get('admin'))) {
                return null;
            }
        }

        $ownername = hsc($this->formatted_owner());
        $ownerlink = hsc($this->owner_link());
        return get_string('viewauthor', 'view', $ownerlink, $ownername);
    }

    public function display_title_editing() {
        if ($this->type == 'profile') {
            return get_string('profileviewtitle', 'view');
        }
        if ($this->type == 'dashboard') {
            return get_string('dashboardviewtitle', 'view');
        }
        if ($this->type == 'grouphomepage') {
            return get_string('Grouphomepage', 'view');
        }
        return $this->title;
    }

    public function visit_message() {
        $visitcountstart = max(get_config('stats_installation_time'), $this->ctime);
        $visitcountend = get_config('viewloglatest');
        if ($visitcountstart && $visitcountend && $visitcountstart < $visitcountend) {
             return get_string(
                'viewvisitcount',
                'view',
                $this->visits,
                trim(format_date(strtotime($visitcountstart), 'strftimedate')),
                trim(format_date(strtotime($visitcountend), 'strftimedate'))
            );
        }
    }

    /**
     * returns a formatted string about the created or last updated date and time
     */
    public function lastchanged_message() {
        return (($this->ctime != $this->mtime) ? get_string('Updatedon', 'view') : get_string('Createdon', 'view')) . ' ' . format_date(strtotime($this->mtime));
    }

    /**
     * after editing the view, redirect back to the appropriate place
     */
    public function post_edit_redirect($new=false) {
        if ($new) {
            $redirecturl = '/view/access.php?id=' . $this->get('id');
        }
        else {
            if ($this->get('group')) {
                if ($this->get('type') == 'grouphomepage') {
                    $redirecturl = group_homepage_url(get_group_by_id($this->get('group'), true));
                }
                else {
                    $redirecturl = '/view/groupviews.php?group='.$this->get('group');
                }
            }
            else if ($this->get('institution')) {
                $redirecturl = '/view/institutionviews.php?institution=' . $this->get('institution');
            }
            else {
                $redirecturl = '/view/index.php';
            }
        }
        redirect($redirecturl);
    }


    /**
     * Makes a URL for a view page
     *
     * @param bool $full return a full url
     * @param bool $useid ignore clean url settings and always return a url with an id in it
     *
     * @return string
     */
    public function get_url($full=true, $useid=false) {
        // No url for a default site template
        if ($this->template == self::SITE_TEMPLATE) {
            return '';
        }
        else if ($this->type == 'profile') {
            if (!$useid) {
                return profile_url($this->get_owner_object(), $full);
            }
            $url = 'user/view.php?id=' . (int) $this->owner;
        }
        else if ($this->type == 'dashboard') {
            $url = '';
        }
        else if ($this->type == 'grouphomepage') {
            if (!$useid && $this->get('group')) {
                return group_homepage_url($this->get_group_object(), $full);
            }
            $url = 'group/view.php?id=' . $this->group;
        }
        else if (!$useid && !is_null($this->urlid) && get_config('cleanurls')) {
            if ($this->owner &&
                ($this->get_owner_object() instanceof User && !is_null($this->get_owner_object()->get('urlid'))
                 || $this->get_owner_object() instanceof stdClass && !is_null($this->get_owner_object()->urlid))
              ) {
                return profile_url($this->ownerobj, $full) . '/' . $this->urlid;
            }
            else if ($this->group && !is_null($this->get_group_object()->urlid)) {
                return group_homepage_url($this->groupobj, $full) . '/' . $this->urlid;
            }
        }

        if (!isset($url)) {
            $url = 'view/view.php?id=' . (int) $this->id;
        }

        return $full ? (get_config('wwwroot') . $url) : $url;
    }


    /**
     * Get all view access records relevant to a user
     */
    public static function user_access_records($viewid, $userid) {
        static $viewaccess = array();
        $userid = (int) $userid;

        if (!isset($viewaccess[$viewid][$userid])) {

            $viewaccess[$viewid][$userid] = get_records_sql_array("
                SELECT va.*
                FROM {view_access} va
                    LEFT OUTER JOIN {group_member} gm
                    ON (va.group = gm.group AND gm.member = ?
                        AND (va.role = gm.role OR va.role IS NULL))
                WHERE va.view = ?
                    AND (va.startdate IS NULL OR va.startdate < current_timestamp)
                    AND (va.stopdate IS NULL OR va.stopdate > current_timestamp)
                    AND (va.accesstype IN ('public', 'loggedin', 'friends')
                         OR va.usr = ? OR va.token IS NOT NULL OR gm.member IS NOT NULL OR va.institution IS NOT NULL)
                ORDER BY va.token IS NULL DESC, va.accesstype != 'friends' DESC",
                array($userid, $viewid, $userid)
            );
        }

        return $viewaccess[$viewid][$userid];
    }


    /**
     * Determine whether a user can write comments on this view
     *
     * If the view doesn't have the allowcomments property set,
     * then we must look at the view_access records to determine
     * whether the user can leave comments.
     *
     * In view_access, allowcomments indicates that the user can
     * comment, however if approvecomments is also set on a particular
     * access record, then all comments can only be private until the
     * view owner decides to make them public.
     *
     * Returns false, 'private', or true
     */
    public function user_comments_allowed(User $user) {
        global $SESSION;

        if (!$user->is_logged_in() && !get_config('anonymouscomments')) {
            return false;
        }

        if ($this->get('allowcomments')) {
            return $this->get('approvecomments') ? 'private' : true;
        }

        $userid = $user->get('id');
        $access = self::user_access_records($this->id, $userid);

        $publicviews = get_config('allowpublicviews');
        $publicprofiles = get_config('allowpublicprofiles');

        // a group view won't have an 'owner'
        if ($publicviews && $ownerobj = $this->get_owner_object()) {
            $publicviews = $ownerobj->institution_allows_public_views();
        }

        $allowcomments = false;
        $approvecomments = true;

        // TODO: The "mviewaccess" cookie is used by the old token-based Mahara assignment submission
        // access system, which is now deprecated. Remove eventually.
        $mnettoken = get_cookie('mviewaccess:'.$this->id);
        $usertoken = get_cookie('viewaccess:'.$this->id);
        $cid = $this->collection_id();
        $ctoken = $cid ? get_cookie('caccess:'.$cid) : null;

        if ($access) {
            foreach ($access as $a) {
                if ($a->accesstype == 'public') {
                    if (!$publicviews && (!$publicprofiles || $this->type != 'profile')) {
                        continue;
                    }
                }
                else if ($a->token && $a->token != $mnettoken
                         && (!$publicviews || ($a->token != $usertoken && $a->token != $ctoken))) {
                    continue;
                }
                else if (!$user->is_logged_in()) {
                    continue;
                }
                else if ($a->accesstype == 'friends') {
                    $owner = $this->get('owner');
                    if (!get_field_sql('
                        SELECT COUNT(*) FROM {usr_friend} f WHERE (usr1=? AND usr2=?) OR (usr1=? AND usr2=?)',
                        array($owner, $userid, $userid, $owner)
                    )) {
                        continue;
                    }
                }

                $objectionable = $this->is_objectionable();
                if ($a->allowcomments && (($objectionable && ($user->get('admin')
                    || $user->is_institutional_admin()) || !$objectionable))) {
                    $allowcomments = $allowcomments || $a->allowcomments;
                    $approvecomments = $approvecomments && $a->approvecomments;
                }
                if (!$approvecomments) {
                    return true;
                }
            }
        }

        if ($allowcomments) {
            return $approvecomments ? 'private' : true;
        }

        return false;
    }

    /**
     * Determine whether the current view is of a type which can be themed.
     * Certain view types do not respect themes when displayed.
     * Templates do not respect themes as well
     *
     * @return boolean whether the view type may be themed
     */
    function is_themeable() {
        $unthemable_types = array('grouphomepage', 'dashboard');
        return !$this->get('template') && !in_array($this->type, $unthemable_types);
    }

    /**
     * Get all views for a (user,group,institution), grouping views
     * into their collections.  Empty collections not returned.
     *
     * @param mixed   $owner integer userid or array of userids
     * @param mixed   $group integer groupid or array of groupids
     * @param mixed   $institution string institution name or array of institution names
     * @param null    $obsoleteparam Former "$matchconfig" parameter, value now ignored, param kept only to avoid breaking back-compatibility.
     * @param boolean $includeprofile include profile view
     * @param integer $submittedgroup return only views & collections submitted to this group
     * @param $string $sort Order to sort by (defaults to 'c.name, v.title')
     *
     * @return array, array
     */
    function get_views_and_collections($owner=null, $group=null, $institution=null, $obsoleteparam=null, $includeprofile=true, $submittedgroup=null, $sort=null) {

        $excludelocked = $group && group_user_access($group) != 'admin';
        $sql = "
            SELECT v.id, v.type, v.title, v.ownerformat, v.startdate, v.stopdate, v.template,
                v.owner, v.group, v.institution, v.urlid, v.submittedgroup, v.submittedhost, " .
                db_format_tsfield('v.submittedtime', 'submittedtime') . ", v.submittedstatus,
                c.id AS cid, c.name AS cname, c.framework,
                c.submittedgroup AS csubmitgroup, c.submittedhost AS csubmithost, " .
                db_format_tsfield('c.submittedtime', 'csubmittime') . ", c.submittedstatus AS csubmitstatus,
                c.progresscompletion, cv.displayorder
            FROM {view} v
                LEFT JOIN {collection_view} cv ON v.id = cv.view
                LEFT JOIN {collection} c ON cv.collection = c.id
            WHERE  v.type IN ('portfolio'";
        $sql .= $includeprofile ? ", 'profile') " : ') ';
        $sql .= $excludelocked ? 'AND v.locked != 1 ' : '';

        if (is_null($owner) && is_null($group) && is_null($institution)) {
            $values = array();
        }
        else {
            list($ownersql, $values) = self::multiple_owner_sql(
                (object) array('owner' => $owner, 'group' => $group, 'institution' => $institution)
            );
            $sql .= "AND v.$ownersql ";
        }

        if ($submittedgroup) {
            $sql .= 'AND v.submittedgroup = ? ';
            $values[] = (int) $submittedgroup;
        }

        if ($sort == null) {
            $sql .= 'ORDER BY c.name, v.title';
        }
        else {
            $sql .= "ORDER BY {$sort}";
        }
        $records = get_records_sql_assoc($sql, $values);

        $collections = array();
        $views = array();

        if (!$records) {
            return array($collections, $views);
        }

        self::get_extra_view_info($records, false, false);

        foreach ($records as &$r) {
            $vid = $r['id'];
            $cid = $r['cid'];
            $v = array(
                'id'             => $vid,
                'type'           => $r['type'],
                'name'           => $r['displaytitle'],
                'url'            => $r['fullurl'],
                'startdate'      => $r['startdate'],
                'stopdate'       => $r['stopdate'],
                'template'       => $r['template'],
                'owner'          => $r['owner'],
                'submittedgroup' => $r['submittedgroup'],
                'submittedhost'  => $r['submittedhost'],
                'submittedtime'  => $r['submittedtime'],
                'submittedstatus' => $r['submittedstatus'],
                'displayorder' => $r['displayorder'],
            );
            if (isset($r['user'])) {
                $v['ownername'] = display_name($r['user']);
                $v['ownerurl']  = profile_url($r['user']);
            }

            // If filtering by submitted views, and the view is submitted, but the collection isn't,
            // then ignore the collection and return the view by itself.
            if ($cid && (!$submittedgroup || ($r['csubmitgroup'] == $r['submittedgroup']))) {
                if (!isset($collections[$cid])) {
                    $collections[$cid] = array(
                        'id'             => $cid,
                        'name'           => $r['cname'],
                        'url'            => $r['fullurl'],
                        'owner'          => $r['owner'],
                        'group'          => $r['group'],
                        'institution'    => $r['institution'],
                        'submittedgroup' => $r['csubmitgroup'],
                        'submittedhost'  => $r['csubmithost'],
                        'submittedtime'  => $r['csubmittime'],
                        'submittedstatus' => $r['csubmitstatus'],
                        'template'       => $r['template'],
                        'views' => array(),
                    );
                    if (isset($r['user'])) {
                        $collections[$cid]['ownername'] = $v['ownername'];
                        $collections[$cid]['ownerurl'] = $v['ownerurl'];
                    }
                    if (!empty($r['progresscompletion'])) {
                        require_once('collection.php');
                        $coll = new stdClass();
                        $coll->id = $cid;
                        $collections[$cid]['url'] = Collection::get_progresscompletion_url($coll);
                    }
                    else if (!empty($r['framework'])) {
                        require_once('collection.php');
                        $coll = new stdClass();
                        $coll->id = $cid;
                        $collections[$cid]['url'] = Collection::get_framework_url($coll);
                    }
                }
                $collections[$cid]['views'][$vid] = $v;
            }
            else {
                $views[$vid] = $v;
            }
        }

        return array($collections, $views);
    }

    /**
     * @param array $portfolioelements
     * @param int $groupid
     * @return array
     * @throws SQLException
     */
    private static function extract_group_selection_plan_outcomes_and_remove_foreign_ones(array &$portfolioelements, $elementclass, $groupid) {
        $grouptaskoutcomes = [];
        /** @var \View|\Collection $portfolioobject */
        foreach($portfolioelements as $id => $portfolioelement) {
            $portfolioobject = new $elementclass($id);
            switch ($portfolioobject->get_group_id_of_corresponding_group_task()) {
                // Element is assigned as outcome to a grouptask in this group - Extract it
                case $groupid:
                    $grouptaskoutcomes[$id] = $portfolioelements[$id];
                    unset($portfolioelements[$id]);
                    break;
                // Element is not assigned as outcome to any grouptask - Leave it in
                case false:
                    break;
                // Element is outcome assigned to a grouptask of another group - Filter it out
                default:
                    unset($portfolioelements[$id]);
            }
        }
        return $grouptaskoutcomes;
    }

    /**
     * @param int $userid
     * @param int $groupid
     * @return array
     * @throws SQLException
     */
    public static function get_views_and_collections_considering_plantasks($userid, $groupid) {
        list($collections, $views) = self::get_views_and_collections($userid);

        $grouptaskoutcomecollections = self::extract_group_selection_plan_outcomes_and_remove_foreign_ones($collections, 'Collection', $groupid);
        $grouptaskoutcomeviews = self::extract_group_selection_plan_outcomes_and_remove_foreign_ones($views, 'View', $groupid);

        return [$collections, $views, $grouptaskoutcomecollections, $grouptaskoutcomeviews];
    }

    // Returns a string describing the override access for a view record
    public static function access_override_description($v) {
        if ($v['startdate'] && $v['stopdate']) {
            return get_string(
                'accessbetweendates3', 'view',
                format_date(strtotime($v['startdate']), 'strftimedate'),
                format_date(strtotime($v['stopdate']), 'strftimedate')
            );
        }
        if ($v['startdate']) {
            return get_string(
                'accessfromdate3', 'view',
                format_date(strtotime($v['startdate']), 'strftimedate')
            );
        }
        if ($v['stopdate']) {
            return get_string(
                'accessuntildate3', 'view',
                format_date(strtotime($v['stopdate']), 'strftimedate')
            );
        }
    }


    // Returns a boolean if access is pending/suspended or not
    public static function access_override_pending($v) {
        return is_view_suspended($v['id']);
    }


    /**
     * Get all views & collections for a (user,group), grouped
     * by their accesslists
     *
     * @param integer $owner
     * @param integer $group
     *
     * @return array
     */
    public static function get_accesslists($owner=null, $group=null, $institution=null) {
        require_once('institution.php');
        require_once('group.php');

        if (!is_null($owner) && !is_array($owner) && $owner > 0) {
            $ownerobj = new User();
            $ownerobj->find_by_id($owner);
        }

        $data = array();
        list($data['collections'], $data['views']) = self::get_views_and_collections($owner, $group, $institution);
        foreach ($data['views'] as $k => $view) {
            if ($view['template'] == self::SITE_TEMPLATE) {
                unset($data['views'][$k]);
            }
        }

        // Remember one representative viewid in each collection
        $viewindex = array();

        // Add strings to describe startdate/stopdate access overrides
        foreach ($data['collections'] as &$c) {
            $view = current($c['views']);
            $viewindex[$view['id']] = array('type' => 'collections', 'id' => $c['id']);
            $c['access']  = self::access_override_description($view);
            $c['pending'] = self::access_override_pending($view);
            $c['viewid']  = $view['id'];
        }
        foreach ($data['views'] as &$v) {
            $viewindex[$v['id']] = array('type' => 'views', 'id' => $v['id']);
            $v['access']  = self::access_override_description($v);
            $v['pending'] = self::access_override_pending($v);
            $v['viewid']  = $v['id'];
        }

        if (empty($viewindex)) {
            return $data;
        }

        // Get view_access records, apart from those with visible = 0 (system access records)
        $accessgroups = get_records_sql_array('
            SELECT va.*, g.grouptype, g.name, g.urlid
            FROM {view_access} va LEFT OUTER JOIN {group} g ON (g.id = va.group AND g.deleted = 0)
            WHERE va.view IN (' . join(',', array_keys($viewindex)) . ') AND va.visible = 1
            ORDER BY va.view, va.accesstype, g.grouptype, va.role, g.name, va.group, va.usr',
            array()
        );

        if (!$accessgroups) {
            return $data;
        }

        if (!function_exists('is_probationary_user')) {
            require_once(get_config('libroot') . 'antispam.php');
        }
        foreach ($accessgroups as $access) {
            // remove 'Public' from the list if the owner isn't allowed to have them
            if ($access->accesstype == 'public'
                && (
                    get_config('allowpublicviews') != 1
                    || (isset($ownerobj) && !$ownerobj->institution_allows_public_views())
                    || (isset($ownerobj) && is_probationary_user($ownerobj->id))
                )
            ) {
                continue;
            }

            // remove 'Registered users' from the list if isolated institutions are enabled
            if ($access->accesstype == 'loggedin' && is_isolated()) {
                continue;
            }
            // remove 'Friends' from the list if friendsnotallowed is enabled
            if ($access->accesstype == 'friends' && get_config('friendsnotallowed')) {
                continue;
            }

            $vi = $viewindex[$access->view];

            // Just count secret urls.
            if ($access->token) {
                if (!isset($data[$vi['type']][$vi['id']]['secreturls'])) {
                    $data[$vi['type']][$vi['id']]['secreturls'] = 0;
                }
                $data[$vi['type']][$vi['id']]['secreturls']++;
                continue;
            }

            $key = null;
            if ($access->usr) {
                $access->accesstype = 'user';
                $access->id = $access->usr;
                if ($access->role) {
                    $access->roledisplay = get_string($access->role, 'view');
                }
            }
            else if ($access->group) {
                $access->accesstype = 'group';
                $access->id = $access->group;
                if ($access->role) {
                    $access->roledisplay = get_string($access->role, 'grouptype.' . $access->grouptype);
                }
                $access->groupurl = group_homepage_url((object) array('id' => $access->group, 'urlid' => $access->urlid));
            }
            else if ($access->institution) {
                $access->accesstype = 'institution';
                $access->id = $access->institution;
                $access->name = institution_display_name($access->institution);
            }
            else {
                $key = $access->accesstype;
            }
            if ($key) {
                if (!isset($data[$vi['type']][$vi['id']]['accessgroups'][$key])) {
                    $data[$vi['type']][$vi['id']]['accessgroups'][$key] = (array) $access;
                }
            }
            else {
                $data[$vi['type']][$vi['id']]['accessgroups'][] = (array) $access;
            }
        }

        return $data;
    }

    public function submit($group, $sendnotification=true) {
        global $USER;

        if ($this->is_submitted()) {
            throw new SystemException('Attempting to submit a submitted view');
        }

        $group->roles = get_column('grouptype_roles', 'role', 'grouptype', $group->grouptype, 'see_submitted_views', 1);

        self::_db_submit(array($this->id), $group);
        handle_event('addsubmission', array('id' => $this->id,
                                            'eventfor' => 'view',
                                            'name' => $this->title,
                                            'group' => $group->id,
                                            'groupname' => $group->name));

        if ($sendnotification) {
            activity_occurred(
                'groupmessage',
                array(
                    'group'         => $group->id,
                    'roles'         => $group->roles,
                    'url'           => $this->get_url(false),
                    'strings'       => (object) array(
                        'urltext' => (object) array('key' => 'view'),
                        'subject' => (object) array(
                            'key'     => 'viewsubmittedsubject1',
                            'section' => 'activity',
                            'args'    => array($group->name),
                        ),
                        'message' => (object) array(
                            'key'     => 'viewsubmittedmessage1',
                            'section' => 'activity',
                            'args'    => array(
                                display_name($USER, null, false, true),
                                $this->title,
                                $group->name,
                            ),
                        ),
                    ),
                )
            );
        }
    }

    /**
     * Lower-level function to handle all the DB changes that should occur when you submit a view or views
     *
     * @param array $viewids The views to submit. (Normally one view by itself, or all the views in a Collection)
     * @param object $submittedgroupobj An object holding information about the group submitting to. Should contain id and roles array
     * @param string $submittedhost Alternately, the name of the remote host the group is being submitted to (for MNet submission)
     * @param int $owner The ID of the owner of the view. Used mostly for verification purposes.
     */
    public static function _db_submit($viewids, $submittedgroupobj = null, $submittedhost = null, $owner = null) {
        global $USER;
        require_once(get_config('docroot') . 'artefact/lib.php');

        $group = $submittedgroupobj;

        // Gotta provide some viewids and/or a remote username
        if (empty($viewids) || (empty($group->id) && empty($submittedhost))) {
            return;
        }

        $idstr = join(',', array_map('intval', $viewids));
        $userid = ($owner == null) ? $USER->get('id') : $owner;
        $sql = 'UPDATE {view} SET submittedtime = current_timestamp, submittedstatus = ' . self::SUBMITTED;
        $params = array();

        if ($group) {
            $groupid = (int) $group->id;
            $sql .= ', submittedgroup = ? ';
            $params[] = $groupid;
        }
        else {
            $sql .= ', submittedhost = ? ';
            $params[] = $submittedhost;
        }

        $sql .= " WHERE id IN ({$idstr}) AND owner = ?";
        $params[] = $userid;

        db_begin();
        execute_sql($sql, $params);

        if ($group) {
            foreach ($group->roles as $role) {
                foreach ($viewids as $viewid) {
                    $accessrecord = (object) array(
                        'view'            => $viewid,
                        'group'           => $groupid,
                        'role'            => $role,
                        'visible'         => 0,
                        'allowcomments'   => 1,
                        'approvecomments' => 0,
                        'ctime'           => db_format_timestamp(time()),
                    );
                    $vaid = ensure_record_exists('view_access', $accessrecord, $accessrecord);
                    handle_event('updateviewaccess', array(
                        'id' => $vaid,
                        'eventfor' => 'group',
                        'parentid' => $viewid,
                        'parenttype' => 'view',
                        'rules' => $accessrecord)
                    );
                }
            }
        }

        ArtefactType::update_locked($userid);
        db_commit();
    }

    /**
     * Indicates whether this view is a site template. (A site template is a special
     * template page which is copied as the starting point whenever a new page is
     * created.)
     *
     * @return boolean
     */
    public function is_site_template() {
        return ($this->get('template') == View::SITE_TEMPLATE);
    }

    /**
     * Returns true if the view contains at least one peer assessment block
     * @return boolean
     */
    public function has_peer_assessement_block() {
        return get_records_select_assoc('block_instance', 'blocktype = ? AND view = ?', array('peerassessment', $this->get('id')));
    }

    /**
    * Returns an array of the url for the "Return to..." button and button title
    *@return array of url, title
    */
    public function get_return_to_url_and_title() {
        $group = $this->get('group');
        $institution = $this->get('institution');

        if (!$group && !$institution) {
            return array(
                'url' => get_config('wwwroot') . "view/index.php",
                'title' => get_string('returntoviews', 'view'),
            );
        }
        else if ($group) {
            return array(
                'url' => get_config('wwwroot') . 'view/groupviews.php?group=' . $group,
                'title' => get_string('returntogroupportfolios', 'group'),
            );
        }
        else if ($institution) {
            if ($institution == 'mahara') {
                return array(
                    'url' => get_config('wwwroot') . 'admin/site/views.php',
                    'title' => get_string('returntositeportfolios', 'view'),
                );
            }
            else {
                return array(
                    'url' => get_config('wwwroot') . 'view/institutionviews.php?institution=' . $institution,
                    'title' => get_string('returntoinstitutionportfolios', 'view'),
                );
            }
        }
    }

    /**
     * Fetch a list of versions for the particular view
     *
     * @param string $view the ID of the view we wish to retrieve versioning information from
     * @param $fromdate date of the oldest version we want to retrieve
     * @param $todate date of the newest version we want to retrieve
     * @return object $views an object containing the count and data of the versions
     */
    public function get_versions($view, $fromdate = null, $todate = null) {
        if (!is_numeric($view)) {
            throw new InvalidArgumentException(get_string('noaccesstoview', 'view'));
        }
        if (is_numeric($fromdate)) {
            $fromdate = db_format_timestamp($fromdate);
        }
        else {
            $fromdate = db_format_timestamp(strtotime($fromdate));
        }
        if (is_numeric($todate)) {
            $todate = db_format_timestamp($todate);
        }
        else {
            $todate = db_format_timestamp(strtotime($todate));
        }
        $versions = new stdClass();
        $versions->count = 0;
        $versions->total = 0;
        $versions->data = array();
        $sql = "SELECT vv.*, v.title AS viewname, v.owner, v.institution
                FROM {view_versioning} vv
                JOIN {view} v ON v.id = vv.view
                WHERE vv.view = ?";
        $values = array($view);
        if ($fromdate) {
            $sql .= " AND vv.ctime >= ?";
            $values[] = $fromdate;
        }
        if ($todate) {
            $sql .= " AND vv.ctime <= ?";
            $values[] = $todate;
        }
        $sql .= " ORDER BY vv.ctime ASC";

        if ($records = get_records_sql_array($sql, $values)) {
            $versions->count = count($records);
            $versions->data = $records;
        }
        $versions->total = count_records('view_versioning', 'view', $view);
        return $versions;
    }

    public function get_timeline_form($view, $from = null, $to = null) {
        if (is_numeric($from)) {
            $from = db_format_timestamp($from);
        }
        if (is_numeric($to)) {
            $to = db_format_timestamp($to);
        }

        require_once('pieforms/pieform/elements/calendar.php');
        $elements = array(
            'from' => array(
                'title' => get_string('From'),
                'type' => 'calendar',
                'defaultvalue' => strtotime($from),
                'caloptions' => array(
                    'showsTime' => false,
                ),
            ),
            'to' => array(
                'title' => get_string('To'),
                'type' => 'calendar',
                'defaultvalue' => strtotime($to),
                'caloptions' => array(
                    'showsTime' => false,
                ),
            ),
            'viewid' => array(
                'type' => 'hidden',
                'value' => $view,
            ),
            'submit' => array(
                'type' => 'submit',
                'class' => 'btn-primary',
                'value' => get_string('go'),
            )
        );

        $form = array(
            'name' => 'timeline',
            'elements' => $elements,
            'autofocus' => false,
        );
        return pieform($form);
    }

    public function build_timeline_results($search, $offset, $limit) {
        return false;
    }

    public function format_versioning_data($data, $versionnumber=0) {
        global $USER;
        if (empty($data)) {
            return false;
        }

        $data = json_decode($data);
        $data->version = $versionnumber;
        $this->description = isset($data->description) ? $data->description : '';
        $this->tags = isset($data->tags) && is_array($data->tags) ? $data->tags : array();
        if (!isset($data->newlayout)) {
            $this->numrows = isset($data->numrows) ? $data->numrows : $this->numrows;
            $this->layout = isset($data->layout) ? $data->layout : $this->layout;
            $colsperrow = array();
            if (isset($data->columnsperrow)) {
                foreach ($data->columnsperrow as $k => $v) {
                    $colsperrow[$k] = $v;
                }
            }
            $this->columnsperrow = $colsperrow;
            $this->columns = array();
            $layout = $this->get_layout();
            for ($i = 1; $i <= $this->numrows; $i++) {
                $widths = explode(',', $layout->rows[$i]['widths']);
                for ($j = 1; $j <= $data->columnsperrow->{$i}->columns; $j++) {
                    $this->columns[$i][$j] = array('blockinstances' => array());
                    $this->columns[$i][$j]['width'] = $widths[$j-1];
                }
            }
        }

        $html = '';
        if (!empty($data->blocks)) {
            require_once(get_config('docroot') . 'blocktype/lib.php');
            if (!isset($data->newlayout)) {
                usort($data->blocks, function($a, $b) { return $a->order > $b->order; });
            }
            foreach ($data->blocks as $k => $v) {
                safe_require('blocktype', $v->blocktype);
                $blockdata = array(
                    'id'          => $v->originalblockid,
                    'blocktype'   => $v->blocktype,
                    'title'       => $v->title,
                    'view'        => $this->get('id'),
                    'view_obj'    => $this,
                    'configdata'  => serialize((array)$v->configdata),
                );
                if (!isset($data->newlayout)) {
                    $blockdata['row']    = $v->row;
                    $blockdata['column'] = $v->column;
                    $blockdata['order']  = $v->order;
                }
                else {
                    $blockdata['positionx'] = $v->positionx;
                    $blockdata['positiony'] = $v->positiony;
                    $blockdata['height']    = $v->height;
                    $blockdata['width']     = $v->width;
                }
                $bi = new BlockInstance(0, $blockdata);
                // Add a fake unique id to allow for pagination etc
                if (!isset($data->newlayout)) {
                    $this->columns[$v->row][$v->column]['blockinstances'][] = $bi;
                }
                else {
                    $this->blocks[] = $bi;
                }
            }
        }
        if (!$USER->has_peer_role_only($this) || $this->has_peer_assessement_block()) {
            if (!isset($data->newlayout)) {
                $html = $this->build_rows(false, false, $data);
            }
            else {
                $html = $this->get_blocks(false, false, $data);
            }
        }
        else {
            $html = '<div class="alert alert-info">' .
                        get_string('nopeerassessmentrequired', 'artefact.peerassessment') .
                    '</div>';
        }
        $data->html = $html;
        return $data;
    }

    public function description_to_block() {
        $description = $this->get('description');
        $configdata = array(
           'text' => $description,
           'retractable' => false,
           'retractedonload' => false,
        );
        $bi = new BlockInstance(0,
           array(
               'blocktype'  => 'text',
               'title'      => get_string('description'),
               'configdata' => serialize($configdata),
               'view'       => $this->get('id'),
               'view_obj'   => $this,
               'row'        => 0,
               'column'     => 0,
               'order'      => 0,
               'positionx'  => 0,
               'positiony'  => 0,
               'width'      => 12,
               'height'     => 1,
           )
        );
        $bi->commit();

        // check artefact_file_embedded table
        update_record('artefact_file_embedded',
            (object) array('resourcetype' => 'text', 'resourceid' => $bi->get('id')),
            array('resourcetype' => 'description', 'resourceid' => $this->get('id'))
        );
    }

    public function has_signoff_block() {
        $blocks = get_record('block_instance', 'blocktype', 'signoff', 'view', $this->id);
        return ($blocks ? true : false);
    }

    public function get_progress_action($column = 'owner') {
        return new ProgressAction($this, $column);
    }

    /**
     * Checks if the view is a copy of a template and it has the instructions locked for edit
     * @return boolean
     */
    public function is_instruction_locked() {
        if (get_field('view_instructions_lock', 'locked', 'view', $this->get('id'))) {
            return true;
        }
        return false;
    }

    /*
     * Gets the id of the view that this view is a copy of
     * @return integer view id of the original view, 0 if this view is not a copy
     */
    public function get_original_template() {
        if (($originaltemplate = get_field('view_instructions_lock', 'originaltemplate', 'view', $this->get('id')))
            && get_record('view', 'id', $originaltemplate)) {
            return $originaltemplate;
        }
        return 0;
    }

    /*
     * Lock the instructions edit for this copy
     * @param integer $templateid the view id of the template this view is a copy of
     */
    public function lock_instructions_edit($templateid) { //todo: dont allow to change the template id if the record exists already
        ensure_record_exists('view_instructions_lock',
            (object) array(
                'view'=> $this->get('id')
            ),
            (object) array(
                'view'=> $this->get('id'),
                'originaltemplate'=> $templateid,
                'locked'=> 1
            )
        );
    }

    /*
     * Unlock the instructions edit for this copy
     */
    public function unlock_instructions_edit() {
        set_field('view_instructions_lock', 'locked', 0, 'view', $this->get('id'));
    }
}

class ProgressAction {
    private $status;
    private $action;
    private $column;
    private $view_as;

    const STATUS_NOTHING = 0;
    const STATUS_NEEDSACTION = 1;
    const STATUS_ACTIONNOTALLOWED = 2;
    const STATUS_COMPLETED = 3;

    const ACTION_SIGNOFF = 4;
    const ACTION_UNSIGNOFF = 5;
    const ACTION_VERIFY = 6;

    public function __construct($view, $column) {
        global $USER;
        $this->action = false;
        $this->column = $column;
        // Who is accessing the view
        $this->view_as = '';
        if ($view->get('owner') == $USER->get('id')) {
            $this->view_as = 'owner';
        }
        else if ($USER->is_manager($view)) {
            $this->view_as = 'manager';
        }

        $hassignoffblock = $view->has_signoff_block();
        $issignedoff = ArtefactTypePeerassessment::is_signed_off($view);
        $isverifiedenabled = ArtefactTypePeerassessment::is_verify_enabled($view);
        $isverified = ArtefactTypePeerassessment::is_verified($view);

        $this->status = self::STATUS_NOTHING;
        if ($hassignoffblock) {
            if ($column == 'owner') {
                if ($issignedoff) {
                    $this->status = self::STATUS_COMPLETED;
                    if ($this->view_as == 'owner') {
                        $this->action = self::ACTION_UNSIGNOFF;
                    }
                }
                else {
                    if ($this->view_as == 'owner') {
                        $this->status = self::STATUS_NEEDSACTION;
                        $this->action = self::ACTION_SIGNOFF;
                    }
                    else {
                        $this->status = self::STATUS_ACTIONNOTALLOWED;
                    }
                }
            }
            else if ($column == 'manager') {
                if ($isverifiedenabled) {
                    if (!$issignedoff) {
                        $this->status = self::STATUS_ACTIONNOTALLOWED;
                    }
                    else if ($isverified) {
                        $this->status = self::STATUS_COMPLETED;
                    }
                    else {
                        if ($this->view_as == 'manager') {
                            $this->status = self::STATUS_NEEDSACTION;
                            $this->action = self::ACTION_VERIFY;
                        }
                        else {
                            $this->status = self::STATUS_ACTIONNOTALLOWED;
                        }
                    }
                }
            }
        }
    }

    public function get_icon() {
        $notallowedicon = "icon icon-circle dot disabled";
        $actionicon = "icon icon-circle action";
        $completedicon = "icon icon-check-circle completed";
        switch ($this->status) {
          case self::STATUS_NEEDSACTION:
                $icon = $actionicon;
            break;
          case self::STATUS_ACTIONNOTALLOWED:
                $icon = $notallowedicon;
            break;
          case self::STATUS_COMPLETED:
                $icon = $completedicon;
            break;
          default:
                $icon = '';
            break;
        }
        return $icon;
    }

    public function get_action() {
        switch ($this->action) {
            case self::ACTION_SIGNOFF:
                $action = 'signoff_action';
            break;
            case self::ACTION_VERIFY:
                  $action = 'verify_action';
              break;
            case self::ACTION_UNSIGNOFF:
                $action = 'unsignoff_action';
            break;
            default:
                $action = false;
            break;
        }
        return $action;
    }

    public function get_title() {
        $title = '';
        if ($this->column == 'owner') {
            if ($this->status == self::STATUS_NEEDSACTION || $this->status == self::STATUS_ACTIONNOTALLOWED) {
                $title = get_string('needssignedoff', 'collection');
            }
            else if ($this->status == self::STATUS_COMPLETED) {
                $title = get_string('signedoff', 'collection');
            }
        }
        else if ($this->column == 'manager') {
            if ($this->status == self::STATUS_NEEDSACTION || $this->status == self::STATUS_ACTIONNOTALLOWED) {
                $title = get_string('needsverified', 'collection');
            }
            else if ($this->status == self::STATUS_COMPLETED) {
                $title = get_string('verified', 'collection');
            }
        }
        return $title;
    }
}

class ViewSubmissionException extends UserException {
    public function strings() {
        return array_merge(
            parent::strings(),
            array(
                'title' => get_string('viewsubmissionexceptiontitle', 'view'),
                'message' => get_string('viewsubmissionexceptionmessage', 'view'),
            )
        );
    }
}

/**
 * Create the form buttons for copying a page and/or a collection
 *
 * @param string $group           The ID of the group to copy to
 * @param string $institution     The ID of the institution to copy to
 * @param string $template        The ID of the page to copy
 * @param string $collection      The ID of the collection to copy
 * @param string $collectiononly  Only display the copy collection button
 *
 * @return form array
 */
function create_view_form($group=null, $institution=null, $template=null, $collection=null, $collectiononly=false) {
    global $USER;
    $form = array(
        'name'            => 'createview',
        'method'          => 'post',
        'plugintype'      => 'core',
        'pluginname'      => 'view',
        'renderer'        => 'div',
        'successcallback' => 'createview_submit',
        'class'           => 'form-as-button float-left',
        'elements'   => array(
            'new' => array(
                'type' => 'hidden',
                'value' => true,
            ),
            'submitcollection' => array(
                'type'  => 'hidden',
                'value' => false,
            ),
            'submit' => array(
                'type'  => 'button',
                'usebuttontag' => true,
                'class' => 'btn-secondary',
                'value' => '<span class="icon icon-plus left" role="presentation" aria-hidden="true"></span>' . get_string('createview', 'view'),
            )
        )
    );
    if ($group) {
        $form['elements']['group'] = array(
            'type'  => 'hidden',
            'value' => $group,
        );
    }
    else if ($institution) {
        $form['elements']['institution'] = array(
            'type'  => 'hidden',
            'value' => $institution,
        );
    }
    else {
        $form['elements']['owner'] = array(
            'type' => 'hidden',
            'value' => $USER->get('id'),
        );
    }
    if ($collection !== null) {
        $form['elements']['copycollection'] = array(
            'type'  => 'hidden',
            'value' => $collection,
        );
        $form['elements']['submitcollection'] = array(
            'type'  => 'button',
            'usebuttontag' => true,
            'class' => 'btn-secondary btn-sm btn-group-item',
            'value' => get_string('copycollection', 'collection'),
        );
    }
    if ($template !== null) {
        $form['elements']['usetemplate'] = array(
            'type'  => 'hidden',
            'value' => $template,
        );
        $form['elements']['submit']['value'] = get_string('copyview', 'view');
        $form['elements']['submit']['class'] = 'btn-secondary btn-sm btn-group-item text-inline';
        $form['name'] .= $template;
    }
    if ($collectiononly) {
        unset($form['elements']['submit']);
    }
    return $form;
}

function createview_submit(Pieform $form, $values) {
    global $SESSION;

    $values['template'] = !empty($values['istemplate']) ? 1 : 0; // Named 'istemplate' in the form to prevent confusion with 'usetemplate'

    if (!empty($values['submitcollection'])) {
        require_once(get_config('libroot') . 'collection.php');
        $templateid = $values['copycollection'];
        unset($values['copycollection']);
        unset($values['usetemplate']);
        list($collection, $template, $copystatus) = Collection::create_from_template($values, $templateid);
        if (isset($copystatus['quotaexceeded'])) {
            $SESSION->add_error_msg(get_string('collectioncopywouldexceedquota', 'collection'));
            redirect(get_config('wwwroot') . 'view/choosetemplate.php');
        }
        $SESSION->add_ok_msg(get_string('copiedpagesblocksandartefactsfromtemplate', 'collection',
            $copystatus['pages'],
            $copystatus['blocks'],
            $copystatus['artefacts'],
            $template->get('name'))
        );

        redirect(get_config('wwwroot') . 'collection/edit.php?copy=1&id=' . $collection->get('id'));
    }
    else if (isset($values['usetemplate'])) {
        $templateid = $values['usetemplate'];
        unset($values['usetemplate']);
        $artefactcopies = array();
        list($view, $template, $copystatus) = View::create_from_template($values, $templateid, null, true, false, $artefactcopies);
        if (isset($copystatus['quotaexceeded'])) {
            $SESSION->add_error_msg(get_string('viewcopywouldexceedquota', 'view'));
            redirect(get_config('wwwroot') . 'view/choosetemplate.php');
        }
        $SESSION->add_ok_msg(get_string('copiedblocksandartefactsfromtemplate', 'view',
            $copystatus['blocks'],
            $copystatus['artefacts'],
            $template->get('title'))
        );
    }
    else {
        // Use the site default portfolio page to create a new page
        $sitedefaultviewid = get_field('view', 'id', 'institution', 'mahara', 'template', View::SITE_TEMPLATE, 'type', 'portfolio');
        if (!empty($sitedefaultviewid)) {
            $artefactcopies = array();
            list($view, $template, $copystatus) = View::create_from_template($values, $sitedefaultviewid, null, true, false, $artefactcopies);
            if (isset($copystatus['quotaexceeded'])) {
                $SESSION->add_error_msg(get_string('viewcreatewouldexceedquota', 'view'));
                redirect(get_config('wwwroot') . 'view/index.php');
            }
        }
        else {
            $view = View::create($values);
        }
    }

    redirect(get_config('wwwroot') . 'view/editlayout.php?new=1&id=' . $view->get('id'));
}

/**
 * Copy a view via a 'copy' url
 * Currently for copying a page via a 'copy' button on view/view.php
 *
 * @param integer $id           View id
 * @param bool $istemplate      (optional) If you want to mark as template
 * @param integer $groupid      (optional) The group to copy the view to
 * @param integer $collectionid (optional) Provide the collection id to indicate we want
 *                                         to copy collection the view belongs to
 */
function copyview($id, $istemplate = false, $groupid = null, $collectionid = null) {
    global $USER, $SESSION;

    // check that the user can copy view
    $view = new View($id);
    if (!$view->is_copyable()) {
        throw new AccessDeniedException(get_string('thisviewmaynotbecopied', 'view'));
    }

    // set up a packet of values to send to the create_from_template function
    $values = array('new' => 1,
                    'owner' => $USER->get('id'),
                    'template' => (int) $istemplate,
                    );
    if (!empty($groupid) && is_int($groupid)) {
        $values['group'] = $groupid;
    }

    require_once(get_config('docroot') . 'artefact/lib.php');

    if (!empty($collectionid)) {
        require_once(get_config('libroot') . 'collection.php');
        list($collection, $template, $copystatus) = Collection::create_from_template($values, $collectionid);
        if (isset($copystatus['quotaexceeded'])) {
            $SESSION->add_error_msg(get_string('collectioncopywouldexceedquota', 'collection'));
            redirect(get_config('wwwroot') . 'view/view.php?id=' . $id);
        }
        $SESSION->add_ok_msg(get_string('copiedpagesblocksandartefactsfromtemplate', 'collection',
                                        $copystatus['pages'],
                                        $copystatus['blocks'],
                                        $copystatus['artefacts'],
                                        $template->get('name'))
                             );
        redirect(get_config('wwwroot') . 'collection/edit.php?copy=1&id=' . $collection->get('id'));
    }
    else {
        $artefactcopies = array();
        list($view, $template, $copystatus) = View::create_from_template($values, $id, null, true, false, $artefactcopies);
        if (isset($copystatus['quotaexceeded'])) {
            $SESSION->add_error_msg(get_string('viewcopywouldexceedquota', 'view'));
            redirect(get_config('wwwroot') . 'view/view.php?id=' . $id);
        }
        $SESSION->add_ok_msg(get_string('copiedblocksandartefactsfromtemplate', 'view',
                                        $copystatus['blocks'],
                                        $copystatus['artefacts'],
                                        $template->get('title'))
                             );
        redirect(get_config('wwwroot') . 'view/editlayout.php?new=1&id=' . $view->get('id'));
    }
}

function createview_cancel_submit(Pieform $form, $values) {
    if (isset($values['group'])) {
        redirect(get_config('wwwroot') . 'view/groupviews.php?group=' . $values['group']);
    }
    if (isset($values['institution'])) {
        redirect(get_config('wwwroot') . 'view/institutionviews.php?institution=' . $values['institution']);
    }
    redirect(get_config('wwwroot') . 'view/index.php');
}

function searchviews_submit(Pieform $form, $values) {
    $tag = $query = null;
    if ($values['query'] != '') {
        $query = $values['query'];
    }
    $searchin = isset($values['type']) ? $values['type'] : null;
    $orderby = isset($values['orderby']) ? $values['orderby'] : null;
    $matchalltags = isset($values['matchalltags']) ? $values['matchalltags'] : false;
    $group = isset($values['group']) ? $values['group'] : null;
    $institution = isset($values['institution']) ? $values['institution'] : null;
    redirect(View::get_myviews_url($group, $institution, $query, $searchin, $orderby, $matchalltags));
}

/**
 * Generates a form which will submit a view or collection to one of
 * the owner's groups.
 *
 * @param mixed $view The view to be submitted. Either a View object,
 *                    or (for compatibility with previous versions of
 *                    this function) an integer view id.
 * @param array $tutorgroupdata An array of stdClass objects with id
 *                    and name properties representing groups.
 * @param string $returnto A URL - where to go after leaving the
 *                    submit page.
 *
 * @return string
 */
function view_group_submission_form($view, $tutorgroupdata, $returnto=null) {
    if (is_numeric($view)) {
        $view = new View($view);
    }
    $viewid = $view->get('id');

    $options = array();
    foreach ($tutorgroupdata as $group) {
        $options[$group->id] = $group->name;
    }

    $selectiontaskgroupid = $view->get_group_id_of_corresponding_group_task();

    if ($selectiontaskgroupid && array_key_exists($selectiontaskgroupid, $options)) {
        $options = array_intersect_key($options, [$selectiontaskgroupid => 'Fill options only with this entry']);
    }

    // This form sucks from a language string point of view. It should
    // use pieforms' form template feature
    $form = array(
        'name' => 'view_group_submission_form_' . $viewid,
        'method' => 'post',
        'renderer' => 'div',
        'class' => 'form-inline',
        'autofocus' => false,
        'successcallback' => 'view_group_submission_form_submit',
        'elements' => array(
            'text1' => array(
                'type' => 'html',
                'class' => 'text-inline',
                'value' => '',
            ),
            'inputgroup' => array(
                'type' => 'fieldset',
                'class' => 'input-group',
                'elements' => array(
                    'options' => array(
                        'type' => 'select',
                        'collapseifoneoption' => false,
                        'options' => $options,
                    ),
                    'submit' => array(
                        'type' => 'button',
                        'usebuttontag' => true,
                        'class' => 'btn-primary input-group-append',
                        'value' => get_string('submit')
                    )
                ),
            ),
            'returnto' => array(
                'type' => 'hidden',
                'value' => $returnto,
            )
        ),
    );

    if ($view->get_collection()) {
        $form['elements']['collection'] = array(
            'type' => 'hidden',
            'value' => $view->get_collection()->get('id'),
        );
        $form['elements']['text1']['value'] = get_string('submitthiscollectionto1', 'view') . '&nbsp;';
    }
    else {
        $form['elements']['view'] = array(
            'type' => 'hidden',
            'value' => $viewid
        );
        $form['elements']['text1']['value'] = get_string('submitthisviewto1', 'view') . '&nbsp;';
    }

    return pieform($form);
}

function view_group_submission_form_submit(Pieform $form, $values) {
    $params = array(
        'group' => $values['options'],
        'returnto' => $values['returnto'],
    );
    if (isset($values['collection'])) {
        $params['collection'] = $values['collection'];
    }
    else {
        $params['id'] = $values['view'];
    }
    redirect('/view/submit.php?' . http_build_query($params));
}

/**
 * This function installs the site default portfolio page
 *
 */
function install_system_portfolio_view() {
    $viewid = get_field('view', 'id', 'institution', 'mahara', 'template', View::SITE_TEMPLATE, 'type', 'portfolio');
    if ($viewid) {
        log_info('A site default portfolio page already seems to be installed');
        return $viewid;
    }
    $view = View::create(array(
        'type'        => 'portfolio',
        'institution' => 'mahara',
        'template'    => 2,
        'title'       => get_string('templateportfoliotitle', 'view'),
        'description' => get_string('templateportfoliodescription1', 'view'),
    ), 0);
    $view->set_access(array(array(
        'type' => 'loggedin'
    )));
    return $view->get('id');
}

/**
 * display format for author names in views - firstname
 */
define('FORMAT_NAME_FIRSTNAME', 1);

/**
 * display format for author names in views - lastname
 */
define('FORMAT_NAME_LASTNAME', 2);

/**
 * display format for author names in views - firstname lastname
 */
define('FORMAT_NAME_FIRSTNAMELASTNAME', 3);

/**
 * display format for author names in views - preferred name
 */
define('FORMAT_NAME_PREFERREDNAME', 4);

/**
 * display format for author names in views - student id
*/
define('FORMAT_NAME_STUDENTID', 5);

/**
 * display format for author names in views - obeys display_name
 */
define('FORMAT_NAME_DISPLAYNAME', 6);

function filter_isolated_view_access($view, $viewaccess) {
    global $SESSION;

    if (!is_isolated() || empty($viewaccess)) {
        // no need to filter
        return $viewaccess;
    }

    $removerules = 0;
    foreach ($viewaccess as $k => $access) {
        if ($access['accesstype'] == 'loggedin') {
            unset($viewaccess[$k]);
            $removerules++;
        }
        else if ($access['type'] == 'user' && !empty($access['usr'])) {
            $userinstitutions = get_column('usr_institution', 'institution', 'usr', $access['usr']);
            if ($view->get('owner')) {
                $viewinstitutions = get_column('usr_institution', 'institution', 'usr', $view->get('owner'));
            }
            else if ($view->get('group')) {
                $viewinstitutions = get_column('group', 'institution', 'id', $view->get('group'));
            }
            else if ($view->get('institution')) {
                $viewinstitutions = array($view->get('institution'));
            }
            // check that the user is in the same institution
            if (!((empty($viewinstitutions) && empty($userinstitutions)) || array_intersect($viewinstitutions, $userinstitutions))) {
                unset($viewaccess[$k]);
                $removerules++;
            }
        }
        else if ($access['type'] == 'group' && !empty($access['group'])) {
            $userinstitutions = get_column('group', 'institution', 'id', $access['group']);
            if ($view->get('owner')) {
                $viewinstitutions = get_column('usr_institution', 'institution', 'usr', $view->get('owner'));
            }
            else if ($view->get('group')) {
                $viewinstitutions = get_column('group', 'institution', 'id', $view->get('group'));
            }
            else if ($view->get('institution')) {
                $viewinstitutions = array($view->get('institution'));
            }
            // check that the user is in the same institution
            if (!((empty($viewinstitutions) && empty($userinstitutions)) || array_intersect($viewinstitutions, $userinstitutions))) {
                unset($viewaccess[$k]);
                $removerules++;
            }
        }
    }
    if ($removerules) {
        $SESSION->add_error_msg(get_string('isolatedinstitutionsremoverules', 'error', $removerules));
    }
    $viewaccess = array_values($viewaccess);
    return $viewaccess;
}

/**
 * Checks if the tinymce text has any tags other than <p> or <br>
 * if it doesn't have extra tags, then it will remove them and return only the text
 */
function can_extract_description_text($description) {
    //remove html tags form the text but leaving only the tags that can be translated to text
    $texttagsonly_description = strip_tags($description, '<p><br><span><em><strong>');
    $cleandescription = strip_tags($description);

    if ($description == $texttagsonly_description && strlen($cleandescription) <= 160) {
        return $cleandescription;
    }
    return false;
}
