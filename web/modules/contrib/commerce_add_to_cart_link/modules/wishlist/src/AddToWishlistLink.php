<?php

namespace Drupal\commerce_add_to_wishlist_link;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;

/**
 * Defines a helper class for constructing add to wishlist links.
 */
class AddToWishlistLink {

  /**
   * The product variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $variation;

  /**
   * Constructs a new AddToWishlistLink object.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The product variation.
   */
  public function __construct(ProductVariationInterface $variation) {
    $this->variation = $variation;
  }

  /**
   * Generate a render array for an add-to-wishlist link.
   *
   * @return array
   *   The render array.
   */
  public function build() {
    $build = [
      '#theme' => 'commerce_add_to_wishlist_link',
      '#url' => $this->url(),
      '#product_variation' => $this->variation,
    ];
    $metadata = $this->metadata();
    $metadata->applyTo($build);
    return $build;
  }

  /**
   * Generate an URL object for an add-to-wishlist link.
   *
   * @return \Drupal\Core\Url
   *   The URL object.
   */
  public function url() {
    /** @var \Drupal\commerce_add_to_cart_link\CartLinkTokenInterface $cart_link_token_service */
    $cart_link_token_service = \Drupal::service('commerce_add_to_cart_link.token');
    return Url::fromRoute('commerce_add_to_wishlist_link.page',
      [
        'commerce_product' => $this->variation->getProductId(),
        'commerce_product_variation' => $this->variation->id(),
        'token' => $cart_link_token_service->generate($this->variation),
      ]
    );
  }

  /**
   * Generate metadata for an add-to-cart link.
   *
   * @return \Drupal\Core\Render\BubbleableMetadata
   *   The metadata object.
   */
  public function metadata() {
    /** @var \Drupal\commerce_add_to_cart_link\CartLinkTokenInterface $cart_link_token_service */
    $cart_link_token_service = \Drupal::service('commerce_add_to_cart_link.token');
    $contexts = $cart_link_token_service->needsCsrfProtection() ? ['session'] : [];
    return BubbleableMetadata::createFromRenderArray([
      '#cache' => [
        'contexts' => $contexts,
        'tags' => ['config:commerce_add_to_cart_link.settings'],
      ],
    ]);
  }

}
