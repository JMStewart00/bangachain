<?php

namespace Drupal\commerce_reports\Plugin\Commerce\ReportType;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the base order report type class.
 */
abstract class ReportTypeBase extends PluginBase implements ReportTypeInterface, ContainerFactoryPluginInterface {

  /**
   * The order report storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $orderReportStorage;

  /**
   * Constructs a new ReportTypeBase object.
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
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->orderReportStorage = $entity_type_manager->getStorage('commerce_order_report');
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
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildReportTable(array $results) {
    $build = [
      '#type' => 'table',
      '#header' => $this->doBuildReportTableHeaders(),
      '#rows' => [],
      '#empty' => t('No reports yet'),
    ];
    foreach ($results as $result) {
      $row = $this->doBuildReportTableRow($result);
      $build['#rows'][] = $row;
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function createFromOrder(OrderInterface $order, array $values = []) {
    $values += [
      'type' => $this->getPluginId(),
      'order_id' => $order->id(),
      'created' => $order->getPlacedTime(),
    ];
    $order_report = $this->orderReportStorage->create($values);
    $order_report->save();
  }

  /**
   * Builds the report table headers.
   *
   * @return array
   *   The report table headers.
   */
  abstract protected function doBuildReportTableHeaders();

  /**
   * Build the report table row.
   *
   * @param array $result
   *   The result row.
   *
   * @return array
   *   The table row data.
   */
  abstract protected function doBuildReportTableRow(array $result);

}
