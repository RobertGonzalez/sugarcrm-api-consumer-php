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
        // Grab the known expected config
        if (file_exists('config.php')) {
            require_once 'config.php';
            if (isset($config)) {
                $this->config = array_merge($this->config, $config);
            }
        }

        // Grab the override if there is one, in similar fashion to SugarCRM proper
        if (file_exists('config_override.php')) {
            // Trash the old config we just got
            unset($config);
            require_once 'config_override.php';
            if (isset($config)) {
                $this->config = array_merge($this->config, $config);
            }
        }
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
}
