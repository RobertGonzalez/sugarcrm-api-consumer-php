<?php
/**
 * Language strings interaction class. This is a read only class.
 */
class SugarApiLanguage
{
    /**
     * The language this object is based on
     *
     * @var string
     */
    protected $lang;

    /**
     * The platform for this object
     *
     * @var string
     */
    protected $platform;

    /**
     * The language data that is used for getting values
     *
     * @var string
     */
    protected $data = array();

    /**
     * Constructor, simply sets the language and the platform
     *
     * @param string $lang The language for this request
     * @param string $platform The platform for this request
     */
    public function __construct($lang, $platform = 'base')
    {
        $this->platform = $platform;
        $this->lang = $lang;
    }

    /**
     * Gets a collection from the language data based on the index of the data
     *
     * @param string $element The index of the language data to get
     * @param string $label The label to get from the element
     * @param mixed $default A default to return if the requested label wasn't found
     * @return mixed
     */
    protected function getLanguageElement($element, $label, $default = null)
    {
        if (!isset($this->data[$element])) {
            $this->loadLanguage();
        }

        if (isset($this->data[$element][$label])) {
            return $this->data[$element][$label];
        }

        return $default;
    }

    /**
     * Gets a string from the app strings collection
     *
     * @param string $label The label to get
     * @param mixed $default A default to return if the requested label wasn't found
     * @return mixed
     */
    public function getAppString($label, $default = null)
    {
        return $this->getLanguageElement('app_strings', $label, $default);
    }

    /**
     * Gets a string from the app list strings collection
     *
     * @param string $label The label to get
     * @param mixed $default A default to return if the requested label wasn't found
     * @return mixed
     */
    public function getAppListString($label, $default = null)
    {
        return $this->getLanguageElement('app_list_strings', $label, $default);
    }

    /**
     * Gets a string from the module strings collection
     *
     * @param string $module The module to get the string for
     * @param string $label The label to get
     * @param mixed $default A default to return if the requested label wasn't found
     * @return mixed
     */
    public function getModuleString($module, $label, $default = null)
    {
        if (!isset($this->data['mod_strings'])) {
            $this->loadLanguage();
        }

        if (isset($this->data['mod_strings'][$module][$label])) {
            return $this->data['mod_strings'][$module][$label];
        }

        // If there was no module string found, try the app strings
        $appString = $this->getAppString($label, $default);
        if ($appString == $label) {
            return $default;
        }

        return $appString;
    }

    /**
     * Loads the language data either from cache or from the api
     */
    public function loadLanguage()
    {
        $this->data = $this->getLanguage();
    }

    /**
     * Gets the language data either from cache or from the api
     *
     * @return array
     */
    public function getLanguage()
    {
        $data = $this->getLanguageFromCache();

        if (empty($data) || isset($data['error'])) {
            unset($data);
            $data['status'] = 'Fetching language from the server';
            $api = SugarApiUtil::getInstance();
            $reply = $api->call("lang/{$this->lang}?platform={$this->platform}");
            if (!empty($reply['reply'])) {
                $data = $reply['reply'];
                $this->writeLanguageToCache($data);
            }
        }

        return $data;
    }

    /**
     * Gets metadata from the cache if it exists
     *
     * @return array
     */
    protected function getLanguageFromCache()
    {
        $language = array();
        $filename = $this->getCacheFilename();

        // Since an empty or non existent file doesn't kill this process, it's
        // ok to suppress errors on file include
        @include $filename;

        // Return what we have
        return $language;
    }

    /**
     * Gets the language cache file name for a platform and language
     *
     * @param string $lang The language code to get the filename for
     * @return string
     */
    public function getCacheFilename()
    {
        return 'cache/language_' . $this->platform . '_' . $this->lang . '.php';
    }

    /**
     * Writes the language data from the api response to the local cache
     *
     * @param string $lang The language to save
     * @param array $data The language values
     * @return boolean
     */
    protected function writeLanguageToCache($data)
    {
        // Handle getting needed paths
        $file = $this->getCacheFilename();
        $dir  = dirname($file);

        // Test the existence of the cache directory
        if (!file_exists($dir)) {
            if (!mkdir($dir)) {
                // TODO: maybe find a better way of handling failure here
                $api = SugarApiUtil::getInstance();
                $api->handleError("Could not create language cache directory");
                return false;
            }
        }

        // Build the metadata array and save it to the cache file
        $write  = "<?php\n// Language Cache for {$this->lang} written " . date(DATE_ATOM) . "\n";
        $write .= "\$language = " . var_export($data, 1) . ";\n";
        $return = file_put_contents($file, $write);
        return $return !== false && $return !== 0;
    }
}
