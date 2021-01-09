<?php

namespace Drupal\commerce_reports\Plugin\Commerce\ReportType;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_tax\Plugin\Commerce\TaxType\LocalTaxTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryAggregateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity\BundleFieldDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide a report for Taxes on behalf of commerce_tax.
 *
 * @CommerceReportType(
 *   id = "tax_report",
 *   label = @Translation("Tax Report"),
 *   description = @Translation("Tax Report"),
 *   provider = "commerce_tax",
 * )
 */
class TaxReport extends ReportTypeBase implements ContainerFactoryPluginInterface {

  /**
   * The tax type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $taxTypeStorage;

  /**
   * Constructs a new TaxReport object.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager);
    $this->taxTypeStorage = $entity_type_manager->getStorage('commerce_tax_type');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = [];
    $fields['tax_amount'] = BundleFieldDefinition::create('commerce_price')
      ->setLabel(t('Tax amount'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['tax_type_id'] = BundleFieldDefinition::create('entity_reference')
      ->setLabel(t('Tax type'))
      ->setSetting('target_type', 'commerce_tax_type')
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['tax_type_label'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The tax type name.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['zone_id'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Zone ID'))
      ->setDescription(t('The tax zone id.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setRequired(FALSE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['zone_label'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Zone label'))
      ->setDescription(t('The tax zone label.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setRequired(FALSE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['rate_id'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Rate ID'))
      ->setDescription(t('The tax rate id.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setRequired(FALSE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['rate_label'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Rate label'))
      ->setDescription(t('The tax rate label.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setRequired(FALSE)
      ->setDisplayConfigurable('view', TRUE);
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function generateReports(OrderInterface $order) {
    $adjustments = $order->collectAdjustments();
    $adjustments = array_filter($order->collectAdjustments(), function (Adjustment $adjustment) {
      return ($adjustment->getType() == 'tax') && (!empty($adjustment->getSourceId()));
    });

    /** @var \Drupal\commerce_order\Adjustment $adjustment */
    foreach ($adjustments as $adjustment) {

      $source_id = $adjustment->getSourceId();
      list($tax_entity_id, $zone_id, $rate_id) = explode('|', $source_id) + [NULL, NULL, NULL];
      if (empty($tax_entity_id)) {
        continue;
      }
      /** @var \Drupal\commerce_tax\Entity\TaxTypeInterface $tax_type */
      $tax_type = $this->taxTypeStorage->load($tax_entity_id);
      if (!$tax_type) {
        continue;
      }

      // Base values for order report.
      $values = [
        'tax_amount' => $adjustment->getAmount(),
        'tax_type_id' => $tax_type->id(),
        'tax_type_label' => $tax_type->label(),
        'zone_id' => NULL,
        'zone_label' => NULL,
        'rate_id' => NULL,
        'rate_label' => NULL,
      ];

      if ($tax_type->getPlugin() instanceof LocalTaxTypeInterface) {
        $zones = $tax_type->getPlugin()->getZones();
        if (!empty($zones) && isset($zones[$zone_id])) {
          $values['zone_id'] = $zone_id;
          $values['zone_label'] = $zones[$zone_id]->getLabel();
          /** @var \Drupal\commerce_tax\TaxRate $rate */
          foreach ($zones[$zone_id]->getRates() as $rate) {
            if ($rate->getId() == $rate_id) {
              $values['rate_id'] = $rate->getId();
              $values['rate_label'] = $rate->getLabel();
              break;
            }
          }
        }
      }
      $this->createFromOrder($order, $values);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doBuildReportTableHeaders() {
    return [
      'formatted_date' => t('Date'),
      'tax_type_id_count' => t('# Tax charges'),
      'tax_amount_sum' => t('Total tax amount'),
      'tax_amount_currency_code' => t('Currency'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function doBuildReportTableRow(array $result) {
    $currency_code = $result['tax_amount_currency_code'];
    $row = [
      $result['formatted_date'],
      $result['tax_type_id_count'],
      [
        'data' => [
          '#type' => 'inline_template',
          '#template' => '{{price|commerce_price_format}}',
          '#context' => [
            'price' => new Price($result['tax_amount_sum'], $currency_code),
          ],
        ],
      ],
      $currency_code,
    ];
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildQuery(QueryAggregateInterface $query) {
    $query->aggregate('tax_type_label', 'COUNT');
    $query->aggregate('tax_amount.number', 'SUM');
    $query->groupBy('tax_type_label');
    $query->groupBy('tax_amount.currency_code');
  }

}
