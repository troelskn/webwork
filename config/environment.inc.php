<?php
// This file contains global configuration settings, that are common for all environments
// You can make local overrides to these settings by creating an `environment.local.inc.php`
// Settings herein can be overridden per environment in the `config/environments/` folder.

// Timezone settings. Adjust to fit your setup.
date_default_timezone_set('Europe/Copenhagen');

// framework level debugging info that you can turn on or off
$GLOBALS['WEBWORK_LOGGING']['render'] = true;
$GLOBALS['WEBWORK_LOGGING']['request'] = true;
$GLOBALS['WEBWORK_LOGGING']['dispatch'] = true;

// Database connection settings
$GLOBALS['DATABASE_CONNECTIONS'] = array(
  'default' => array(
    'constructor' => 'create_pdo',
    'username' => null,
    'password' => null
  )
);

// If the public directory is a subdir on the web server where it's served, set the HREF_BASE below
$GLOBALS['HREF_BASE'] = '';

// To use pdoext instead of plain pdo, uncomment this section:
//
// // Tell webwork to use pdoext
// $GLOBALS['DATABASE_CONNECTIONS']['default']['constructor'] = 'create_pdoext';
// // Enable logging
// $GLOBALS['DATABASE_CONNECTIONS']['default']['log_file'] = '/var/log/pdoext_log.txt';
// // Only log slow queries
// $GLOBALS['DATABASE_CONNECTIONS']['default']['log_time'] = 0.5;

// Mailer settings
$GLOBALS['POSTMAN'] = array(
  'constructor' => 'create_dummy_postman'
);

// By default, no mails are sent through the dummy mailer. Configure a mailer by setting the constructor to swift mailer:
//
// // Tell webwork to use Swift mailer
// $GLOBALS['POSTMAN']['constructor'] = 'create_swift_mailer_postman';
//
// Then use one of the following:
//
// // Mail through smtp
// $GLOBALS['POSTMAN']['transport_type'] = 'smtp';
// $GLOBALS['POSTMAN']['host'] = 'smtp.example.org';
// $GLOBALS['POSTMAN']['port'] = 25;
// $GLOBALS['POSTMAN']['username'] = 'username';
// $GLOBALS['POSTMAN']['password'] = 'secret';
//
// // Mail through sendmail
// $GLOBALS['POSTMAN']['transport_type'] = 'sendmail';
//
// // Mail through php mail
// $GLOBALS['POSTMAN']['transport_type'] = 'mail';
