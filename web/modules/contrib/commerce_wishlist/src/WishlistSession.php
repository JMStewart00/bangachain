<?php

namespace Drupal\commerce_wishlist;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Default implementation of the wishlist session.
 */
class WishlistSession implements WishlistSessionInterface {

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Constructs a new WishlistSession object.
   *
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   */
  public function __construct(SessionInterface $session) {
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   */
  public function getWishlistIds() {
    return $this->session->get('commerce_wishlists', []);
  }

  /**
   * {@inheritdoc}
   */
  public function addWishlistId($wishlist_id) {
    $ids = $this->session->get('commerce_wishlists', []);
    $ids[] = $wishlist_id;
    $this->session->set('commerce_wishlists', array_unique($ids));
  }

  /**
   * {@inheritdoc}
   */
  public function hasWishlistId($wishlist_id) {
    $ids = $this->session->get('commerce_wishlists', []);
    return in_array($wishlist_id, $ids);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteWishlistId($wishlist) {
    $ids = $this->session->get('commerce_wishlists', []);
    $ids = array_diff($ids, [$wishlist]);
    if (!empty($ids)) {
      $this->session->set('commerce_wishlists', $ids);
    }
    else {
      // Remove the empty list to allow the system to clean up empty sessions.
      $this->session->remove('commerce_wishlists');
    }
  }

}
