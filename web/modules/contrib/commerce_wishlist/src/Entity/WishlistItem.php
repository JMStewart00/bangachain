<?php

namespace Drupal\commerce_wishlist\Entity;

use Drupal\commerce_wishlist\WishlistPurchase;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines the wishlist item entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_wishlist_item",
 *   label = @Translation("Wishlist item"),
 *   label_singular = @Translation("wishlist item"),
 *   label_plural = @Translation("wishlist items"),
 *   label_count = @PluralTranslation(
 *     singular = "@count wishlist item",
 *     plural = "@count wishlist items",
 *   ),
 *   bundle_label = @Translation("Wishlist item type"),
 *   handlers = {
 *     "event" = "Drupal\commerce_wishlist\Event\WishlistItemEvent",
 *     "storage" = "Drupal\commerce_wishlist\WishlistItemStorage",
 *     "access" = "Drupal\commerce_wishlist\WishlistItemAccessControlHandler",
 *     "permission_provider" = "Drupal\commerce_wishlist\WishlistItemPermissionProvider",
 *     "list_builder" = "Drupal\commerce_wishlist\WishlistItemListBuilder",
 *     "views_data" = "Drupal\commerce_wishlist\WishlistItemViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\commerce_wishlist\Form\WishlistItemForm",
 *       "edit" = "Drupal\commerce_wishlist\Form\WishlistItemForm",
 *       "duplicate" = "Drupal\commerce_wishlist\Form\WishlistItemForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "details" = "Drupal\commerce_wishlist\Form\WishlistItemDetailsForm",
 *     },
 *     "local_task_provider" = {
 *       "default" = "Drupal\entity\Menu\DefaultEntityLocalTaskProvider",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\commerce_wishlist\WishlistItemRouteProvider",
 *     },
 *     "inline_form" = "Drupal\commerce_wishlist\Form\WishlistItemInlineForm",
 *   },
 *   base_table = "commerce_wishlist_item",
 *   admin_permission = "administer commerce_wishlist",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "wishlist_item_id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/wishlists/{commerce_wishlist}/items/add",
 *     "edit-form" = "/admin/commerce/wishlists/{commerce_wishlist}/items/{commerce_wishlist_item}/edit",
 *     "duplicate-form" = "/admin/commerce/wishlists/{commerce_wishlist}/items/{commerce_wishlist_item}/duplicate",
 *     "delete-form" = "/admin/commerce/wishlists/{commerce_wishlist}/items/{commerce_wishlist_item}/delete",
 *     "collection" = "/admin/commerce/wishlists/{commerce_wishlist}/items",
 *     "details-form" = "/wishlist-item/{commerce_wishlist_item}/details",
 *   },
 * )
 */
class WishlistItem extends ContentEntityBase implements WishlistItemInterface {

  use EntityChangedTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    $uri_route_parameters['commerce_wishlist'] = $this->getWishlistId();
    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getWishlist() {
    return $this->get('wishlist_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getWishlistId() {
    return $this->get('wishlist_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getPurchasableEntity() {
    return $this->get('purchasable_entity')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getPurchasableEntityId() {
    return $this->get('purchasable_entity')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getTitle();
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    $purchasable_entity = $this->getPurchasableEntity();
    if ($purchasable_entity) {
      return $purchasable_entity->label();
    }
    else {
      return $this->t('This item is no longer available');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getQuantity() {
    return (string) $this->get('quantity')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setQuantity($quantity) {
    $this->set('quantity', (string) $quantity);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getComment() {
    return $this->get('comment')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setComment($comment) {
    $this->set('comment', $comment);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPriority() {
    return $this->get('priority')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPriority($priority) {
    $this->set('priority', $priority);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPurchases() {
    return $this->get('purchases')->getPurchases();
  }

  /**
   * {@inheritdoc}
   */
  public function setPurchases(array $purchases) {
    return $this->set('purchases', $purchases);
  }

  /**
   * {@inheritdoc}
   */
  public function addPurchase(WishlistPurchase $purchase) {
    $this->get('purchases')->appendItem($purchase);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removePurchase(WishlistPurchase $purchase) {
    $this->get('purchases')->removePurchase($purchase);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPurchasedQuantity() {
    $purchased_quantity = 0;
    foreach ($this->getPurchases() as $purchase) {
      $purchased_quantity += $purchase->getQuantity();
    }
    return $purchased_quantity;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastPurchasedTime() {
    $last_purchased_time = NULL;
    if ($purchases = $this->getPurchases()) {
      $purchased_times = array_map(function (WishlistPurchase $purchase) {
        return $purchase->getPurchasedTime();
      }, $purchases);
      asort($purchased_times, SORT_NUMERIC);
      $last_purchased_time = end($purchased_times);
    }
    return $last_purchased_time;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Ensure there's a reference on each wishlist.
    $wishlist = $this->getWishlist();
    if ($wishlist && !$wishlist->hasItem($this)) {
      $wishlist->addItem($this);
      $wishlist->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['type']
      ->setSetting('max_length', EntityTypeInterface::BUNDLE_MAX_LENGTH)
      ->setSetting('is_ascii', TRUE);

    // The wishlist back reference, populated by Wishlist::postSave().
    $fields['wishlist_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Wishlist'))
      ->setDescription(t('The parent wishlist.'))
      ->setSetting('target_type', 'commerce_wishlist')
      ->setReadOnly(TRUE);

    $fields['purchasable_entity'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Purchasable entity'))
      ->setDescription(t('The purchasable entity.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    // Provide a default target_type for Views, which uses
    // base field definitions without bundle overrides.
    if (\Drupal::moduleHandler()->moduleExists('commerce_product')) {
      $fields['purchasable_entity']->setSetting('target_type', 'commerce_product_variation');
    }

    $fields['comment'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Comment'))
      ->setDescription(t('The item comment.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 25,
        'settings' => [
          'rows' => 4,
        ],
      ])
      ->setDisplayOptions('view', [
        'type' => 'string',
        'label' => 'above',
        'settings' => [],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['priority'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Priority'))
      ->setDescription(t('The item priority.'))
      ->setDefaultValue(0);

    $fields['quantity'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Quantity'))
      ->setDescription(t('The number of units.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE)
      ->setDefaultValue(1)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['purchases'] = BaseFieldDefinition::create('commerce_wishlist_purchase')
      ->setLabel(t('Purchases'))
      ->setDescription(t('The order ID, quantity and timestamp of each purchase.'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time when the wishlist item was created.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time when the wishlist item was last edited.'))
      ->setRequired(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $purchasable_entity_type = \Drupal::entityTypeManager()->getDefinition($bundle);
    $fields = [];
    $fields['purchasable_entity'] = clone $base_field_definitions['purchasable_entity'];
    $fields['purchasable_entity']->setSetting('target_type', $purchasable_entity_type->id());
    $fields['purchasable_entity']->setLabel($purchasable_entity_type->getLabel());

    return $fields;
  }

}
