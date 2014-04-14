<?php
require_once 'utils/SugarApiConfig.php';
require_once 'utils/SugarApiBean.php';

/**
 * Basic abstract controller class that handles most of the common actions of
 * all requests
 */
abstract class AbstractController
{
    /**
     * The template to parse when rendering
     *
     * @var string
     */
    public $template;

    /**
     * The selected module, if there is one
     *
     * @var string
     */
    public $module;

    /**
     * The request platform. Should default to 'base'.
     *
     * @var string
     */
    public $platform;

    /**
     * The request language. Should default to 'en_us'.
     *
     * @var string
     */
    public $language;

    /**
     * The requested action
     *
     * @var string
     */
    public $action;

    /**
     * The ID of the module, if one was requested
     *
     * @var string
     */
    public $id;

    /**
     * The API object
     *
     * @var SugarApiUtil
     */
    public $api;

    /**
     * The modules available to this application. Should be metadata driven.
     *
     * @var array
     */
    public $modules = array(/*'Contacts', 'Notes', 'Accounts'*/);

    /**
     * The list of platforms to test
     *
     * @var array
     */
    public $platforms = array();

    /**
     * The view variables to parse
     *
     * @var array
     */
    protected $_vars = array();

    /**
     * A SugarApiConfig object
     *
     * @var SugarApiConfig
     */
    protected $_config;

    public function __construct() {
        // Handle requested pieces of information
        $this->id = empty($_REQUEST['id']) ? '' : $_REQUEST['id'];
        $this->module = $_SESSION['module'];
        $this->platform = $_SESSION['platform'];
        $this->language = $_SESSION['language'];

        // Set the requested record id into the template
        $this->__set('id', $this->id);

        // Get the config object setup
        $this->_config = SugarApiConfig::getInstance();

        // Builds the form action used in postbacks
        $this->formaction = 'http://' . $_SERVER['HTTP_HOST'] . '/'. $this->_config->site_path;

        // Gets certain lists
        $this->modules = $this->_getApi()->getModules();
        $this->platforms = $this->_getApi()->getPlatforms();

        // Get a default module if one isn't selected, but make sure it isn't home
        if (empty($this->module)) {
            $modules = array_keys($this->modules);
            foreach ($modules as $module) {
                if ($module == 'Home') {
                    continue;
                }

                $this->module = $module;
                break;
            }
        }

        $this->moduleSingular = $this->getModuleSingular();
    }

    /**
     * Gets a singular version of this module name, if it exists, from the app
     * strings
     *
     * @return string
     */
    protected function getModuleSingular()
    {
        $singularList = $this->getAppListString('moduleListSingular');
        return isset($singularList[$this->module]) ? $singularList[$this->module] : $this->module;
    }

    /**
     * Template var setter
     *
     * @param string $var The template variable to set
     * @param mixed $val The val of the var should be set to
     */
    public function __set($var, $val) {
        $this->_vars[$var] = $val;
    }

    /**
     * Template var getter.
     *
     * @param string $var The template variable to get the value for
     * @return mixed
     */
    public function __get($var) {
        return array_key_exists($var, $this->_vars) ? $this->_vars[$var] : null;
    }

    /**
     * Renders the view for the request
     */
    public function render() {
        // Handle output of the template for this request
        ob_start();
        require_once 'views/' . $this->template . '.php';
        $this->view = ob_get_clean();

        // Handle output of the entire layout now
        ob_start();
        require_once 'layouts/default.php';
        echo ob_get_clean();
    }

    /**
     * Handles the action being requested. Delegates to the action method.
     */
    public function action() {
        if (empty($_SESSION['authtoken'])) {
            $action = 'login';
        } else {
            $action = empty($_REQUEST['action']) ? 'list' : $_REQUEST['action'];
        }

        $this->action = $action;

        $this->template = ucfirst(strtolower($action));
        $method = $action . 'Action';
        $this->{$method}();
    }

    /**
     * The action for handling metadata
     */
    public function metadataAction() {
        $this->template = 'MetadataList';
        $types = empty($_REQUEST['filter']) ? 'modules,full_module_list' : $_REQUEST['filter'];
        $platform = empty($_REQUEST['platform']) ? '' : $_REQUEST['platform'];

        // This is the core return
        $metadata = $this->_getApi()->getModuleMetadata($this->module, $types, $platform);

        // Small cleanup
        $fields = $relationships = $shared = $relmodules = array();
        foreach ($metadata[$this->module]['fields'] as $field => $defs) {
            $fields[$field]['name'] = $field;

            if (isset($defs['type']) && $defs['type'] == 'link' && isset($defs['relationship'])) {
                $relmodule = empty($defs['module']) ? '' : $defs['module'];
                $tmodule = empty($relmodule) ? 'NOMODULE' : $relmodule;

                if (empty($relmodules[$tmodule])) {
                    $relmodules[$tmodule] = 1;
                } else {
                    $relmodules[$tmodule]++;
                }
                $relationships[$field] = array('name' => $defs['relationship'], 'module' => $relmodule);
                $fields[$field]['rel'] = $defs['relationship'];
            }
        }

        ksort($relmodules);

        foreach ($metadata[$this->module]['relationships'] as $rel => $defs) {
            foreach ($relationships as $field => $def) {
                if ($def['name'] == $rel) {
                    $shared[$field] = $rel;
                    break;
                }
            }
        }

        $this->list = array('metadata' => $metadata, 'fields' => $fields, 'relationships' => $relationships, 'shared' => $shared, 'relmodules' => $relmodules);
    }

    /**
     * Gets rows for a list view from a data collection from a records call
     *
     * @param array $data The result of an API records fetch
     * @return array
     */
    protected function getListRows($data)
    {
        $rows = array();
        foreach ($data as $row) {
            $row['detail'] = '?action=detail&id=' . $row['id'];
            $row['link_name'] = '<a href="' . $row['detail'] . '">' . $row['name'] . '</a>';
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * The action for handling record lists
     */
    public function listAction() {
        // Handle saving of a create/edit first, so it will reflect in the list
        $this->handleSave();

        // Process list now
        $this->headings = $this->getListHeadings();

        $rows = array();
        if ($this->module) {
            $res = $this->_getApi()->getList($this->module);
            $rows = $this->getListRows($res);
        }

        $this->rows = $rows;
    }

    /**
     * The action for handling editing of a record and creating a record
     */
    public function editAction() {
        $bean = new SugarApiBean;
        if ($this->id) {
            $res = $this->_getApi()->getRecord($this->module, $this->id);
            $bean->loadFromArray($res);
        }
        $this->bean = $bean;

        $obj = $this->_getApi()->getMetadataObject($this->platform);
        $this->metadata = $obj->getModuleFieldsForView($this->module, 'record');
    }

    /**
     * The action for handling viewing a record. It's the same as edit, except
     * the rendered template is not a form.
     */
    public function detailAction() {
        $this->editAction();
    }

    /**
     * The action for handling removing a document attachment
     */
    public function removedocAction() {
        $field = empty($_REQUEST['field']) ? '' : $_REQUEST['field'];
        if ($field && $this->id) {
            if ($this->_getApi()->deleteRecordAttachment($this->module, $this->id, $field)) {
                $this->_redirect();
            } else {
                $this->error = 'Could not remove attachment.';
            }
        } else {
            $this->error = 'Missing attachment identifier.';
        }
    }

    /**
     * The action for handling logins and login failure
     */
    public function loginAction() {
        $username = empty($_POST['username']) ? null : $_POST['username'];
        if (empty($_SESSION['authtoken'])) {
            if (!empty($username) && !empty($_POST['password'])) {
                if ($this->_getApi()->login($username, $_POST['password'])) {
                    $this->_redirect();
                } else {
                    $this->error = 'Login failed.';
                }
            } else {
                if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                    $this->error = 'A username and password is required.';
                }
            }
            $this->username = $username;
            // Handle the login and set the auth token
        } else {
            // Redirect to home
            $this->_redirect();
        }
    }

    /**
     * The action for handling logouts
     */
    public function logoutAction() {
        $this->_getApi()->logout();
        $this->_redirect();
    }

    /**
     * The action for handling downloading of attachments and files
     */
    public function downloadAction() {
        $field = empty($_REQUEST['field']) ? '' : $_REQUEST['field'];
        if ($field && $this->id) {
            $module = !empty($_REQUEST['dlmodule']) ? $_REQUEST['dlmodule'] : $this->module;
            $this->_getApi()->getAttachment($module, $this->id, $field);
        }
    }

    /**
     * Redirects the request to another location
     *
     * @param string $location The URL of the location to redirect to
     */
    protected function _redirect($location = null) {
        if (empty($location)) {
            $location = $this->formaction;
        }
        header('Location: ' . $location);
        exit;
    }

    /**
     * Uploads an attachment
     */
    protected function _uploadAttachment() {
        if (!empty($_FILES)) {
            $field = key($_FILES);
            $newfile = basename($_FILES[$field]['name']);
            if (is_uploaded_file($_FILES[$field]['tmp_name'])) {
                if (move_uploaded_file($_FILES[$field]['tmp_name'], $newfile)) {
                    $record = $this->_getApi()->getRecord($this->module, $this->id);
                    if (!empty($record[$field])) {
                        $success = $this->_getApi()->putRecordAttachment($this->module, $this->id, $field, $newfile);
                    } else {
                        $success = $this->_getApi()->postRecordAttachment($this->module, $this->id, $field, $newfile);
                    }

                    if ($success) {
                        unlink($newfile);
                        //$this->_redirect();
                    } else {
                        $this->error = 'Could not save the attachment.';
                    }
                } else {
                    $this->error = 'Error saving uploaded attachment.';
                }
            } else {
                $this->error = 'Possible security issue.';
            }
        }
    }

    /**
     * Gets a single record based on the request module and id
     *
     * @param boolean $asObject Flag that indicates type of data to return
     */
    protected function _getRecord($asObject = true) {
        $record = $this->_getApi()->getRecord($this->module, $this->id);
        if ($asObject) {
            settype($record, 'object');
        }
        return $record;
    }

    /**
     * Gets the API object
     *
     * @return SugarApiUtil
     */
    protected function _getApi() {
        if ($this->api === null) {
            require_once 'utils/SugarApiUtil.php';
            $this->api = SugarApiUtil::getInstance();
        }

        return $this->api;
    }

    public function getModuleString($string)
    {
        $obj = $this->_getApi()->getLanguageObject($this->language, $this->platform);
        return $obj->getModuleString($this->module, $string, $string);
    }

    public function getAppListString($string)
    {
        $obj = $this->_getApi()->getLanguageObject($this->language, $this->platform);
        return $obj->getAppListString($string, array());
    }

    /**
     * Gets list view columns from the list metadata. This will parse the labels
     * for each field as part of the process.
     *
     * @return array
     */
    protected function getListHeadings()
    {
        $obj = $this->_getApi()->getMetadataObject($this->platform);
        $fields = $obj->getModuleFieldsForView($this->module, 'list');

        if (empty($fields)) {
            $headings = $this->_config->default_list_headings;
        } else {
            $headings = array();
            foreach ($fields as $name => $def) {
                $headings[$name] = $this->getModuleString($def['vname']);
            }
        }

        return $headings;
    }

    /**
     * Handles saving a record. Will attempt to compare old state to new state
     * on an edit so that it only sends changes. Will send all fields on a create.
     *
     * This method sets success and error message where appropriate.
     */
    protected function handleSave()
    {
        if (isset($_POST['save']) && $_POST['save'] === 'true') {
            $obj = $this->_getApi()->getMetadataObject($this->platform);
            $fields = $obj->getModuleFieldsForView($this->module, 'record');
            $send = array();

            if ($this->id) {
                // This is an edit
                $type = 'PUT';
                $record = $this->_getRecord(false);
                foreach ($_POST as $key => $val) {
                    // Don't need id, as that will be part of the request URL
                    if ($key === 'id') {
                        continue;
                    }

                    // Only send different field values
                    if (isset($record[$key]) && $record[$key] !== $val) {
                        $send[$key] = $val;
                    }
                }
            } else {
                // This is an add
                $type = 'POST';
                foreach ($_POST as $key => $val) {
                    if ($key === 'id') {
                        continue;
                    }

                    // Send the entire packet
                    $send[$key] = $val;
                }
            }

            if (!empty($send)) {
                $res = $this->_getApi()->saveRecord($this->module, $this->id, $type, $send);
                if (!empty($res)) {
                    $this->success = 'Record saved!';
                } else {
                    $this->error = 'Could not save the record';
                }
            } else {
                $this->success = 'Nothing to save';
            }
        }
    }
}
