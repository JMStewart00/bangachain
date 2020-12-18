<?php

namespace Drupal\commerce_add_to_cart_link;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides the cart link token interface.
 *
 * This service is responsible for both generating and validating tokens that
 * are added to the cart links. The tokens are not tied to the user session.
 */
interface CartLinkTokenInterface {

  /**
   * Generates a token for the given product variation.
   *
   * The token is added to the add to cart link.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The product variation.
   *
   * @return string
   *   The generated token.
   */
  public function generate(ProductVariationInterface $variation);

  /**
   * Checks the given token for the given variation for validity.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The product variation.
   * @param string $token
   *   The token to be validated.
   *
   * @return bool
   *   TRUE, if the given token is valid, FALSE otherwise.
   */
  public function validate(ProductVariationInterface $variation, string $token);

  /**
   * Checks whether the given user account needs CSRF protection.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account. If left NULL, the current user will be taken.
   * @return bool
   *   TRUE, if add to cart and wishlist links should be CSRF protected, FALSE
   *   otherwise.
   */
  public function needsCsrfProtection(AccountInterface $account = NULL);

}
