<?php

/**
 * Local development settings for Drupal with SQLite
 */

$databases['default']['default'] = [
  'database' => '/var/www/html/web/sites/default/files/drupal.sqlite',
  'driver' => 'sqlite',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\sqlite',
  'autoload' => 'core/lib/Drupal/Core/Database/Driver/sqlite/',
];

// Trusted host settings for Docker
$settings['trusted_host_patterns'] = [
  '^localhost$',
  '^127\.0\.0\.1$',
  '^.*\.localhost$',
];

// Disable CSS and JS aggregation for development
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;

// Disable render cache and page cache for development
$settings['cache']['bins']['render'] = 'cache.backend.null';
$settings['cache']['bins']['page'] = 'cache.backend.null';
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';

// Enable local development services
$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/development.services.yml';

// Salt for one-time login links, etc.
$settings['hash_salt'] = 'viostream-dev-salt-' . md5(__DIR__);

// Private file path
$settings['file_private_path'] = '/var/www/html/web/sites/default/files/private';

// Temporary file path
$settings['file_temp_path'] = '/tmp';