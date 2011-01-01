<?php
// You can make local overrides to these settings by creating a config.local.inc.php
$GLOBALS['DATABASE_CONNECTION'] = array(
  'constructor' => 'create_pdo',
  'driver' => 'mysql',
  'host' => 'localhost',
  'database' => 'application_development',
  'user' => 'root',
  'pass' => null,
);

$GLOBALS['POSTMAN'] = array(
  'constructor' => 'create_dummy_postman');


/**
 * Returns a database connection object.
 */
function db() {
  if (!isset($GLOBALS['DATABASE_CONNECTION']['instance'])) {
    $ctor = $GLOBALS['DATABASE_CONNECTION']['constructor'];
    $GLOBALS['DATABASE_CONNECTION']['instance'] = call_user_func($ctor, $GLOBALS['DATABASE_CONNECTION']);
  }
  return $GLOBALS['DATABASE_CONNECTION']['instance'];
}

/**
 * Default db constructor.
 */
function create_pdo($params) {
  $dsn = $params['driver'].":host=".$params['host'].";dbname=".$params['database'].";charset=UTF-8";
  return new pdo($dsn, $params['user'], $params['pass']);
}

/**
 * Returns a postman object.
 */
function postman() {
  if (!isset($GLOBALS['POSTMAN']['instance'])) {
    $ctor = $GLOBALS['POSTMAN']['constructor'];
    $GLOBALS['POSTMAN']['instance'] = call_user_func($ctor, $GLOBALS['POSTMAN']);
  }
  return $GLOBALS['POSTMAN']['instance'];
}

/**
 * Default postman constructor.
 */
function create_dummy_postman($params) {
  return new PostmanDummy();
}

class PostmanDummy {
  function deliver($template, $data = array()) {
    debug("PostmanDummy#deliver $template");
  }
}