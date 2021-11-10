<?php

namespace Drupal\commerce_wishlist\Plugin\Block;

use Drupal\commerce_wishlist\WishlistProviderInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a wishlist block.
 *
 * @Block(
 *   id = "commerce_wishlist",
 *   admin_label = @Translation("Wishlist"),
 *   category = @Translation("Commerce")
 * )
 */
class WishlistBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The wishlist provider.
   *
   * @var \Drupal\commerce_wishlist\WishlistProviderInterface
   */
  protected $wishlistProvider;

  /**
   * Constructs a new WishlistBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_wishlist\WishlistProviderInterface $wishlist_provider
   *   The wishlist provider.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, WishlistProviderInterface $wishlist_provider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->wishlistProvider = $wishlist_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_wishlist.wishlist_provider')
    );
  }

  /**
   * Builds the wishlist block.
   *
   * @return array
   *   A render array.
   */
  public function build() {
    /** @var \Drupal\commerce_wishlist\Entity\WishlistInterface[] $wishlists */
    $wishlist = $this->wishlistProvider->getWishlist('default');
    $count = $wishlist ? count($wishlist->getItems()) : 0;

    return [
      '#theme' => 'commerce_wishlist_block',
      '#count' => $count,
      '#count_text' => $this->formatPlural($count, '@count item', '@count items', [], ['context' => 'wishlist block']),
      '#wishlist_entity' => $wishlist,
      '#url' => Url::fromRoute('commerce_wishlist.page'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['wishlist']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cache_tags = parent::getCacheTags();
    $wishlist_cache_tags = [];

    /** @var \Drupal\commerce_wishlist\Entity\WishlistInterface[] $wishlists */
    $wishlists = $this->wishlistProvider->getWishlists();
    foreach ($wishlists as $wishlist) {
      // Add tags for all wishlists regardless items.
      $wishlist_cache_tags = Cache::mergeTags($wishlist_cache_tags, $wishlist->getCacheTags());
    }
    return Cache::mergeTags($cache_tags, $wishlist_cache_tags);
  }

}
