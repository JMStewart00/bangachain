<?php

namespace Drupal\commerce_wishlist\Plugin\views\field;

use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_wishlist\WishlistManagerInterface;
use Drupal\commerce_wishlist\WishlistProviderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\field\UncacheableFieldHandlerTrait;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form element for moving or copying the wishlist item to the cart.
 *
 * @ViewsField("commerce_wishlist_order_item_move_to_wishlist")
 */
class MoveToWishlist extends FieldPluginBase {

  use UncacheableFieldHandlerTrait;

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The wishlist manager.
   *
   * @var \Drupal\commerce_wishlist\WishlistManagerInterface
   */
  protected $wishlistManager;

  /**
   * The wishlist provider.
   *
   * @var \Drupal\commerce_wishlist\WishlistProviderInterface
   */
  protected $wishlistProvider;

  /**
   * Constructs a new MoveToWishlist object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   * @param \Drupal\commerce_wishlist\WishlistManagerInterface $wishlist_manager
   *   The wishlist manager.
   * @param \Drupal\commerce_wishlist\WishlistProviderInterface $wishlist_provider
   *   The wishlist provider.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CartManagerInterface $cart_manager, WishlistManagerInterface $wishlist_manager, WishlistProviderInterface $wishlist_provider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->cartManager = $cart_manager;
    $this->wishlistManager = $wishlist_manager;
    $this->wishlistProvider = $wishlist_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_cart.cart_manager'),
      $container->get('commerce_wishlist.wishlist_manager'),
      $container->get('commerce_wishlist.wishlist_provider')
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
      '#description' => $this->t('Enable in order to keep the item in the cart (copy instead of move).'),
      '#default_value' => $this->options['keep_item'],
    ];

    $form['combine'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Combine'),
      '#description' => $this->t('Combine wishlist items containing the same product variation.'),
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
      $form[$this->options['id']][$row_index] = [
        '#type' => 'submit',
        '#value' => $this->options['keep_item'] ? $this->t('Copy to wishlist') : $this->t('Move to wishlist'),
        '#name' => 'move-cart-item-' . $row_index,
        '#move_cart_item' => TRUE,
        '#row_index' => $row_index,
        '#attributes' => ['class' => ['move-cart-item']],
      ];
    }
  }

  /**
   * Submit handler for the views form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function viewsFormSubmit(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if (!empty($triggering_element['#move_cart_item'])) {
      $row_index = $triggering_element['#row_index'];
      /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
      $order_item = $this->getEntity($this->view->result[$row_index]);
      $purchased_entity = $order_item->getPurchasedEntity();
      $quantity = $order_item->getQuantity();
      $wishlist_type = 'default';
      $wishlist = $this->wishlistProvider->getWishlist($wishlist_type);
      if (!$wishlist) {
        $wishlist = $this->wishlistProvider->createWishlist($wishlist_type);
      }
      $this->wishlistManager->addEntity($wishlist, $purchased_entity, $quantity, $this->options['combine']);

      if (!$this->options['keep_item']) {
        $this->cartManager->removeOrderItem($order_item->getOrder(), $order_item);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing.
  }

}
