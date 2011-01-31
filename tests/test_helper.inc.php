<?php
$_SERVER['ENVIRONMENT'] = 'test';
require_once(dirname(__FILE__).'/../bootstrap.inc.php');
require_once 'migrations.inc.php';

if (preg_match('/;dbname=([^;]+)/', $GLOBALS['DATABASE_CONNECTION']['dsn'], $reg)) {
  echo "(re)creating test database $reg[1] ...";
  db()->exec('DROP DATABASE ' . $reg[1]);
  db()->exec('CREATE DATABASE ' . $reg[1]);
  $GLOBALS['DATABASE_CONNECTION']['instance'] = null; // closes connection
echo " OK\n";
}
echo "Migrating ...";
ob_start();
include($GLOBALS['APPLICATION_ROOT'].'/scripts/migrate');
ob_clean();
echo "                  OK\n";
echo "Running fixtures ...";
$test_fixtures = new Fixtures(db(), dirname(__FILE__).'/fixtures');
$test_fixtures->migrate();
echo "           OK\n";

echo "Running test suite ...\n";
require_once 'simpletest/autorun.php';