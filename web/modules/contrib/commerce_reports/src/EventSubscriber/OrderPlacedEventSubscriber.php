<?php

namespace Drupal\commerce_reports\EventSubscriber;

use Drupal\commerce_reports\OrderReportGeneratorInterface;
use Drupal\Core\State\StateInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber to order placed transition event.
 */
class OrderPlacedEventSubscriber implements EventSubscriberInterface {

  /**
   * The state key/value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The order report generator.
   *
   * @var \Drupal\commerce_reports\OrderReportGeneratorInterface
   */
  protected $orderReportGenerator;

  /**
   * Constructs a new OrderPlacedEventSubscriber object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key/value store.
   * @param \Drupal\commerce_reports\OrderReportGeneratorInterface $order_report_generator
   *   The order report generator.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(StateInterface $state, OrderReportGeneratorInterface $order_report_generator) {
    $this->state = $state;
    $this->orderReportGenerator = $order_report_generator;
  }

  /**
   * Flags the order to have a report generated.
   *
   * @todo come up with better flagging.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The workflow transition event.
   */
  public function flagOrder(WorkflowTransitionEvent $event) {
    $order = $event->getEntity();
    $existing = $this->state->get('commerce_order_reports', []);
    $existing[] = $order->id();
    $this->state->set('commerce_order_reports', $existing);
  }

  /**
   * Generates order reports once output flushed.
   *
   * This creates the base order report populated with the bundle plugin ID,
   * order ID, and created timestamp from when the order was placed. Each
   * plugin then sets its values.
   *
   * @param \Symfony\Component\HttpKernel\Event\PostResponseEvent $event
   *   The post response event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function generateReports(PostResponseEvent $event) {
    $order_ids = $this->state->get('commerce_order_reports', []);
    $this->orderReportGenerator->generateReports($order_ids);

    // @todo this could lose data, possibly as its global state.
    $this->state->set('commerce_order_reports', []);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_order.place.pre_transition' => 'flagOrder',
      KernelEvents::TERMINATE => 'generateReports',
    ];
    return $events;
  }

}
