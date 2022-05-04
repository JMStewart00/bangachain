<?php

namespace Drupal\commerce_reports\Plugin\views\field;

use Drupal\commerce_price\Entity\Currency;
use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Aggregated price fields have their handler swapped to use this handler in
 * commerce_reports_views_pre_build.
 *
 * @internal
 */
class PriceNumericField extends NumericField {

  /**
   * The currency formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface
   */
  protected $currencyFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->currencyFormatter = $container->get('commerce_price.currency_formatter');
    return $instance;
  }

  public static function createFromNumericField(NumericField $field) {
    $handler = static::create(\Drupal::getContainer(), $field->configuration, $field->pluginId, $field->pluginDefinition);
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

    return $this->currencyFormatter->format($number, $currency->getCurrencyCode());
  }

}
