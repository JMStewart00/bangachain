<?php

namespace Drupal\commerce_feeds\Feeds\Target;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;
use Drupal\physical\LengthUnit;

/**
 * Defines a physical_dimensions field mapper.
 *
 * @FeedsTarget(
 *   id = "commerce_feeds_physical_dimensions",
 *   field_types = {"physical_dimensions"}
 * )
 */
class Dimensions extends FieldTargetBase implements ConfigurableTargetInterface {

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    return FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('length')
      ->addProperty('width')
      ->addProperty('height');
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    parent::prepareValue($delta, $values);
    $values['unit'] = $this->configuration['unit'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['unit' => LengthUnit::getBaseUnit()];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['unit'] = [
      '#type' => 'select',
      '#title' => $this->t('Unit'),
      '#options' => LengthUnit::getLabels(),
      '#default_value' => $this->configuration['unit'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return $this->t('Unit: %unit', [
      '%unit' => $this->configuration['unit'],
    ]);
  }

}
