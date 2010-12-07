<?php
// You can make local overrides to these settings by creating a config.local.inc.php
$database_connection = array(
  'constructor' => 'pdo',
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
    $dsn = $database_connection['driver'].":host=".$database_connection['host'].";dbname=".$database_connection['database'].";charset=UTF-8";
    $ctor = $database_connection['constructor'];
    $database_connection['instance'] = new $ctor($dsn, $database_connection['user'], $database_connection['pass']);
  }
  return $database_connection['instance'];
}