<?php

namespace Drupal\commerce_feeds\Feeds\Target;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;
use Drupal\physical\MeasurementType;

/**
 * Defines a physical_measurement field mapper.
 *
 * @FeedsTarget(
 *   id = "commerce_feeds_physical_measurement",
 *   field_types = {"physical_measurement"}
 * )
 */
class Measurement extends FieldTargetBase implements ConfigurableTargetInterface {

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    return FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('number');
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
    return ['unit' => MeasurementType::getUnitClass($this->getMeasurementType())::getBaseUnit()];
  }

  /**
   * Returns the measurement type used for the field.
   *
   * @return string
   *   The measurement type for the field.
   */
  protected function getMeasurementType() {
    return $this->settings['measurement_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['unit'] = [
      '#type' => 'select',
      '#title' => $this->t('Unit'),
      '#options' => MeasurementType::getUnitClass($this->getMeasurementType())::getLabels(),
      '#default_value' => $this->configuration['unit'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      'measurement_type' => [
        '#type' => 'item',
        '#markup' => $this->t('Measurement type: @type', [
          '@type' => MeasurementType::getLabels()[$this->settings['measurement_type']],
        ]),
      ],
      'unit' => [
        '#type' => 'item',
        '#markup' => $this->t('Unit: %unit', [
          '%unit' => $this->configuration['unit'],
        ]),
      ],
    ];
  }

}
