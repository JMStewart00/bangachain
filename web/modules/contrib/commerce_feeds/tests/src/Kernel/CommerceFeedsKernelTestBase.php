<?php

namespace Drupal\Tests\commerce_feeds\Kernel;

use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Drupal\Tests\feeds\Traits\FeedCreationTrait;
use Drupal\Tests\feeds\Traits\FeedsCommonTrait;

/**
 * Provides a base class for Commerce Feeds kernel tests.
 */
abstract class CommerceFeedsKernelTestBase extends CommerceKernelTestBase {

  use FeedCreationTrait;
  use FeedsCommonTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'path',
    'feeds',
    'commerce_feeds',
    'commerce_product',
  ];

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Install database schemes.
    $this->installEntitySchema('feeds_feed');
    $this->installEntitySchema('feeds_subscription');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installSchema('feeds', 'feeds_clean_list');
    $this->installConfig(['commerce_product']);

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);
    $this->container->get('current_user')->setAccount($user);
  }

  /**
   * Returns the absolute directory path of the Commerce Feeds module.
   *
   * @return string
   *   The absolute path to the Feeds module.
   */
  protected function absolutePath() {
    return $this->absolute() . '/' . drupal_get_path('module', 'commerce_feeds');
  }

}
