<?php

namespace Drupal\Tests\commerce_square\Kernel;

use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests config schema upgrade for beta3 to beta4.
 *
 * @group commerce_square
 */
class SquareConfigurationUpradeTest extends CommerceKernelTestBase {

  public static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_order',
    'commerce_payment',
    'commerce_square',
    'commerce_number_pattern',
  ];

  /**
   * {@inheritdoc}
   *
   * Set to false, so we can save the old schema format.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Tests the config migrates.
   */
  public function testUpgrade1() {
    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $gateway */
    $gateway = PaymentGateway::create([
      'id' => 'square_connect',
      'label' => 'Square',
      'plugin' => 'square',
    ]);
    $gateway->getPlugin()->setConfiguration([
      'app_name' => 'Testing',
      'app_secret' => 'Test Secret',
      'test_app_id' => 'sandbox-sq0idp',
      'test_location_id' => 'test123',
      'test_access_token' => 'sandbox-sq0atb',
      'live_app_id' => 'live-sq0idp',
      'live_location_id' => 'test123',
      'live_access_token' => 'live-sq0atb',
      'live_access_token_expiry' => $this->container->get('datetime.time')->getRequestTime(),
      'mode' => 'test',
      'payment_method_types' => ['credit_card'],
    ]);
    $gateway->trustData();
    $gateway->save();

    module_load_install('commerce_square');
    commerce_square_update_8001();

    $gateway = $this->reloadEntity($gateway);
    $this->assertEquals('test123', $gateway->getPlugin()->getConfiguration()['test_location_id']);
    $this->assertEquals('test123', $gateway->getPlugin()->getConfiguration()['live_location_id']);

    $config = $this->config('commerce_square.settings');
    $this->assertEquals('Testing', $config->get('app_name'));
    $this->assertEquals('Test Secret', $config->get('app_secret'));
    $this->assertEquals('sandbox-sq0idp', $config->get('sandbox_app_id'));
    $this->assertEquals('sandbox-sq0atb', $config->get('sandbox_access_token'));
    $this->assertEquals('live-sq0idp', $config->get('production_app_id'));
    $this->assertEquals('live-sq0atb', $config->get('production_access_token'));
    $this->assertEquals($this->container->get('datetime.time')->getRequestTime(), $config->get('production_access_token_expiry'));
  }

  /**
   * Tests the upgrade to move access token to state.
   */
  public function testUpgrade2() {
    $this->testUpgrade1();
    module_load_install('commerce_square');
    commerce_square_update_8002();

    $config = $this->config('commerce_square.settings');
    $this->assertNull($config->get('production_access_token'));
    $this->assertNull($config->get('production_access_token_expiry'));

    $state = $this->container->get('state');
    $this->assertEquals('live-sq0atb', $state->get('commerce_square.production_access_token'));
    $this->assertEquals($this->container->get('datetime.time')->getRequestTime(), $state->get('commerce_square.production_access_token_expiry'));
  }

}
