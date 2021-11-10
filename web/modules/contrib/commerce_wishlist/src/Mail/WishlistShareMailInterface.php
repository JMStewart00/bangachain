<?php

namespace Drupal\commerce_wishlist\Mail;

use Drupal\commerce_wishlist\Entity\WishlistInterface;

/**
 * Defines the interface for the wishlist share email.
 */
interface WishlistShareMailInterface {

  /**
   * Sends the wishlist share email to the given address.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistInterface $wishlist
   *   The wishlist.
   * @param string $to
   *   The address the email will be sent to.
   *
   * @return bool
   *   TRUE if the email was sent successfully, FALSE otherwise.
   */
  public function send(WishlistInterface $wishlist, $to);

}
