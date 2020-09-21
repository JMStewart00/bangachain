<?php

namespace Drupal\Tests\commerce_square\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the Connect service to act as application configuration.
 *
 * @group commerce_square
 */
class ConnectTest extends KernelTestBase {

  public static $modules = [
    'commerce_square',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->container->get('config.factory')
      ->getEditable('commerce_square.settings')
      ->set('app_name', 'Testing')
      ->set('app_secret', 'Test secret')
      ->set('sandbox_app_id', 'sandbox-sq0idp-nV_lBSwvmfIEF62s09z0-Q')
      ->set('sandbox_access_token', 'sandbox-sq0atb-uEZtx4_Qu36ff-kBTojVNw')
      ->set('production_app_id', 'live-sq0idp')
      ->save();
    $this->container->get('state')->set('commerce_square.production_access_token', 'TESTTOKEN');
    $this->container->get('state')->set('commerce_square.production_access_token_expiry', $this->container->get('datetime.time')->getRequestTime());
  }

  /**
   * Tests the methods.
   */
  public function testConnectService() {
    $connect = $this->container->get('commerce_square.connect');
    $this->assertEquals('Testing', $connect->getAppName());
    $this->assertEquals('Test secret', $connect->getAppSecret());
    $this->assertEquals('sandbox-sq0idp-nV_lBSwvmfIEF62s09z0-Q', $connect->getAppId('sandbox'));
    $this->assertEquals('sandbox-sq0atb-uEZtx4_Qu36ff-kBTojVNw', $connect->getAccessToken('sandbox'));
    $this->assertEquals(-1, $connect->getAccessTokenExpiration('sandbox'));
    $this->assertEquals('live-sq0idp', $connect->getAppId('production'));
    $this->assertEquals('TESTTOKEN', $connect->getAccessToken('production'));
    $this->assertEquals($this->container->get('datetime.time')->getRequestTime(), $connect->getAccessTokenExpiration('production'));
  }
}
