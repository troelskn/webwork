<?php
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
  throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
function autoloader($class) {
  if (isset($GLOBALS['AUTOLOAD'][$class])) {
    require_once($GLOBALS['AUTOLOAD']);
    return true;
  }
  return spl_autoload(str_replace('_', '/', $class));
}
error_reporting(E_ALL);
set_error_handler('exception_error_handler');
spl_autoload_register('autoloader');

$GLOBALS['AUTOLOAD'] = array();
$GLOBALS['ENVIRONMENT'] = isset($_SERVER['ENVIRONMENT']) ? strtolower($_SERVER['ENVIRONMENT']) : 'development';
$GLOBALS['APPLICATION_ROOT'] = dirname(__FILE__);
$GLOBALS['HTTP_ROOT'] = 'http://localhost/';
set_include_path(get_include_path() . PATH_SEPARATOR . $GLOBALS['APPLICATION_ROOT'].'/lib/');
// Register thirdparty plugins
$GLOBALS['PLUGINS'] = array();
foreach (scandir($GLOBALS['APPLICATION_ROOT'].'/vendor') as $plugin) {
  if (substr($plugin, 0, 1) !== '.') {
    $GLOBALS['PLUGINS'][$plugin] = $GLOBALS['APPLICATION_ROOT'].'/vendor/'.$plugin;
    set_include_path(
      get_include_path() . PATH_SEPARATOR . $GLOBALS['APPLICATION_ROOT'].'/vendor/'.$plugin.'/lib');
  }
}

// Load various global functions/bindings used throughout the application
require_once 'common_globals.inc.php';

// Load environment
// Include default config
include($GLOBALS['APPLICATION_ROOT'].'/config/environment.inc.php');
// Include local config
if (is_file($GLOBALS['APPLICATION_ROOT'].'/config/environment.local.inc.php')) {
  include($GLOBALS['APPLICATION_ROOT'].'/config/environment.local.inc.php');
}
// Include env config
if (is_file($GLOBALS['APPLICATION_ROOT'].'/config/environments/'.$GLOBALS['ENVIRONMENT'].'.php')) {
  include($GLOBALS['APPLICATION_ROOT'].'/config/environments/'.$GLOBALS['ENVIRONMENT'].'.php');
}
// Include local env config
if (is_file($GLOBALS['APPLICATION_ROOT'].'/config/environments/'.$GLOBALS['ENVIRONMENT'].'.local.php')) {
  include($GLOBALS['APPLICATION_ROOT'].'/config/environments/'.$GLOBALS['ENVIRONMENT'].'.local.php');
}

// Load routes
include($GLOBALS['APPLICATION_ROOT'].'/config/routes.inc.php');

// Include autoload mapping
if (is_file($GLOBALS['APPLICATION_ROOT'].'/config/autoload.inc.php')) {
  include($GLOBALS['APPLICATION_ROOT'].'/config/autoload.inc.php');
}

// Init plugins
foreach ($GLOBALS['PLUGINS'] as $plugin => $path) {
  if (is_file($path.'/bootstrap.inc.php')) {
    include($path.'/bootstrap.inc.php');
  }
}

