<?php
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
  throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
function autoloader($class) {
  return spl_autoload(str_replace('_', '/', $class));
}
error_reporting(E_ALL);
set_error_handler('exception_error_handler');
spl_autoload_register('autoloader');

$GLOBALS['ENVIRONMENT'] = isset($_SERVER['ENVIRONMENT']) ? strtolower($_SERVER['ENVIRONMENT']) : 'development';
$GLOBALS['APPLICATION_ROOT'] = dirname(__FILE__);
$GLOBALS['HTTP_ROOT'] = 'http://localhost/';
set_include_path(get_include_path() . PATH_SEPARATOR . $GLOBALS['APPLICATION_ROOT'].'/lib/');

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

// Register thirdparty plugins
foreach (scandir($GLOBALS['APPLICATION_ROOT'].'/vendor') as $plugin) {
  if (substr($plugin, 0, 1) !== '.') {
    set_include_path(
      get_include_path() . PATH_SEPARATOR . $GLOBALS['APPLICATION_ROOT'].'/vendor/'.$plugin.'/lib');
    if (is_file($GLOBALS['APPLICATION_ROOT'].'/vendor/'.$plugin.'/bootstrap.inc.php')) {
      include($GLOBALS['APPLICATION_ROOT'].'/vendor/'.$plugin.'/bootstrap.inc.php');
    }
  }
}

