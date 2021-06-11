<?php

namespace Drupal\commerce_pos_loyalty_points_support\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_reference_autocomplete_tags' widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_autocomplete_loyalty_points",
 *   label = @Translation("Autocomplete with loyalty points"),
 *   description = @Translation("An autocomplete text field that displays customer loyalty points."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceAutocompleteLoyaltyPointsWidget extends EntityReferenceAutocompleteWidget {

  use LoyaltyPointsTrait;

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $referenced_entities = $items->referencedEntities();
    if (isset($referenced_entities[$delta])) {
      $uid = $referenced_entities[$delta]->id();
      if ($uid) {
        $default_points = $this->getLoyaltyPoints($uid);
      }
    }

    $loyalty_points['container'] = [
      '#type' => 'fieldset',
      '#title' => t('Loyalty points'),
      '#collapsed' => FALSE,
      '#collapsible' => TRUE,
    ];
    $loyalty_points['container']['display_loyalty_points'] = [
      '#type' => 'item',
      '#title' => isset($default_points) ? $default_points : '',
      '#id' => 'customer-loyalty-points',
    ];
    $loyalty_points['container']['get_loyalty_points'] = [
      '#type' => 'submit',
      '#value' => t('Check points'),
      '#ajax' => [
        'callback' => [$this, 'displayLoyaltyPoints'],
        'progress' => [
          'type' => 'throbber',
          'message' => t('Looking up...'),
        ],
      ],
    ];
    return $element + $loyalty_points['container'];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    return $values;
  }

}
