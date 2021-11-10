<?php

namespace Drupal\commerce_wishlist\Entity;

use Drupal\Component\Utility\Random;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Url;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the wishlist entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_wishlist",
 *   label = @Translation("Wishlist"),
 *   label_collection = @Translation("Wishlists"),
 *   label_singular = @Translation("wishlist"),
 *   label_plural = @Translation("wishlists"),
 *   label_count = @PluralTranslation(
 *     singular = "@count wishlist",
 *     plural = "@count wishlists",
 *   ),
 *   bundle_label = @Translation("Wishlist type"),
 *   handlers = {
 *     "event" = "Drupal\commerce_wishlist\Event\WishlistEvent",
 *     "storage" = "Drupal\commerce_wishlist\WishlistStorage",
 *     "access" = "Drupal\entity\UncacheableEntityAccessControlHandler",
 *     "query_access" = "Drupal\entity\QueryAccess\UncacheableQueryAccessHandler",
 *     "permission_provider" = "Drupal\entity\UncacheableEntityPermissionProvider",
 *     "list_builder" = "Drupal\commerce_wishlist\WishlistListBuilder",
 *     "views_data" = "Drupal\commerce\CommerceEntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\commerce_wishlist\Form\WishlistForm",
 *       "add" = "Drupal\commerce_wishlist\Form\WishlistForm",
 *       "edit" = "Drupal\commerce_wishlist\Form\WishlistForm",
 *       "duplicate" = "Drupal\commerce_wishlist\Form\WishlistForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "user" = "Drupal\commerce_wishlist\Form\WishlistUserForm",
 *       "share" = "Drupal\commerce_wishlist\Form\WishlistShareForm",
 *     },
 *     "local_task_provider" = {
 *       "default" = "Drupal\entity\Menu\DefaultEntityLocalTaskProvider",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\commerce_wishlist\WishlistRouteProvider",
 *       "delete-multiple" = "Drupal\entity\Routing\DeleteMultipleRouteProvider",
 *     },
 *   },
 *   base_table = "commerce_wishlist",
 *   admin_permission = "administer commerce_wishlist",
 *   permission_granularity = "bundle",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "wishlist_id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "uid" = "uid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "add-page" = "/admin/commerce/wishlists/add",
 *     "add-form" = "/admin/commerce/wishlists/add/{commerce_wishlist_type}",
 *     "edit-form" = "/admin/commerce/wishlists/{commerce_wishlist}/edit",
 *     "duplicate-form" = "/admin/commerce/wishlists/{commerce_wishlist}/duplicate",
 *     "delete-form" = "/admin/commerce/wishlists/{commerce_wishlist}/delete",
 *     "delete-multiple-form" = "/admin/commerce/wishlists/delete",
 *     "collection" = "/admin/commerce/wishlists",
 *     "user-form" = "/user/{user}/wishlist/{code}",
 *     "share-form" = "/user/{user}/wishlist/{code}/share",
 *   },
 *   bundle_entity_type = "commerce_wishlist_type",
 *   field_ui_base_route = "entity.commerce_wishlist_type.edit_form"
 * )
 */
class Wishlist extends ContentEntityBase implements WishlistInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function createDuplicate() {
    $duplicate = parent::createDuplicate();
    // Unique code cannot be transferred because their codes are unique.
    $duplicate->set('code', NULL);
    // We don't duplicate wishlist items.
    $duplicate->set('wishlist_items', []);

    return $duplicate;
  }

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'canonical', array $options = []) {
    // Can't declare "canonical" as a link template because it requires a
    // custom parameter, which breaks contribs that don't expect it.
    // StringFormatter assumes 'revision' is always a valid link template.
    if (in_array($rel, ['canonical', 'revision'])) {
      $route_name = 'entity.commerce_wishlist.canonical';
      $route_parameters = [
        'code' => $this->getCode(),
      ];
      $options += [
        'entity_type' => 'commerce_wishlist',
        'entity' => $this,
        // Display links by default based on the current language.
        'language' => $this->language(),
      ];
      return new Url($route_name, $route_parameters, $options);
    }
    else {
      return parent::toUrl($rel, $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    if (in_array($rel, ['user-form', 'share-form'])) {
      return [
        'user' => $this->getOwnerId(),
        'code' => $this->getCode(),
      ];
    }
    else {
      return parent::urlRouteParameters($rel);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCode() {
    return $this->get('code')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCode($code) {
    $this->set('code', $code);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getShippingProfile() {
    return $this->get('shipping_profile')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setShippingProfile(ProfileInterface $profile) {
    $this->set('shipping_profile', $profile);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getItems() {
    return $this->get('wishlist_items')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function setItems(array $wishlist_items) {
    $this->set('wishlist_items', $wishlist_items);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasItems() {
    return !$this->get('wishlist_items')->isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function addItem(WishlistItemInterface $wishlist_item) {
    if (!$this->hasItem($wishlist_item)) {
      $this->get('wishlist_items')->appendItem($wishlist_item);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeItem(WishlistItemInterface $wishlist_item) {
    $index = $this->getItemIndex($wishlist_item);
    if ($index !== FALSE) {
      $this->get('wishlist_items')->offsetUnset($index);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasItem(WishlistItemInterface $wishlist_item) {
    return $this->getItemIndex($wishlist_item) !== FALSE;
  }

  /**
   * Gets the index of the given wishlist item.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item
   *   The wishlist item.
   *
   * @return int|bool
   *   The index of the given wishlist item, or FALSE if not found.
   */
  protected function getItemIndex(WishlistItemInterface $wishlist_item) {
    $values = $this->get('wishlist_items')->getValue();
    $wishlist_item_ids = array_map(function ($value) {
      return $value['target_id'];
    }, $values);

    return array_search($wishlist_item->id(), $wishlist_item_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function isDefault() {
    return (bool) $this->get('is_default')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefault($default) {
    $this->set('is_default', (bool) $default);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublic() {
    return (bool) $this->get('is_public')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPublic($public) {
    $this->set('is_public', (bool) $public);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getKeepPurchasedItems() {
    return (bool) $this->get('keep_purchased_items')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setKeepPurchasedItems($keep_purchased_items) {
    $this->set('keep_purchased_items', (bool) $keep_purchased_items);
    return $this;
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
  public function preSave(EntityStorageInterface $storage) {
    /** @var \Drupal\commerce_wishlist\WishlistStorageInterface $storage */
    parent::preSave($storage);

    if ($this->get('code')->isEmpty()) {
      /** @var \Drupal\commerce_wishlist\WishlistStorageInterface $storage */
      $storage = $this->entityTypeManager()->getStorage('commerce_wishlist');
      $random = new Random();
      $code = $random->word(13);
      // Ensure code uniqueness. Collisions are rare, but possible.
      while ($storage->loadByCode($code)) {
        $code = $random->word(13);
      }
      $this->setCode($random->word(13));
    }
    // Mark the wishlist as default if there's no other default.
    if ($this->getOwnerId() && !$this->isDefault()) {
      $wishlist = $storage->loadDefaultByUser($this->getOwner(), $this->bundle());
      if (!$wishlist || !$wishlist->isDefault()) {
        $this->setDefault(TRUE);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    /** @var \Drupal\commerce_wishlist\WishlistStorageInterface $storage */
    parent::postSave($storage, $update);

    // Ensure there's a back-reference on each wishlist item.
    foreach ($this->getItems() as $wishlist_item) {
      if ($wishlist_item->wishlist_id->isEmpty()) {
        $wishlist_item->wishlist_id = $this->id();
        $wishlist_item->save();
      }
    }

    if ($this->getOwnerId()) {
      $default = $this->isDefault();
      $original_default = $this->original ? $this->original->isDefault() : FALSE;
      if ($default && !$original_default) {
        $wishlists = $storage->loadMultipleByUser($this->getOwner(), $this->bundle());
        foreach ($wishlists as $wishlist) {
          if ($wishlist->id() != $this->id() && $wishlist->isDefault()) {
            $wishlist->setDefault(FALSE);
            $wishlist->save();
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Delete the wishlist items of a deleted wishlist.
    $wishlist_items = [];
    /** @var \Drupal\commerce_wishlist\Entity\WishlistInterface $entity */
    foreach ($entities as $entity) {
      foreach ($entity->getItems() as $wishlist_item) {
        $wishlist_items[$wishlist_item->id()] = $wishlist_item;
      }
    }
    /** @var \Drupal\commerce_wishlist\WishlistItemStorageInterface $wishlist_item_storage */
    $wishlist_item_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_wishlist_item');
    $wishlist_item_storage->delete($wishlist_items);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Code'))
      ->setDescription(t('The wishlist code.'))
      ->setSetting('max_length', 25)
      ->addConstraint('UniqueField', []);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The wishlist name.'))
      ->setRequired(TRUE)
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid']->setLabel(t('Owner'))
      ->setDescription(t('The wishlist owner.'))
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['shipping_profile'] = BaseFieldDefinition::create('entity_reference_revisions')
      ->setLabel(t('Shipping profile'))
      ->setDescription(t('Shipping profile'))
      ->setSetting('target_type', 'profile')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', ['target_bundles' => ['customer']])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
        'settings' => [],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['wishlist_items'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Wishlist items'))
      ->setDescription(t('The wishlist items.'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'commerce_wishlist_item')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'type' => 'commerce_wishlist_item_table',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_default'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Default'))
      ->setDescription(t('A boolean indicating whether the wishlist is the default one.'));

    $fields['is_public'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Public'))
      ->setDescription(t('Whether the wishlist is public.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 19,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['keep_purchased_items'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Keep purchased items in the list'))
      ->setDescription(t('Whether items should remain in the wishlist once purchased.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time when the wishlist was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time when the wishlist was last edited.'));

    return $fields;
  }

}
