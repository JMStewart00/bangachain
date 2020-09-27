<?php

namespace Drupal\commerce_feeds\Feeds\Target;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a commerce_price field mapper.
 *
 * @FeedsTarget(
 *   id = "commerce_feeds_price",
 *   field_types = {"commerce_price"}
 * )
 */
class Price extends FieldTargetBase implements ConfigurableTargetInterface {

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
    $values['currency_code'] = $this->configuration['currency_code'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $currency_code = NULL;
    $currencies = \Drupal::entityTypeManager()->getStorage('commerce_currency')->loadMultiple();
    $currency_codes = array_keys($currencies);
    if (count($currency_codes) == 1) {
      $currency_code = reset($currency_codes);
    }
    return ['currency_code' => $currency_code];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $currencies = \Drupal::entityTypeManager()->getStorage('commerce_currency')->loadMultiple();
    $currency_codes = array_keys($currencies);

    $form['currency_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Currency'),
      '#options' => array_combine($currency_codes, $currency_codes),
      '#default_value' => $this->configuration['currency_code'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      'unit' => [
        '#type' => 'item',
        '#markup' => $this->t('Currency: %currency_code', [
          '%currency_code' => $this->configuration['currency_code'],
        ]),
      ],
    ];
  }

}
