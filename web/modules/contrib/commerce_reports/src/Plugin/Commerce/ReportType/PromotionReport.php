<?php

namespace Drupal\commerce_reports\Plugin\Commerce\ReportType;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryAggregateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity\BundleFieldDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide a report for Promotions on behalf of commerce_promotion.
 *
 * @CommerceReportType(
 *   id = "promotion_report",
 *   label = @Translation("Promotion Report"),
 *   description = @Translation("Promotions Report"),
 *   provider = "commerce_promotion",
 * )
 */
class PromotionReport extends ReportTypeBase implements ContainerFactoryPluginInterface {

  /**
   * The promotion storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $promotionStorage;

  /**
   * Constructs a new PromotionReport object.
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
    $this->promotionStorage = $entity_type_manager->getStorage('commerce_promotion');
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
    $fields['promotion_id'] = BundleFieldDefinition::create('entity_reference')
      ->setLabel(t('Promotion'))
      ->setTargetEntityTypeId('commerce_promotion')
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['promotion_label'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The promotion name.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ]);
    $fields['promotion_amount'] = BundleFieldDefinition::create('commerce_price')
      ->setLabel(t('Promotion amount'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['coupon_id'] = BundleFieldDefinition::create('entity_reference')
      ->setLabel(t('Coupon'))
      ->setTargetEntityTypeId('commerce_promotion_coupon')
      ->setCardinality(1)
      ->setRequired(FALSE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['coupon_code'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Coupon code'))
      ->setDescription(t('The unique, machine-readable identifier for a coupon.'))
      ->setRequired(FALSE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 50,
      ]);
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function generateReports(OrderInterface $order) {
    $coupons = $order->get('coupons')->referencedEntities();
    $adjustments = array_filter($order->collectAdjustments(), function (Adjustment $adjustment) {
      return ($adjustment->getType() == 'promotion') && (!empty($adjustment->getSourceId()));
    });

    /** @var \Drupal\commerce_order\Adjustment $adjustment */
    foreach ($adjustments as $adjustment) {
      $promotion_id = $adjustment->getSourceId();
      /** @var \Drupal\commerce_promotion\Entity\PromotionInterface $promotion */
      $promotion = $this->promotionStorage->load($promotion_id);
      if (!$promotion) {
        continue;
      }

      // Base values for order report.
      $values = [
        'promotion_id' => $promotion->id(),
        'promotion_label' => $promotion->label(),
        'promotion_amount' => $adjustment->getAmount(),
        'coupon_id' => NULL,
        'coupon_code' => NULL,
      ];

      /** @var \Drupal\commerce_promotion\Entity\CouponInterface $coupon */
      foreach ($coupons as $coupon) {
        if ($coupon->getPromotionId() == $adjustment->getSourceId()) {
          $values['coupon_id'] = $coupon->id();
          $values['coupon_code'] = $coupon->getCode();
          break;
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
      'promotion_id_count' => t('# Promotions'),
      'promotion_amount_sum' => t('Total promotions amount'),
      'coupon_id_count' => t('# Coupons'),
      'coupon_amount_sum' => t('Total coupons amount'),
      'promotion_amount_currency_code' => t('Currency'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function doBuildReportTableRow(array $result) {
    $currency_code = $result['promotion_amount_currency_code'];
    $row = [
      $result['formatted_date'],
      $result['promotion_id_count'],
      [
        'data' => [
          '#type' => 'inline_template',
          '#template' => '{{price|commerce_price_format}}',
          '#context' => [
            'price' => new Price($result['promotion_amount_sum'], $currency_code),
          ],
        ],
      ],
      $result['coupon_id_count'],
      [
        'data' => [
          '#type' => 'inline_template',
          '#template' => '{{price|commerce_price_format}}',
          '#context' => [
            'price' => new Price($result['coupon_amount_sum'], $currency_code),
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
    $query->aggregate('promotion_label', 'COUNT');
    $query->aggregate('promotion_amount.number', 'SUM');
    $query->aggregate('coupon_code', 'COUNT');
    $query->aggregate('coupon_amount.number', 'SUM');
    $query->groupBy('promotion_label');
    $query->groupBy('coupon_code');
    $query->groupBy('promotion_amount.currency_code');
  }

}
