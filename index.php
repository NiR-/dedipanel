<?php
//ini_set('xdebug.profiler_enable', 'on');
//session_start();

define('BASE_URL', 'http://localhost/dp_design/dedipanel');
define('ROOT_DIR', dirname(__FILE__));

define('APPS_DIR', ROOT_DIR . '/apps/');
define('LIBS_DIR', ROOT_DIR . '/libs/');
define('CFG_DIR', ROOT_DIR . '/configs/');

define('HTML_DIR', ROOT_DIR . '/assets/html/');
define('LANG_DIR', ROOT_DIR . '/assets/langs/');

define('CSS_URL', BASE_URL . '/assets/css/');
define('JS_URL', BASE_URL . '/assets/js/');
define('IMG_URL', BASE_URL . '/assets/images');

include_once LIBS_DIR . 'Core/Application.class.php';

// On utilise les deux autoloaders de Doctrine.
// Le premier sert à charger Doctrine lui même, le second sert a charger nos modèles
spl_autoload_register(array('Doctrine_Core', 'autoload'));
spl_autoload_register(array('Doctrine_Core', 'modelsAutoload'));

$app = new Application();
?>