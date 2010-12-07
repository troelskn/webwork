<?php
// You can make local overrides to these settings by creating a config.local.inc.php
$database_connection = array(
  'constructor' => 'create_pdo',
  'driver' => 'mysql',
  'host' => 'localhost',
  'database' => 'application_development',
  'user' => 'root',
  'pass' => null,
);

/**
 * Returns a database connection object.
 */
function db() {
  global $database_connection;
  if (!isset($database_connection['instance'])) {
    $ctor = $database_connection['constructor'];
    $database_connection['instance'] = call_user_func($ctor, $database_connection);
  }
  return $database_connection['instance'];
}

function create_pdo($params) {
  $dsn = $params['driver'].":host=".$params['host'].";dbname=".$params['database'].";charset=UTF-8";
  return new pdo($dsn, $params['user'], $params['pass']);
}