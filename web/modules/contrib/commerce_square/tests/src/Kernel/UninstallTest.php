<?php

namespace Drupal\Tests\commerce_square\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the uninstall of the module.
 *
 * @group commerce_square
 */
class UninstallTest extends KernelTestBase {

  public static $modules = [
    'system',
    'commerce_square',
    'commerce_number_pattern',
  ];

  /**
   * Tests OAuth data is removed from the state key value store.
   */
  public function testUninstallRemoveAuthData() {
    $this->container->get('state')->set('commerce_square.production_access_token', 'TESTTOKEN');
    $this->container->get('state')->set('commerce_square.production_access_token_expiry', $this->container->get('datetime.time')->getRequestTime());

    $this->container->get('module_installer')->uninstall(['commerce_square']);

    $this->assertNull($this->container->get('state')->get('commerce_square.production_access_token'));
    $this->assertNull($this->container->get('state')->get('commerce_square.production_access_token_expiry'));
  }

}
