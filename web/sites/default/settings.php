<?php

/**
 * Oomph, Inc. Drupal Scaffold - Drupal 8 Settings
 *
 * This file contains the default settings required to have a functioning site
 * with Lando. You can define your own settings in settings.local.php to get a
 * working instance of Drupal outside of Lando.
 *
 * Note: After a Drupal installation, Drupal _may_ add an installation profile
 * definition to the end of this file. It is recommended to NOT add this change
 * to version control. It is safe to delete it if present in this file.
 */

// Disable all config splits by default so they can be enabled when appropriate
$config['config_split.config_split.local']['status'] = FALSE;
$config['config_split.config_split.dev']['status'] = FALSE;
$config['config_split.config_split.uat']['status'] = FALSE;
$config['config_split.config_split.prod']['status'] = FALSE;

if (getenv('LANDO_INFO') && !defined('PANTHEON_ENVIRONMENT')) {
  $lando_info = json_decode(getenv('LANDO_INFO'), TRUE);

  $databases['default']['default'] = [
    'database' => $lando_info['database']['creds']['database'],
    'username' => $lando_info['database']['creds']['user'],
    'password' => $lando_info['database']['creds']['password'],
    'prefix' => '',
    'host' => $lando_info['database']['internal_connection']['host'],
    'port' => $lando_info['database']['internal_connection']['port'],
    'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
    'driver' => 'mysql',
  ];

  // Default hash salt for Lando.
  $settings['hash_salt'] = 'BfHE?EG)vJPa3uikBCZWW#ATbDLijMFRZgfkyayYcZYoy>eC7QhdG7qaB4hcm4x$';

  // Allow any domains to access the site with Lando.
  $settings['trusted_host_patterns'] = [
    '^(.+)$',
  ];

  // Enable the local config split for Lando environments
  $config['config_split.config_split.local']['status'] = TRUE;
}

$pantheon_settings_file = __DIR__ . '/settings.pantheon.php';

if (file_exists($pantheon_settings_file)) {
  require $pantheon_settings_file;
}

// Config sync directory
$settings['config_sync_directory'] = '../config/common';

$local_settings_file = __DIR__ . '/settings.local.php';

if (file_exists($local_settings_file)) {
  require $local_settings_file;
}

/**
 * Set config split to true based on the PANTHEON_ENVIRONMENT envvar
 */
if (defined('PANTHEON_ENVIRONMENT')) {
  $pantheon_env = getenv('PANTHEON_ENVIRONMENT');

  // Set the active config split based on the Pantheon environment variable
  switch ($pantheon_env) {
    case 'dev':
      $config['config_split.config_split.dev']['status'] = TRUE;
      break;

    case 'test':
      $config['config_split.config_split.uat']['status'] = TRUE;
      break;

    case 'live':
      $config['config_split.config_split.prod']['status'] = TRUE;
      break;

    // Default to dev config for unknown environments
    default:
      $config['config_split.config_split.dev']['status'] = TRUE;
  }
}

if (isset($_ENV['AH_SITE_GROUP'], $_ENV['AH_SITE_ENVIRONMENT'])) {
  $config['system.file']['path']['temporary'] = "/mnt/tmp/{$_ENV['AH_SITE_GROUP']}.{$_ENV['AH_SITE_ENVIRONMENT']}";
}
