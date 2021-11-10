<?php

namespace Drupal\commerce_wishlist;

use Drupal\commerce_wishlist\Exception\DuplicateWishlistException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Default implementation of the wishlist provider.
 */
class WishlistProvider implements WishlistProviderInterface {

  use StringTranslationTrait;

  /**
   * The wishlist storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $wishlistStorage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The session.
   *
   * @var \Drupal\commerce_wishlist\WishlistSessionInterface
   */
  protected $wishlistSession;

  /**
   * The loaded wishlist data, keyed by wishlist ID, then grouped by uid.
   *
   * @var array
   *
   * Each data item is an array with the following keys:
   * - type: The wishlist type.
   *
   * Example:
   * @code
   * 1 => [
   *   10 => ['type' => 'default'],
   * ]
   * @endcode
   */
  protected $wishlistData = [];

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new WishlistProvider object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\commerce_wishlist\WishlistSessionInterface $wishlist_session
   *   The wishlist session.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, WishlistSessionInterface $wishlist_session, ConfigFactoryInterface $config_factory) {
    $this->wishlistStorage = $entity_type_manager->getStorage('commerce_wishlist');
    $this->currentUser = $current_user;
    $this->wishlistSession = $wishlist_session;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function createWishlist($wishlist_type, AccountInterface $account = NULL, $name = NULL) {
    $account = $account ?: $this->currentUser;
    $uid = $account->id();
    $allow_multiple = $this->configFactory->get('commerce_wishlist.settings')->get('allow_multiple');

    if (empty($allow_multiple) && $this->getWishlistId($wishlist_type, $account)) {
      // Don't allow multiple wishlist entities matching the same criteria.
      throw new DuplicateWishlistException("A wishlist for type '$wishlist_type' and account '$uid' already exists.");
    }

    // Create the new wishlist entity.
    $wishlist = $this->wishlistStorage->create([
      'type' => $wishlist_type,
      'uid' => $uid,
      'name' => $name ?: $this->t('Wishlist'),
      // @todo By now, we only support one wishlist per user, so we automatically set it as the default one. In the long run we may need to distinct here.
      'is_default' => TRUE,
    ]);
    $wishlist->save();
    // Store the new wishlist id in the anonymous user's session so that it can
    // be retrieved on the next page load.
    if ($account->isAnonymous()) {
      $this->wishlistSession->addWishlistId($wishlist->id());
    }
    // Wishlist data has already been loaded, add the new wishlist to the list.
    if (isset($this->wishlistData[$uid])) {
      $this->wishlistData[$uid][$wishlist->id()] = [
        'type' => $wishlist_type,
      ];
    }

    return $wishlist;
  }

  /**
   * {@inheritdoc}
   */
  public function getWishlist($wishlist_type, AccountInterface $account = NULL) {
    $wishlist = NULL;
    $wishlist_id = $this->getWishlistId($wishlist_type, $account);
    if ($wishlist_id) {
      $wishlist = $this->wishlistStorage->load($wishlist_id);
    }

    return $wishlist;
  }

  /**
   * {@inheritdoc}
   */
  public function getWishlistId($wishlist_type, AccountInterface $account = NULL) {
    $wishlist_id = NULL;
    $wishlist_data = $this->loadWishlistData($account);
    if ($wishlist_data) {
      $search = [
        'type' => $wishlist_type,
      ];
      $wishlist_id = array_search($search, $wishlist_data);
    }

    return $wishlist_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getWishlists(AccountInterface $account = NULL) {
    $wishlists = [];
    $wishlist_ids = $this->getWishlistIds($account);
    if ($wishlist_ids) {
      $wishlists = $this->wishlistStorage->loadMultiple($wishlist_ids);
    }

    return $wishlists;
  }

  /**
   * {@inheritdoc}
   */
  public function getWishlistIds(AccountInterface $account = NULL) {
    $wishlist_data = $this->loadWishlistData($account);
    return array_keys($wishlist_data);
  }

  /**
   * Loads the wishlist data for the given user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user. If empty, the current user is assumed.
   *
   * @return array
   *   The wishlist data.
   */
  protected function loadWishlistData(AccountInterface $account = NULL) {
    $account = $account ?: $this->currentUser;
    $uid = $account->id();
    if (isset($this->wishlistData[$uid])) {
      return $this->wishlistData[$uid];
    }

    if ($account->isAuthenticated()) {
      $query = $this->wishlistStorage->getQuery()
        ->condition('uid', $account->id())
        ->sort('is_default', 'DESC')
        ->sort('wishlist_id', 'DESC')
        ->accessCheck(FALSE);
      $wishlist_ids = $query->execute();
    }
    else {
      $wishlist_ids = $this->wishlistSession->getWishlistIds();
    }

    $this->wishlistData[$uid] = [];
    if (!$wishlist_ids) {
      return [];
    }
    // Getting the wishlist data and validating the wishlist ids received from
    // the session requires loading the entities. This is a performance hit, but
    // it's assumed that these entities would be loaded at one point anyway.
    /** @var \Drupal\commerce_wishlist\Entity\WishlistInterface[] $wishlists */
    $wishlists = $this->wishlistStorage->loadMultiple($wishlist_ids);
    foreach ($wishlists as $wishlist) {
      if ($wishlist->getOwnerId() != $uid) {
        // Skip wishlists that are no longer eligible.
        continue;
      }

      $this->wishlistData[$uid][$wishlist->id()] = [
        'type' => $wishlist->bundle(),
      ];
    }

    return $this->wishlistData[$uid];
  }

}
