<?php
class Migrations {
  protected $db;
  protected $dir_migrations;
  function __construct($db, $dir_migrations) {
    $this->db = $db;
    $this->dir_migrations = $dir_migrations;
  }

  function setup() {
    if ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
      // set default storage-engine to innodb
      $this->db->exec("SET storage_engine=INNODB");
    }
    // Create migrations table, if it doesn't exist
    if (!in_array('schema_migrations', $this->getTables())) {
      $this->db->exec("CREATE TABLE schema_migrations ( version varchar(255) NOT NULL PRIMARY KEY )");
    }
  }

  function getTables() {
    switch ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME)) {
      case 'mysql':
        $sql = "SHOW TABLES";
        break;
      case 'pgsql':
        $sql = "SELECT table_name AS name FROM information_schema.tables 
                WHERE table_type = 'BASE TABLE' AND table_schema NOT IN ('pg_catalog','information_schema')";
      case 'sqlite':
        $sql = 'SELECT name FROM sqlite_master WHERE type = "table"';
        break;
      default:
        throw new Exception("DB not supported");
    }
    $result = $this->db->query($sql);
    $result->setFetchMode(PDO::FETCH_NUM);
    $meta = array();
    foreach ($result as $row) {
      $meta[] = $row[0];
    }
    return $meta;
  }

  function getVersions() {
    $versions = array();
    foreach ($this->db->query("SELECT version FROM schema_migrations") as $row) {
      $versions[] = $row['version'];
    }
    return $versions;
  }

  function getMigrations() {
    return array_keys($this->getMigrationFileNames());
  }

  function getMigrationFileNameByVersion($version) {
    $files = $this->getMigrationFileNames();
    return $files[$version];
  }

  function getMigrationFileNames() {
    $migrations = array();
    foreach ($this->dir_migrations as $path) {
      foreach (scandir($path) as $entry) {
        $fullname = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $entry;
        if (preg_match('/^([0-9]+)/', $entry, $reg) && is_file($fullname)) {
          $migrations[$reg[1]] = $fullname;
        }
      }
    }
    ksort($migrations);
    return $migrations;
  }

  function getOutstanding() {
    $outstanding = array();
    $versions = $this->getVersions();
    foreach ($this->getMigrations() as $version) {
      if (!in_array($version, $versions)) {
        $outstanding[] = $version;
      }
    }
    return $outstanding;
  }

  function migrateVersion($version) {
    $filename = $this->getMigrationFileNameByVersion($version);
    echo "Running migration: " . $version ."\n";
    if (is_executable($filename)) {
      $this->executeBin($filename, $version);
    } elseif (preg_match('/\.sql$/', $filename)) {
      $this->executeSql($filename, $version);
    } elseif (preg_match('/\.php$/', $filename)) {
      $this->executePhp($filename, $version);
    } else {
      throw new Exception("Unable to process migration ".$filename);
    }
    $statement = $this->db->prepare("insert into schema_migrations values (:version)");
    $statement->execute(array(':version' => $version));
    echo "OK.\n";
  }

  function executeBin($filename, $version) {
    echo "* Executing external command:\n" . basename($filename) . "\n";
    system($filename, $retval);
    if ($retval !== 0) {
      throw new Exception("External command failed with exit code ($retval).");
    }
  }

  function executePhp($filename, $version) {
    export($GLOBALS);
    echo "* Include PHP file:\n" . basename($filename) . "\n";
    include($filename);
  }

  function executeSql($filename, $version) {
    $contents = file_get_contents($filename);
    foreach (explode(";", $contents) as $sql) {
      if (trim($sql)) {
        echo "* Executing SQL:\n" . trim($sql) . "\n";
        $this->db->exec($sql);
      }
    }
  }
}

class Fixtures {
  protected $db;
  protected $dir_fixtures;
  function __construct($db, $dir_fixtures) {
    $this->db = $db;
    $this->dir_fixtures = rtrim($dir_fixtures, DIRECTORY_SEPARATOR);
  }

  function getFixtureFileNames() {
    $prioritiesed = array();
    $arbitrary = array();
    $d = dir($this->dir_fixtures);
    while (false !== ($entry = $d->read())) {
      if ($entry[0] != '.') {
	$fullname = $this->dir_fixtures . DIRECTORY_SEPARATOR . $entry;
	if (preg_match('/^([0-9]+)/', $entry, $reg) && is_file($fullname)) {
	  $prioritiesed[$reg[1]] = $fullname;
	} elseif (is_file($fullname)) {
	  $arbitrary[] = $fullname;
	}
      }
    }
    $d->close();
    ksort($prioritiesed);
    return array_merge($prioritiesed, $arbitrary);
  }

  function migrate() {
    foreach ($this->getFixtureFileNames() as $filename) {
      if (is_executable($filename)) {
        $this->executeBin($filename);
      } elseif (preg_match('/\.sql$/', $filename)) {
        $this->executeSql($filename);
      } elseif (preg_match('/\.php$/', $filename)) {
        $this->executePhp($filename);
      } else {
        throw new Exception("Unable to process fixture ".$filename);
      }
    }
  }

  function executeBin($filename, $version) {
    system($filename, $retval);
    if ($retval !== 0) {
      throw new Exception("External command failed with exit code ($retval).");
    }
  }

  function executePhp($filename, $version) {
    export($GLOBALS);
    include($filename);
  }

  function executeSql($filename, $version) {
    $contents = file_get_contents($filename);
    foreach (explode(";", $contents) as $sql) {
      if (trim($sql)) {
        $this->db->exec($sql);
      }
    }
  }
}
