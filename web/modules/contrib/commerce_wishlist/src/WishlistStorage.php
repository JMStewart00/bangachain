<?php

namespace Drupal\commerce_wishlist;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the wishlist storage.
 */
class WishlistStorage extends CommerceContentEntityStorage implements WishlistStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadByCode($code) {
    $wishlists = $this->loadByProperties(['code' => $code]);
    $wishlist = reset($wishlists);

    return $wishlist ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadDefaultByUser(AccountInterface $account, $wishlist_type_id) {
    $query = $this->getQuery();
    $query
      ->condition('uid', $account->id())
      ->condition('is_default', TRUE)
      ->condition('type', $wishlist_type_id)
      ->sort('is_default', 'DESC')
      ->sort('wishlist_id', 'DESC')
      ->range(0, 1)
      ->accessCheck(FALSE);
    $result = $query->execute();

    return $result ? $this->load(reset($result)) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultipleByUser(AccountInterface $account, $wishlist_type_id) {
    $query = $this->getQuery();
    $query
      ->condition('uid', $account->id())
      ->condition('type', $wishlist_type_id)
      ->sort('is_default', 'DESC')
      ->sort('wishlist_id', 'DESC')
      ->accessCheck(FALSE);
    $result = $query->execute();

    return $result ? $this->loadMultiple($result) : [];
  }

}
