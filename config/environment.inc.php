<?php
// You can make local overrides to these settings by creating an environment.local.inc.php
$GLOBALS['DATABASE_CONNECTION'] = array(
  'constructor' => 'create_pdo',
  'user' => null,
  'pass' => null
);

$GLOBALS['POSTMAN'] = array(
  'constructor' => 'create_dummy_postman'
);

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
  return new pdo($params['dsn'], $params['user'], $params['pass']);
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

/**
 * Just a dummy implementation. Should be replaced with somethin that *actually* mails out.
 */
class PostmanDummy {
  function deliver($template, $data = array()) {
    debug("PostmanDummy#deliver $template");
  }
}