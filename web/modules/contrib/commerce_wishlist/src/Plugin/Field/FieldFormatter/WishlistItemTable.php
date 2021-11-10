<?php

namespace Drupal\commerce_wishlist\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'commerce_wishlist_item_table' formatter.
 *
 * @FieldFormatter(
 *   id = "commerce_wishlist_item_table",
 *   label = @Translation("wishlist item table"),
 *   field_types = {
 *     "entity_reference",
 *   },
 * )
 */
class WishlistItemTable extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $wishlist = $items->getEntity();
    return [
      '#type' => 'view',
      // @todo Allow the view to be configurable.
      '#name' => 'commerce_wishlist_item_table',
      '#arguments' => [$wishlist->id()],
      '#embed' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $entity_type == 'commerce_wishlist' && $field_name == 'wishlist_items';
  }

}
