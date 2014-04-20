<?php
/**
 * A simple configuration class for handling configs. This uses a very simple
 * implementation of the registry pattern.
 */
class SugarApiConfig
{
    /**
     * Instance holder for this singleton object
     *
     * @var SugarApiConfig
     */
    private static $instance = null;

    /**
     * The collection of configurations
     *
     * @var array
     */
    private $config = array();

    /**
     * Private constructor used to enforce singleton loading
     */
    final private function __construct() {}

    /**
     * Static instance getter, gets the singleton instance of this class
     *
     * @return SugarApiConfig
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self;
            self::$instance->load();
        }

        return self::$instance;
    }

    /**
     * Loads the config file and override file if set and found.
     */
    public function load($withOverride = true)
    {
        $configs = $this->getConfigs();
        $configs = array_merge($configs['config'], $configs['override']);
        $this->config = array_merge($this->config, $configs);
    }

    /**
     * Simple setter, sets a name/value pair into the configuration array
     *
     * @param string $name The name of the config entry
     * @param mixed $value The value of the config entry
     */
    public function set($name, $value)
    {
        $this->config[$name] = $value;
    }

    /**
     * Simple public getter that fetches a value for a given config param. If the
     * param isn't found, an optional default value can be returned.
     *
     * @param string $name The name of the config entry
     * @param mixed $default The default to return if the config entry is not found
     */
    public function get($name, $default = null)
    {
        return array_key_exists($name, $this->config) ? $this->config[$name] : $default;
    }

    /**
     * Overloaded getter that allows object access of config array entries
     *
     * @param string $name The name of the config entry to get a value for
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Overloaded setter, sets a name/value pair into the configuration array
     *
     * @param string $name The name of the config entry
     * @param mixed $value The value of the config entry
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * Gets config arrays from the core config file and the override if there is
     * one
     *
     * @return array
     */
    protected function getConfigs()
    {
        // Default the return to something reasonable
        $return = array('config' => array(), 'override' => array());

        // Grab the known expected config
        if (file_exists('config.php')) {
            require_once 'config.php';
            if (isset($config)) {
                $return['config'] = $config;
            }
        }

        // Grab the override if there is one, in similar fashion to SugarCRM proper
        if (file_exists('config_override.php')) {
            // Trash the old config we just got
            unset($config);
            require_once 'config_override.php';
            if (isset($config)) {
                $return['override'] = $config;
            }
        }

        return $return;
    }

    /**
     * Saves changes made to existing configs to the override file
     *
     * Will only add newly set values or changes to existing values
     *
     * @return boolean
     */
    public function save()
    {
        // Holds the changes/additions
        $changed = array();

        // Gets the current collection of configs from the config files
        $configs = $this->getConfigs();

        // All current configs as one large collection
        $all = array_merge($configs['config'], $configs['override']);

        // See if there are new values in the config array that need to be handled
        $newKeys = array_diff_key($this->config, $all);
        foreach ($newKeys as $key) {
            $changed[$key] = $this->config[$key];
        }

        // Now check and see if any of the current array are different from the
        // base and custom
        foreach ($configs as $config) {
            $changed = array_merge($changed, $this->getChangedValues($config));
        }

        if ($changed) {
            $write = '';
            foreach ($changed as $key => $value) {
                $write .= "\$config['$key'] = " . var_export($value) . ";\n";
            }
            return file_put_contents('config_override.php', $write);
        }

        return true;
    }

    /**
     * Gets differences between the current config and the $config passed in
     *
     * @param array $config A config collection to check diffs on
     * @return array
     */
    protected function getChangedValues(array $config)
    {
        $diff = array();
        foreach ($this->config as $key => $value) {
            if (!isset($config[$key]) || $config[$key] !== $value) {
                $diff[$key] = $value;
            }
        }
        return $diff;
    }
}
