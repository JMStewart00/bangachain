<?php

namespace Drupal\Tests\commerce_square\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Square\Environment;

/**
 * Tests the Connect service to act as application configuration.
 *
 * @group commerce_square
 */
class ConnectTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'commerce_square',
    'commerce_number_pattern',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
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
    $this->assertEquals('sandbox-sq0idp-nV_lBSwvmfIEF62s09z0-Q', $connect->getAppId(Environment::SANDBOX));
    $this->assertEquals('sandbox-sq0atb-uEZtx4_Qu36ff-kBTojVNw', $connect->getAccessToken(Environment::SANDBOX));
    $this->assertEquals(-1, $connect->getAccessTokenExpiration(Environment::SANDBOX));
    $this->assertEquals('live-sq0idp', $connect->getAppId(Environment::PRODUCTION));
    $this->assertEquals('TESTTOKEN', $connect->getAccessToken(Environment::PRODUCTION));
    $this->assertEquals($this->container->get('datetime.time')->getRequestTime(), $connect->getAccessTokenExpiration(Environment::PRODUCTION));
  }

}
