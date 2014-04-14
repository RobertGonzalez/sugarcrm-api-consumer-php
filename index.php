<?php
// Get the utility class that does a lot of the heavy lifting, as well as talks
// to the API
require_once 'utils/SugarApiUtil.php';
SugarApiUtil::getInstance();

// Handle the module controller
require_once 'controllers/AbstractController.php';
$module = isset($_SESSION['module']) ? $_SESSION['module'] : 'Module';
$class = $module . 'Controller';
$file = "controllers/$class.php";
if (file_exists($file)) {
    require_once $file;
} else {
    $class = 'ModuleController';
    require_once "controllers/$class.php";
}

// Handle the request
$controller = new $class;
$controller->action();
$controller->render();
