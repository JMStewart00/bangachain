<?php

namespace Drupal\commerce_wishlist\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\field\UncacheableFieldHandlerTrait;
use Drupal\views\ResultRow;

/**
 * Defines a form element for editing the wishlist item quantity.
 *
 * @ViewsField("commerce_wishlist_item_edit_quantity")
 */
class EditQuantity extends FieldPluginBase {

  use UncacheableFieldHandlerTrait;

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
      /** @var \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item */
      $wishlist_item = $this->getEntity($row);
      $quantity = $wishlist_item->getQuantity();

      $form[$this->options['id']][$row_index] = [
        '#type' => 'number',
        '#title' => $this->t('Quantity'),
        '#title_display' => 'invisible',
        '#default_value' => round($quantity),
        '#size' => 4,
        '#min' => 1,
        '#max' => 9999,
        '#step' => 1,
      ];
    }
    // Replace the form submit button label.
    $form['actions']['submit']['#value'] = $this->t('Update wishlist');
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
    $quantities = $form_state->getValue($this->options['id']);
    foreach ($quantities as $row_index => $quantity) {
      /** @var \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item */
      $wishlist_item = $this->getEntity($this->view->result[$row_index]);
      if ($wishlist_item->getQuantity() != $quantity) {
        $wishlist_item->setQuantity($quantity);
        $wishlist_item->save();
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
