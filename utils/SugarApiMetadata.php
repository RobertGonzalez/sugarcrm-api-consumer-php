<?php
require_once 'utils/SugarApiUtil.php';
require_once 'utils/SugarApiLanguage.php';

/**
 * Class for handling metadata locally. Sugar7 is driven entirely by metadata,
 * and the metadata call is probably the most expensive and most time consuming
 * of all API calls within the app. This class seeks to alleviate the expense
 * and time of the metadata API by maintaining a local cache.
 */
class SugarApiMetadata
{
    /**
     * The platform for this metadata. Defaults to 'base'.
     *
     * @var string
     */
    protected $platform = 'base';

    /**
     * The metadata collection. Will be loaded when needed in {@see getMetadata}.
     *
     * @var array
     */
    protected $metadata = array();

    /**
     * Class constructor. Simply sets the platform for this request into this class.
     *
     * @param string $platform The platform for this metadata
     */
    public function __construct($platform = 'base')
    {
        $this->platform = $platform;
    }

    /**
     * Writes the metadata from the api response to the local cache
     *
     * @param array $data The metadata collection
     * @return boolean
     */
    protected function writeMetadataToCache($data)
    {
        // Handle getting needed paths
        $file = $this->getMetadataCacheFilename();
        $dir  = dirname($file);

        // Test the existence of the cache directory
        if (!file_exists($dir)) {
            if (!mkdir($dir)) {
                // TODO: maybe find a better way of handling failure here
                $api = SugarApiUtil::getInstance();
                $api->handleError("Could not create metadata cache directory");
                return false;
            }
        }

        // Build the metadata array and save it to the cache file
        $write  = "<?php\n// Metadata Cache written " . date(DATE_ATOM) . "\n";
        $write .= "\$metadata = " . var_export($data, 1) . ";\n";
        $return = file_put_contents($file, $write);
        return $return !== false && $return !== 0;
    }

    /**
     * Gets metadata from the cache if it exists
     *
     * @return array
     */
    protected function getMetadataFromCache()
    {
        $metadata = array();
        $filename = $this->getMetadataCacheFilename();

        // Since an empty or non existent file doesn't kill this process, it's
        // ok to suppress errors on file include
        @include $filename;

        // Return what we have
        return $metadata;
    }

    /**
     * Gets the metadata cache file name
     *
     * @return string
     */
    public function getMetadataCacheFilename()
    {
        return 'cache/metadata_' . $this->platform . '.php';
    }

    /**
     * Gets the metadata either from cache or from the api
     *
     * @param mixed $filter Either a string or array of string metadata sections
     * @return array
     */
    public function getMetadata($filter = null)
    {
        $data = $this->getMetadataFromCache();
        // Go to the API to get the metadata. This is an expensive and time
        // consuming call, hence the caching of the data locally.
        if (empty($data)) {
            $api = SugarApiUtil::getInstance();
            $reply = $api->call("metadata?platform={$this->platform}");
            if (!empty($reply['reply'])) {
                $data = $reply['reply'];
                $this->writeMetadataToCache($data);
            }
        }

        // Handle languages
        if (isset($data['languages']['enabled'])) {
            $langs = $data['languages']['enabled'];
            if (!in_array('en_us', $langs)) {
                array_unshift($langs, 'en_us');
            }

            foreach ($langs as $lang) {
                $obj = new SugarApiLanguage($lang, $this->platform);
                $obj->getLanguage();
            }
        }

        // If there is no filter just send back the data as-is
        if (empty($filter)) {
            return $data;
        }

        // Handle the filter request. Assumption is it's an array unless it is
        // found to be a string, which could contain multiple filters separated
        // by commas
        if (is_string($filter)) {
            // If it is a comma separated list of filters, handle it
            if (strpos($filter, ',')) {
                $filter = explode(',', $filter);
            } else {
                // Make it an array
                $filter = array($filter);
            }
        }

        // Make sure hash is in the return always
        if (array_search('_hash', $filter) === false) {
            $filter[] = '_hash';
        }

        // Prepare the return
        $return = array();
        foreach ($filter as $section) {
            if (isset($data[$section])) {
                $return[$section] = $data[$section];
            }
        }
        return $return;
    }

    /**
     * Gets filtered metadata for a module
     *
     * @var string $module The module to get filtered metadata for
     * @return array
     */
    public function getMetadataForModule($module)
    {
        // Default the return
        $return = array();

        // Get the metadata collection
        $data = $this->getMetadata();

        // Handle the filtration
        if (isset($data['modules'][$module])) {
            $return[$module] = $data['modules'][$module];

            // Handle relationships
            if (isset($data['relationships'])) {
                foreach ($data['relationships'] as $name => $def) {
                    if ($name == '_hash') {
                        continue;
                    }

                    if ($def['rhs_module'] === $module || $def['lhs_module'] === $module) {
                        $return[$module]['relationships'][$name] = $def;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Gets required fields of a module
     *
     * @param string $module The name of the module to get the fields for
     * @param boolean $required Only get fields that are required fields
     * @return array
     */
    public function getModuleFields($module, $required = true)
    {
        $data = $this->getMetadataForModule($module);
        $fields = $data[$module]['fields'];
        unset($fields['_hash']);
        $return = array();
        foreach ($fields as $name => $def) {
            $file = isset($def['type']) && $def['type'] === 'file';
            $reqd = $required === false || !empty($def['required']);
            if ($file || $reqd) {
                $return[$name] = $def;
            }
        }
        return $return;
    }

    /**
     * Gets fields for a module that happen to be on a view
     *
     * @param string $module The module to get the view field list for
     * @param string $view The view to scrape for fields
     * @return array
     */
    public function getModuleFieldsForView($module, $view)
    {
        $data = $this->getMetadataForModule($module);
        $fields = $data[$module]['fields'];
        $panels = $data[$module]['views'][$view]['meta']['panels'];
        $return = array();
        foreach ($panels as $panel) {
            foreach ($panel['fields'] as $pField) {
                $fName = null;
                if (is_array($pField) && isset($pField['name'])) {
                    $fName = $pField['name'];
                } elseif (is_string($pField)) {
                    $fName = $pField;
                }

                if ($fName && isset($fields[$fName])) {
                    $return[$fName] = $fields[$fName];
                }
            }
        }

        return $return;
    }
}
