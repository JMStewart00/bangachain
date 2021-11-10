<?php

namespace Drupal\commerce_wishlist\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the wishlist type entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_wishlist_type",
 *   label = @Translation("Wishlist type"),
 *   label_collection = @Translation("Wishlist types"),
 *   label_singular = @Translation("wishlist type"),
 *   label_plural = @Translation("wishlist types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count wishlist type",
 *     plural = "@count wishlist types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\commerce_wishlist\Form\WishlistTypeForm",
 *       "edit" = "Drupal\commerce_wishlist\Form\WishlistTypeForm",
 *       "delete" = "Drupal\commerce\Form\CommerceBundleEntityDeleteFormBase"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\commerce_wishlist\WishlistTypeListBuilder",
 *   },
 *   admin_permission = "administer commerce_wishlist_type",
 *   config_prefix = "commerce_wishlist_type",
 *   bundle_of = "commerce_wishlist",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "label",
 *     "id",
 *     "allowAnonymous",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/wishlist-types/add",
 *     "edit-form" = "/admin/commerce/config/wishlist-types/{commerce_wishlist_type}/edit",
 *     "delete-form" = "/admin/commerce/config/wishlist-types/{commerce_wishlist_type}/delete",
 *     "collection" = "/admin/commerce/config/wishlist-types"
 *   }
 * )
 */
class WishlistType extends ConfigEntityBundleBase implements WishlistTypeInterface {

  /**
   * The wishlist type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The wishlist type label.
   *
   * @var string
   */
  protected $label;

  /**
   * Whether the wishlist item type allows anonymous wishlists.
   *
   * @var bool
   */
  protected $allowAnonymous;

  /**
   * {@inheritdoc}
   */
  public function isAllowAnonymous() {
    return $this->allowAnonymous;
  }

  /**
   * {@inheritdoc}
   */
  public function setAllowAnonymous($allow_anonymous) {
    $this->allowAnonymous = $allow_anonymous;
    return $this;
  }

}
