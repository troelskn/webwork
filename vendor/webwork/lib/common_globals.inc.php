<?php
// This file contains various global functions used for bindings to external libraries, such as database and mailer
// Configuration of these happens in environment files.

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
 * Default db constructor.
 */
function create_pdo($params) {
  return new pdo($params['dsn'], $params['username'], $params['password']);
}

/**
 * db constructor that returns an instance of pdoext.
 * You need to have pdoext in `/vendor/pdoext` to use this.
 */
function create_pdoext($params) {
  require_once 'pdoext.inc.php';
  $db = new pdoext_Connection($params['dsn'], $params['username'], $params['password']);
  if (isset($params['object_cache']) && $params['object_cache']) {
    $db->enableCache();
  }
  if (isset($params['table_name_mapping'])) {
    $db->setTableNameMapping($params['table_name_mapping']);
  }
  if (isset($params['log_file'])) {
    $db->setLogging($params['log_file'], isset($params['log_time']) ? $params['log_time'] : null);
  }
  if (preg_match('/^mysql:.*;charset=UTF-8$/', $params['dsn'])) {
    $db->query("SET NAMES UTF8");
  }
  return $db;
}

/**
 * Default postman constructor.
 */
function create_dummy_postman($params) {
  return new PostmanDummy();
}

/**
 * Swift mailer postman constructor.
 * You need to have Swift mailer in `vendor/swift_mailer`
 */
function create_swift_mailer_postman($params) {
  require_once 'swift_required.php';
  switch ($params['transport_type']) {
  case 'smtp':
    $transport = Swift_SmtpTransport::newInstance($params['host']);
    if (isset($params['port'])) {
      $transport->setPort($params['port']);
    }
    if (isset($params['username'])) {
      $transport->setUsername($params['username']);
      $transport->setPassword($params['password']);
    }
    break;
  case 'sendmail':
    if (isset($params['sendmail_command'])) {
      $transport = Swift_SendmailTransport::newInstance($params['sendmail_command']);
    } else {
      $transport = Swift_SendmailTransport::newInstance();
    }
    break;
  case 'mail':
    $transport = Swift_MailTransport::newInstance();
    break;
  }
  return new SwiftMailerPostman($transport);
}

/**
 * Just a dummy implementation. Should be replaced with something that *actually* mails out.
 */
class PostmanDummy {
  function deliver($file_name, $params = array()) {
    debug("PostmanDummy#deliver $file_name");
  }
}

/**
 * Mailer bindings to Swift mailer
 */
class SwiftMailerPostman {
  protected $mailer;
  function __construct($transport) {
    $this->mailer = Swift_Mailer::newInstance($transport);
  }
  function deliver($file_name, $params = array()) {
    $message = Swift_Message::newInstance();
    extract($params);
    include(resolve_file_with_plugins('/mail_handlers/'.$file_name.'.php'));
    $this->mailer->send($message);
  }
}
