<?php
// This file contains global configuration settings, that are common for all environments
// You can make local overrides to these settings by creating an `environment.local.inc.php`
// Settings herein can be overridden per environment in the `config/environments/` folder.

// Timezone settings. Adjust to fit your setup.
date_default_timezone_set('Europe/Copenhagen');
// Database connection settings
$GLOBALS['DATABASE_CONNECTION'] = array(
  'constructor' => 'create_pdo',
  'user' => null,
  'pass' => null
);

// Mailer settings
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
 * Just a dummy implementation. Should be replaced with something that *actually* mails out.
 */
class PostmanDummy {
  function deliver($template, $data = array()) {
    debug("PostmanDummy#deliver $template");
  }
}