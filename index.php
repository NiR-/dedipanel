<?php
//ini_set('xdebug.profiler_enable', 'on');

define('BASE_URL', 'http://localhost/dp_steam/');
define('ROOT_DIR', dirname(__FILE__));

define('APPS_DIR', ROOT_DIR . '/apps/');
define('LIBS_DIR', ROOT_DIR . '/libs/');
define('CFG_DIR', ROOT_DIR . '/configs/');

define('HTML_DIR', ROOT_DIR . '/assets/html/');
define('LANG_DIR', ROOT_DIR . '/assets/langs/');

define('SOCK_DIR', LIBS_DIR . 'Socket/');
define('QUERY_DIR', LIBS_DIR . 'Steam/');

define('CSS_URL', BASE_URL . '/assets/css/');
define('JS_URL', BASE_URL . '/assets/js/');
define('IMG_URL', BASE_URL . '/assets/images');

include_once LIBS_DIR . 'Core/Doctrine.compiled.php';
include_once LIBS_DIR . 'Core/Application.class.php';

// On ajoute un autoloader pour les classes du projet
spl_autoload_register(function ($classname) {
    $files = array(
        'SSH' => 'SSH', 'Server' => 'Steam/Server');
    if (array_key_exists($classname, $files)) {
        require_once LIBS_DIR . $files[$classname] . '.class.php';
    }
});

$app = new Application();
$app->execute();
?>