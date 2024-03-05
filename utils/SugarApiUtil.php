<?php
require_once 'utils/SugarApiConfig.php';
require_once 'utils/SugarApiMetadata.php';
require_once 'utils/SugarApiLanguage.php';

/**
 * Utility class that interacts with the SugarCRM API and does some other useful
 * stuff.
 */
class SugarApiUtil
{
    protected $_config;
    protected $_url;
    protected $_platforms = array('base' => 'Default', 'mobile' => 'Mobile', 'portal' => 'Portal');
    protected $_init = false;
    protected $metadataObjects = array();
    protected $languageObjects = array();
    private static $_instance = null;
    private static $_reauthCount = 0;

    public static function getInstance() {
        if (null === self::$_instance) {
            session_name('LocalAPI');
            session_start();
            self::$_instance = new self;
            self::$_instance->init();
        }

        return self::$_instance;
    }

    public function init() {
        if (!$this->_init) {
            $this->_config = SugarApiConfig::getInstance();
            $this->_url = $this->_config->api_url;
            $this->_setupModule();
            $this->_setupPlatform();
            $this->_setupLanguage();
            $this->_init = true;
        }
    }

    /**
     * Redirects the request to another location
     *
     * @param string $location The URL of the location to redirect to
     */
    public function redirect($location = null) {
        if (empty($location)) {
            $location = $this->getFormAction();
        }
        header('Location: ' . $location);
        exit;
    }

    /**
     * Gets the form action for the current request. Also useful for redirecting
     * on failed auth/reauth situations
     *
     * @return string
     */
    public function getFormAction()
    {
        return 'http://' . $_SERVER['HTTP_HOST'] . '/'. $this->_config->site_path;
    }

    public function logout() {
        // Unset all of the session variables.
        $_SESSION = array();

        // If it's desired to kill the session, also delete the session cookie.
        // Note: This will destroy the session, and not just the session data!
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Finally, destroy the session.
        session_destroy();
    }

    protected function _reauth() {
        if (!empty($_SESSION['refreshtoken'])) {
            if (self::$_reauthCount == 2) {
                $this->logout();
                $this->redirect();
            }
            self::$_reauthCount++;
            $args = array(
                'grant_type' => 'refresh_token',
                'refresh_token' => $_SESSION['refreshtoken'],
                'client_id' => 'sugar',
                'client_secret' => '',
            );

            $oldtoken = $_SESSION['authtoken'];
            // Allow reauth to not send the oauth token
            $_SESSION['authtoken'] = '';
            $reply = $this->call('oauth2/token',json_encode($args));
            if (empty($reply['reply']['access_token'])) {
                $this->handleError("Rest re-authentication failed, message looked like: ".$reply['replyRaw']);
            }
            $_SESSION['authtoken'] = $reply['reply']['access_token'];
            $_SESSION['refreshtoken'] = $reply['reply']['refresh_token'];
            $_SESSION['downloadtoken'] = $reply['reply']['download_token'];
        } else {
            $this->handleError("Attempt to reauth without a refresh token");
        }
    }

    public function login($username, $password) {
        $args = array(
            'grant_type' => 'password',
            'username' => $username,
            'password' => $password,
            'client_id' => 'sugar',
            'client_secret' => '',
        );

        $reply = $this->call('oauth2/token', $args, 'POST');
        if ( empty($reply['reply']['access_token']) ) {
            return false;
        }
        $_SESSION['authtoken'] = $reply['reply']['access_token'];
        $_SESSION['refreshtoken'] = $reply['reply']['refresh_token'];
        $_SESSION['download_token'] = $reply['reply']['download_token'];
        return true;
    }

    public function getPlatforms() {
        return $this->_platforms;
    }

    /**
     * Gets the list of visible, enabled, displayable modules
     *
     * @return array
     */
    public function getModules()
    {
        $modules = array();
        if (!empty($_SESSION['authtoken'])) {
            // Gets the modules_info element of the metadata
            $list = $this->getMetadataObject()->getMetadata('modules_info');

            // Get rid of hashes since we don't need them for this
            unset($list['_hash'], $list['modules_info']['_hash']);

            // Loop and set to get what we want
            foreach ($list['modules_info'] as $module => $defs) {
                if (!empty($defs['enabled']) && !empty($defs['visible']) && !empty($defs['display_tab'])) {
                    $modules[$module] = $module;
                }
            }
        }

        return $modules;
    }

    public function getApiUrl() {
        return $this->_url;
    }

    public function getList($module) {
        $reply = $this->call($module);
        return isset($reply['reply']['records']) ? $reply['reply']['records'] : array();
    }

    public function getRecord($module, $id) {
        $reply = $this->call("$module/$id");
        return isset($reply['reply']) ? $reply['reply'] : array();
    }

    public function saveRecord($module, $id, $type, $data) {
        $reply = $this->call("$module/$id", $data, $type);
        return isset($reply['reply']) ? $reply['reply'] : array();
    }

    public function getAttachment($module, $id, $field) {
        $record = $this->getRecord($module, $id);
        $reply = $this->call("$module/$id/file/$field");
        $rawdata = $reply['replyRaw'];
        $name = $record[$field];
        touch($name);
        file_put_contents($name, $rawdata);
        $mime = $this->getMimeType($name);
        $size = filesize($name);


        $images = array('image/gif', 'image/png', 'image/bmp', 'image/jpeg', 'image/jpg', 'image/pjpeg');
        header("Pragma: public");
        header("Cache-Control: maxage=1, post-check=0, pre-check=0");

        if (in_array($mime, $images)) {
            header("Content-Type: $mime");
        } else {
            header("Content-Type: application/force-download");
            header("Content-type: application/octet-stream");
            header("Content-Disposition: attachment; filename=\"".$name."\";");
        }
        header("X-Content-Type-Options: nosniff");
        header("Content-Length: $size");
        header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 2592000));
        set_time_limit(0);
        ob_start();

        readfile($name);
        @ob_end_flush();
        unlink($name);
    }

    public function getAttachmentInfo($module, $id) {
        $reply = $this->call("$module/$id/file");
        return isset($reply['reply']) ? $reply['reply'] : array();
    }

    public function deleteRecordAttachment($module, $id, $field) {
        $reply = $this->call("$module/$id/file/$field", '', 'DELETE');
        return isset($reply['reply'][$field]);
    }

    public function postRecordAttachment($module, $id, $field, $filepath) {
        // Work on this file now
        //$post = array($field => '@' . realpath('.') . '/' . basename($filepath), 'noJSON' => true);
        $post = array($field => new CURLFile(realpath('.') . '/' . basename($filepath)), 'noJSON' => true);
        $reply = $this->call("$module/$id/file/$field", $post);
        return !empty($reply['reply'][$field]['name']);
    }

    public function putRecordAttachment($module, $id, $field, $filepath, $encode = false) {
        //$filedata = base64_encode(file_get_contents($filepath));
        //$params = json_encode(array('filename' => basename($filepath), 'filecontents' => $filedata, 'filesize' => filesize($filepath)));
        //$reply = $this->_getReply("$module/$id/file/$field", $params, 'PUT');
        $opts = array(CURLOPT_INFILESIZE => filesize($filepath), CURLOPT_INFILE => fopen($filepath, 'r'));
        $headers = array(
            /*'Content-Type: image/png', */
            'filename: ' . basename($filepath),
        );
        if ($encode) {
            $args['content_transfer_encoding'] = 'base64';
        } else {
            $args = '';
        }

        $reply = $this->call("$module/$id/file/$field", $args, 'PUT', $opts, $headers);

        return !empty($reply['reply'][$field]['name']);
    }

    public function getModuleMetadata($module, $platform = '') {
        return $this->getMetadataObject($platform)->getMetadataForModule($module);
        /*
        $path = "metadata?typeFilter=$types&moduleFilter=$module";
        if (!empty($platform) && $platform != 'base' && isset($this->_platforms[$platform])) {
            $path .= "&platform=$platform";
        }

        $reply = $this->call($path);
        return isset($reply['reply']) ? $reply['reply'] : array();
        */
    }

    public function getRelatedRecords($module, $id, $field) {
        $reply = $this->call("$module/$id/link/$field");
        return !empty($reply['reply']['records']) ? $reply['reply']['records'] : array();
    }

    /**
     * Gets the mime type of a file
     *
     * @param string $filename Path to the file
     * @return string
     */
    public function getMimeType($filename) {
        if( function_exists( 'mime_content_type' ) ) {
            $mimetype = mime_content_type($filename);
        } elseif( function_exists( 'ext2mime' ) ) {
            $mimetype = ext2mime($filename);
        } else {
            $mimetype = 'application/octet-stream';
        }

        return $mimetype;
    }

    protected function _setupModule() {
        $modulechange = false;
        if (empty($_REQUEST['module'])) {
            if (!isset($_SESSION['module'])) {
                $_SESSION['module'] = '';
            }
        } else {
            if (isset($_SESSION['module'])) {
                if ($_SESSION['module'] != $_REQUEST['module']) {
                    $modulechange = true;
                }
            }

            $_SESSION['module'] = $_REQUEST['module'];
        }

        $_SESSION['moduleChanged'] = $modulechange;
    }

    protected function _setupPlatform() {
        $platformchange = false;
        if (empty($_REQUEST['platform'])) {
            if (empty($_SESSION['platform'])) {
                $_SESSION['platform'] = 'Base';
            }
        } else {
            if (isset($_SESSION['platform'])) {
                if ($_SESSION['platform'] != $_REQUEST['platform']) {
                    $platformchange = true;
                }
            }

            $_SESSION['platform'] = $_REQUEST['platform'];
        }

        $_SESSION['platformChanged'] = $platformchange;
    }

    protected function _setupLanguage() {
        $change = false;
        if (empty($_REQUEST['language'])) {
            if (empty($_SESSION['language'])) {
                $_SESSION['language'] = 'en_us';
            }
        } else {
            if (isset($_SESSION['language'])) {
                if ($_SESSION['language'] != $_REQUEST['language']) {
                    $change = true;
                }
            }

            $_SESSION['language'] = $_REQUEST['language'];
        }

        $_SESSION['languageChanged'] = $change;
    }

    /**
     * Makes an HTTP call to the Sugar API and returns a combination collection
     * of data.
     *
     * @param string $action The endpoint path
     * @param mixed $args A string or an array of arguments to send with the request
     * @param string $type The request type, defaulted internally to GET
     * @param array $addedOpts Additional cURL options
     * @param array $addedHeaders Additional HTTP headers to send with the request
     * @return array A collection of results from the request
     */
    public function call($action, $args='', $type='', $addedOpts = array(), $addedHeaders = array())
    {
        // We'll need these in case of a 412
        $_input = array($action, $args, $type, $addedOpts, $addedHeaders);
        // Normalize the args
        if (!empty($args) && is_array($args)) {
            if (empty($args['noJSON'])) {
                $args = json_encode($args);
            } else {
                unset($args['noJSON']);
            }
        }

        if (!preg_match('#platform=(.*)#', $action) && $_SESSION['platform'] != 'base' && isset($this->_platforms[$_SESSION['platform']])) {
            $con = strpos($action, '?') !== false ? '?' : '&';
            $action .= $con . 'platform=' . $_SESSION['platform'];
        }

        $ch = curl_init($this->_url . $action);
        if (!empty($args)) {
            if (empty($type)) {
                $type = 'POST';
                curl_setopt($ch, CURLOPT_POST, 1); // This sets the POST array
                $requestMethodSet = true;
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
        } else {
            if (empty($type)) {
                $type = 'GET';
            }
        }

        if (!empty($_SESSION['authtoken'])) {
            $addedHeaders[] = 'OAuth-Token: ' . $_SESSION['authtoken'];
        }

        // Only set a custom request for not POST with a body
        // This affects the server and how it sets its superglobals
        if (empty($requestMethodSet)) {
            if ($type == 'PUT') {
                curl_setopt($ch, CURLOPT_PUT, 1);
            } else {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
            }
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $addedHeaders);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        if (is_array($addedOpts) && !empty($addedOpts)) {
            // I know curl_setopt_array() exists, just wasn't sure if it was hurting stuff
            foreach ($addedOpts as $opt => $val) {
                curl_setopt($ch, $opt, $val);
            }
        }

        $httpInfo = curl_getinfo($ch);
        $httpReply = curl_exec($ch);
        $reply = json_decode($httpReply,true);

        // Handle reauth if need be.
        if (isset($reply['error']) && $reply['error'] == 'invalid_grant') {
            //$this->handleError("There are errors: " . var_export($reply, 1));
            //echo "About to reauth...";
            $this->_reauth();
            return $this->call($_input[0],$_input[1],$_input[2],$_input[3],$_input[4]);
        }

        $httpError = $httpReply === false ? curl_error($ch) : null;

        $return = array('info' => $httpInfo, 'reply' => $reply, 'replyRaw' => $httpReply, 'error' => $httpError, 'headers' => get_headers($this->_url . $action));
        //var_dump($return);
        return $return;
    }

    /**
     * Handles errors in this class
     *
     * @param string $msg The error message to report
     */
    public function handleError($msg) {
        // Die. Just die.
        die($msg);
    }

    /**
     * Gets a metadata object for a given platform.
     *
     * @param string $platform The platform to get the metadata object for
     * @return SugarApiMetadata
     */
    public function getMetadataObject($platform = 'base')
    {
        $platform = $this->getNormalizedPlatform($platform);

        if (!isset($this->metadataObjects[$platform])) {
            $this->metadataObjects[$platform] = new SugarApiMetadata($platform);
        }

        return $this->metadataObjects[$platform];
    }

    /**
     * Gets a language object for a given language and platform.
     *
     * @param string $lang The language to get the object for
     * @param string $platform The platform to get the metadata object for
     * @return SugarApiMetadata
     */
    public function getLanguageObject($lang, $platform = 'base')
    {
        $platform = $this->getNormalizedPlatform($platform);

        if (!isset($this->languageObjects[$platform])) {
            $this->languageObjects[$platform] = new SugarApiLanguage($lang, $platform);
        }

        return $this->languageObjects[$platform];
    }

    /**
     * Gets a normalized platform name
     *
     * @param string $platform The platform to get a cleaned up value for
     * @return string
     */
    protected function getNormalizedPlatform($platform)
    {
        if (empty($platform) || !isset($this->_platforms[$platform])) {
            $platform = 'base';
        }

        return $platform;
    }
}
