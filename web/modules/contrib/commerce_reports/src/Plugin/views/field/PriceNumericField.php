<?php

namespace Drupal\commerce_reports\Plugin\views\field;

use Drupal\commerce_price\Entity\Currency;
use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;

/**
 * Aggregated price fields have their handler swapped to use this handler in
 * commerce_reports_views_pre_build.
 *
 * @internal
 */
class PriceNumericField extends NumericField {

  public static function createFromNumericField(NumericField $field) {
    $handler = new static(
      $field->configuration,
      $field->pluginId,
      $field->definition
    );
    $handler->init(
      $field->view,
      $field->displayHandler,
      $field->options
    );
    return $handler;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $number = $this->getValue($values, $this->field . '_number');
    $currency_code = $this->getValue($values, $this->field . '_currency_code');

    if (!$currency_code) {
      return parent::render($values);
    }

    $currency = Currency::load($currency_code);

    if (!$currency) {
      return parent::render($values);
    }

    $formatter = \Drupal::getContainer()->get('commerce_price.number_formatter_factory')->createInstance();
    return $formatter->formatCurrency($number, $currency);
  }

}
