<?php

namespace Drupal\commerce_wishlist\Plugin\views\field;

use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_order\Resolver\OrderTypeResolverInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\commerce_store\SelectStoreTrait;
use Drupal\commerce_wishlist\Entity\WishlistItemInterface;
use Drupal\commerce_wishlist\WishlistManagerInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\field\UncacheableFieldHandlerTrait;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form element for moving or copying the wishlist item to the cart.
 *
 * @ViewsField("commerce_wishlist_item_move_to_cart")
 */
class MoveToCart extends FieldPluginBase {

  use SelectStoreTrait;

  use UncacheableFieldHandlerTrait;

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * The order type resolver.
   *
   * @var \Drupal\commerce_order\Resolver\OrderTypeResolverInterface
   */
  protected $orderTypeResolver;

  /**
   * The current store.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  protected $currentStore;

  /**
   * The wishlist manager.
   *
   * @var \Drupal\commerce_wishlist\WishlistManagerInterface
   */
  protected $wishlistManager;

  /**
   * Constructs a new MoveToCart object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_wishlist\WishlistManagerInterface $wishlist_manager
   *   The wishlist manager.
   * @param \Drupal\commerce_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   * @param \Drupal\commerce_order\Resolver\OrderTypeResolverInterface $order_type_resolver
   *   The order type resolver.
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   *   The current store.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, WishlistManagerInterface $wishlist_manager, CartManagerInterface $cart_manager, CartProviderInterface $cart_provider, OrderTypeResolverInterface $order_type_resolver, CurrentStoreInterface $current_store) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->wishlistManager = $wishlist_manager;
    $this->cartManager = $cart_manager;
    $this->cartProvider = $cart_provider;
    $this->orderTypeResolver = $order_type_resolver;
    $this->currentStore = $current_store;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_wishlist.wishlist_manager'),
      $container->get('commerce_cart.cart_manager'),
      $container->get('commerce_cart.cart_provider'),
      $container->get('commerce_order.chain_order_type_resolver'),
      $container->get('commerce_store.current_store')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function clickSortable() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $row, $field = NULL) {
    return '<!--form-item-' . $this->options['id'] . '--' . $row->index . '-->';
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['keep_item'] = ['default' => FALSE];
    $options['combine'] = ['default' => TRUE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['keep_item'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Keep item'),
      '#description' => $this->t('Enable in order to keep the item in the wishlist (copy instead of move).'),
      '#default_value' => $this->options['keep_item'],
    ];

    $form['combine'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Combine'),
      '#description' => $this->t('Combine order items containing the same product variation.'),
      '#default_value' => $this->options['combine'],
    ];
  }

  /**
   * Form constructor for the views form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function viewsForm(array &$form, FormStateInterface $form_state) {
    // Make sure we do not accidentally cache this form.
    $form['#cache']['max-age'] = 0;
    // The view is empty, abort.
    if (empty($this->view->result)) {
      unset($form['actions']);
      return;
    }

    $form[$this->options['id']]['#tree'] = TRUE;
    foreach ($this->view->result as $row_index => $row) {
      /** @var \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item */
      $wishlist_item = $this->getEntity($this->view->result[$row_index]);

      if ($this->isValid($wishlist_item)) {
        $form[$this->options['id']][$row_index] = [
          '#type' => 'submit',
          '#value' => $this->options['keep_item'] ? $this->t('Copy to cart') : $this->t('Move to cart'),
          '#name' => 'move-wishlist-item-' . $row_index,
          '#move_wishlist_item' => TRUE,
          '#row_index' => $row_index,
          '#attributes' => ['class' => ['move-wishlist-item']],
        ];
      }
      else {
        $form[$this->options['id']][$row_index] = [];
      }
    }
  }

  /**
   * Submit handler for the views form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Exception
   *   When the call to self::selectStore() throws an exception because the
   *   entity can't be purchased from the current store.
   */
  public function viewsFormSubmit(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if (!empty($triggering_element['#move_wishlist_item'])) {
      $row_index = $triggering_element['#row_index'];
      /** @var \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item */
      $wishlist_item = $this->getEntity($this->view->result[$row_index]);

      $purchased_entity = $wishlist_item->getPurchasableEntity();
      $order_item = $this->cartManager->createOrderItem($purchased_entity, $wishlist_item->getQuantity());
      $order_type = $this->orderTypeResolver->resolve($order_item);

      $store = $this->selectStore($purchased_entity);
      $cart = $this->cartProvider->getCart($order_type, $store);
      if (!$cart) {
        $cart = $this->cartProvider->createCart($order_type, $store);
      }
      $this->cartManager->addOrderItem($cart, $order_item, $this->options['combine']);
      if (!$this->options['keep_item']) {
        $wishlist = $wishlist_item->getWishlist();
        $wishlist->removeItem($wishlist_item);
        $wishlist->save();
        $wishlist_item->delete();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing.
  }

  /**
   * Checks, if the given wishlist item is still valid.
   *
   * The underlying purchasable entity could have been deleted or disabled in
   * the meantime. In this case, we won't show the move/copy to cart action at
   * all.
   *
   * @param \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item
   *   The wishlist item.
   *
   * @return bool
   *   TRUE, if the given wishlist item is valid, FALSE otherwise.
   */
  protected function isValid(WishlistItemInterface $wishlist_item) {
    $purchasable_entity = $wishlist_item->getPurchasableEntity();
    $valid = FALSE;
    // Check the deprecated ::isActive method.
    if ($purchasable_entity instanceof ProductVariationInterface) {
      $valid = $purchasable_entity->isActive();
    }
    elseif ($purchasable_entity instanceof EntityPublishedInterface) {
      $valid = $purchasable_entity->isPublished();
    }
    if ($valid) {
      try {
        $this->selectStore($wishlist_item->getPurchasableEntity());
      }
      catch (\Exception $e) {
        $valid = FALSE;
      }
    }
    return $valid;
  }

}
