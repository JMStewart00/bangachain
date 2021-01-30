<?php

/**
 * @file
 * Websocket server for customer display, runs as a stand-alone service to relay info.
 */

namespace Drupal\commerce_pos_customer_display;

use Drupal\Core\DrupalKernel;
use Ratchet\App;
use Ratchet\Server\EchoServer;
use Symfony\Component\HttpFoundation\Request;

// Super hacky, this is ripped off from Drush, should be a cleaner way.
// Set up autoloader.
if (file_exists($autoloadFile = __DIR__ . '/vendor/autoload.php')
  || file_exists($autoloadFile = __DIR__ . '/../autoload.php')
  || file_exists($autoloadFile = __DIR__ . '/../../autoload.php')
  || file_exists($autoloadFile = __DIR__ . '/../../../autoload.php')
  || file_exists($autoloadFile = __DIR__ . '/../../../../autoload.php')
  || file_exists($autoloadFile = __DIR__ . '/../../../../../autoload.php')
  || file_exists($autoloadFile = __DIR__ . '/../../../../../../autoload.php')
) {
  $autoloader = include_once $autoloadFile;
}
else {
  throw new \Exception("Could not locate autoload.php. __DIR__ is " . __DIR__);
}

// We load up the kernel just so we can pull the config settings we need.
$kernel = new DrupalKernel('prod', $autoloader);

$request = Request::createFromGlobals();
$response = $kernel->handle($request);

$websocket_host = \Drupal::configFactory()->getEditable('commerce_pos_customer_display.settings')->get('websocket_host');
$websocket_post = \Drupal::configFactory()->getEditable('commerce_pos_customer_display.settings')->get('websocket_internal_port');
$websocket_address = \Drupal::configFactory()->getEditable('commerce_pos_customer_display.settings')->get('websocket_address');

// Drop the kernel since we don't need it when running and it will probably use a ton of resources.
$kernel->terminate($request, $response);

include_once 'src/DisplayServer.php';

$app = new App($websocket_host, $websocket_post, $websocket_address);

$app->route('/display', new DisplayServer(), ['*']);

// Echo server for testing and debugging, can probably be removed one stable.
$app->route('/echo', new EchoServer(), ['*']);

$app->run();
