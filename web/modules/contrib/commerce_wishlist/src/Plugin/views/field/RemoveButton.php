<?php

namespace Drupal\commerce_wishlist\Plugin\views\field;

use Drupal\commerce_wishlist\WishlistManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\field\UncacheableFieldHandlerTrait;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form element for removing the wishlist item.
 *
 * @ViewsField("commerce_wishlist_item_remove_button")
 */
class RemoveButton extends FieldPluginBase {

  use UncacheableFieldHandlerTrait;

  /**
   * The wishlist manager.
   *
   * @var \Drupal\commerce_wishlist\WishlistManagerInterface
   */
  protected $wishlistManager;

  /**
   * Constructs a new EditRemove object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_wishlist\WishlistManagerInterface $wishlist_manager
   *   The wishlist manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, WishlistManagerInterface $wishlist_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->wishlistManager = $wishlist_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_wishlist.wishlist_manager')
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
        '#value' => t('Remove'),
        '#name' => 'delete-wishlist-item-' . $row_index,
        '#remove_wishlist_item' => TRUE,
        '#row_index' => $row_index,
        '#attributes' => ['class' => ['delete-wishlist-item']],
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
    if (!empty($triggering_element['#remove_wishlist_item'])) {
      $row_index = $triggering_element['#row_index'];
      /** @var \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item */
      $wishlist_item = $this->getEntity($this->view->result[$row_index]);
      $this->wishlistManager->removeWishlistItem($wishlist_item->getWishlist(), $wishlist_item);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing.
  }

}
