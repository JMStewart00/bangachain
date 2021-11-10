<?php

namespace Drupal\commerce_wishlist\Form;

use Drupal\inline_entity_form\Form\EntityInlineForm;

/**
 * Defines the inline form for wishlist items.
 */
class WishlistItemInlineForm extends EntityInlineForm {

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeLabels() {
    $labels = [
      'singular' => t('wishlist item'),
      'plural' => t('wishlist items'),
    ];
    return $labels;
  }

  /**
   * {@inheritdoc}
   */
  public function getTableFields($bundles) {
    $fields = parent::getTableFields($bundles);
    $fields['quantity'] = [
      'type' => 'field',
      'label' => t('Quantity'),
      'weight' => 2,
    ];

    return $fields;
  }

}
