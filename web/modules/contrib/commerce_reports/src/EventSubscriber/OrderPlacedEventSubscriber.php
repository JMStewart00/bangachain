<?php

namespace Drupal\commerce_reports\EventSubscriber;

use Drupal\commerce_reports\OrderReportGeneratorInterface;
use Drupal\Core\DestructableInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to order placed transition event.
 */
class OrderPlacedEventSubscriber implements EventSubscriberInterface, DestructableInterface {

  /**
   * The order report generator.
   *
   * @var \Drupal\commerce_reports\OrderReportGeneratorInterface
   */
  protected $orderReportGenerator;

  /**
   * Static cache of order IDS that were placed during this request.
   *
   * @var array
   */
  protected $orderIds = [];

  /**
   * Constructs a new OrderPlacedEventSubscriber object.
   *
   * @param \Drupal\commerce_reports\OrderReportGeneratorInterface $order_report_generator
   *   The order report generator.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(OrderReportGeneratorInterface $order_report_generator) {
    $this->orderReportGenerator = $order_report_generator;
  }

  /**
   * Flags the order to have a report generated.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The workflow transition event.
   */
  public function onOrderPlace(WorkflowTransitionEvent $event) {
    $order = $event->getEntity();
    $this->orderIds[$order->id()] = $order->id();
  }

  /**
   * Generates order reports on destruct.
   *
   * This creates the base order report populated with the bundle plugin ID,
   * order ID, and created timestamp from when the order was placed. Each
   * plugin then sets its values.
   */
  public function destruct() {
    if (!empty($this->orderIds)) {
      $this->orderReportGenerator->generateReports($this->orderIds);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'commerce_order.place.post_transition' => 'onOrderPlace',
    ];
  }

}
