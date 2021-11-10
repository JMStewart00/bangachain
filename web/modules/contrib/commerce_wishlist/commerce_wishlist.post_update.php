<?php

/**
 * @file
 * Post update functions for Wishlist.
 */

/**
 * Revert the 'commerce_wishlist_item_table' view - fix broken handler.
 */
function commerce_wishlist_post_update_1() {
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');
  $result = $config_updater->revert([
    'views.view.commerce_wishlist_item_table',
  ]);
  $message = implode('<br>', $result->getFailed());

  return $message;
}

/**
 * Revert the 'commerce_wishlists' view - remove the 'link_to_entity'.
 */
function commerce_wishlist_post_update_2() {
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');
  $result = $config_updater->revert([
    'views.view.commerce_wishlists',
  ]);
  $message = implode('<br>', $result->getFailed());

  return $message;
}
