<?php

/**
 * Automated Drupal installation script
 */

use Drupal\Core\Database\Database;
use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

$autoloader = require_once '/var/www/html/vendor/autoload.php';

// Initialize Drupal kernel
$request = Request::createFromGlobals();
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();

// Install Drupal
$connection = Database::getConnection();

// Check if Drupal is already installed
try {
  $connection->query("SELECT 1 FROM {users} LIMIT 1")->fetchField();
  echo "Drupal is already installed.\n";
  return;
} catch (Exception $e) {
  echo "Installing Drupal...\n";
}

// Install Drupal using Drush-like approach
$install_profile = 'standard';

// Run the installation
system('cd /var/www/html && drush site:install standard --db-url=sqlite://sites/default/files/drupal.sqlite --site-name="Viostream Demo" --account-name=admin --account-pass=admin --yes');

echo "Drupal installation complete.\n";

// Enable the viostream module
system('cd /var/www/html && drush en viostream field link -y');
echo "Viostream module enabled.\n";

// Create a basic page content type with viostream field
system('cd /var/www/html && drush generate:content-types --content-types="Demo Page" --fields="field_video:link:Viostream Video Link" --bundles=1');

// Set the field formatter to use viostream
$config_data = [
  'targetEntityType' => 'node',
  'bundle' => 'demo_page',
  'mode' => 'default',
  'content' => [
    'field_video' => [
      'type' => 'viostream_video',
      'weight' => 1,
      'region' => 'content',
      'settings' => [
        'width' => '100%',
        'height' => '400',
        'responsive' => TRUE,
        'autoplay' => FALSE,
        'muted' => FALSE,
        'controls' => TRUE,
      ],
      'third_party_settings' => [],
    ],
  ],
];

// This would need to be done through proper Drupal API calls
// but for demo purposes, we'll do it via drush config
system('cd /var/www/html && drush config:set core.entity_view_display.node.demo_page.default content.field_video.type viostream_video -y');

echo "Demo content type configured.\n";

// Clear caches
system('cd /var/www/html && drush cr');

echo "Setup complete! Admin user: admin / admin\n";