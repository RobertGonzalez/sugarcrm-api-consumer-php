<?php
class SugarApiLanguage
{
    protected $lang;
    protected $platform;
    protected $data = array();
    
    public function __construct($lang, $platform = 'base')
    {
        $this->platform = $platform;
        $this->lang = $lang;
    }
    
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
    
    public function getAppString($label, $default = null)
    {
        return $this->getLanguageElement('app_strings', $label, $default);
    }
    
    public function getAppListString($label, $default = null)
    {
        return $this->getLanguageElement('app_list_strings', $label, $default);
    }
    
    public function getModuleString($module, $label, $default = null)
    {
        if (!isset($this->data['mod_strings'])) {
            $this->loadLanguage();
        }
        
        if (isset($this->data['mod_strings'][$module][$label])) {
            return $this->data['mod_strings'][$module][$label];
        }
        
        $appString = $this->getAppString($label, $default);
        if ($appString == $label) {
            return $default;
        }
        
        return $appString;
    }
    
    public function loadLanguage()
    {
        $this->data = $this->getLanguage();
    }
    
    public function getLanguage()
    {
        $data = $this->getLanguageFromCache();
        
        if (empty($data)) {
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