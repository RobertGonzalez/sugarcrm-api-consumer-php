<?php
/**
 * You can setup any configuration parameters you want here. This is a simple
 * hash that will be used by the SugarApiConfig object to access values within
 * this array. You can also define a similar array in a config_override.php file
 * and those values will be merged into these values.
 */
$config = array(
    // Headings used in list views, can be overridden in each module controller
    'default_list_headings' => array(
        'id' => 'ID',
        'link_name' => 'Name',
    ),
    // This needs to be a FQDN to your sugarcrm instance
    'api_url' => 'rest/v10/',
    // Path on your local server following your host name
    'site_path' => '',
);
