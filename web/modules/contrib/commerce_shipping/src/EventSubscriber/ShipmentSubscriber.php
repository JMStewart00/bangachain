<?php

namespace Drupal\commerce_shipping\EventSubscriber;

use Drupal\commerce_shipping\Mail\ShipmentConfirmationMailInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ShipmentSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The shipment notification mail service.
   *
   * @var \Drupal\commerce_shipping\Mail\ShipmentConfirmationMailInterface
   */
  protected $shipmentConfirmationMail;

  /**
   * Constructs a new ShipmentSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_shipping\Mail\ShipmentConfirmationMailInterface $shipment_confirmation_mail
   *   The shipment confirmation mail service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ShipmentConfirmationMailInterface $shipment_confirmation_mail) {
    $this->entityTypeManager = $entity_type_manager;
    $this->shipmentConfirmationMail = $shipment_confirmation_mail;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'commerce_shipment.ship.post_transition' => ['onShip'],
    ];
  }

  /**
   * Checks shipment mail settings and sends confirmation email to customer.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function onShip(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
    $shipment = $event->getEntity();
    /** @var \Drupal\commerce_shipping\Entity\ShipmentTypeInterface $shipment_type */
    $shipment_type = $this->entityTypeManager->getStorage('commerce_shipment_type')->load($shipment->bundle());
    $order = $shipment->getOrder();
    assert($order !== NULL);

    // Continue only if settings are configured to send confirmation.
    if ($shipment_type->shouldSendConfirmation()) {
      $this->shipmentConfirmationMail->send($shipment, $order->getEmail(), $shipment_type->getConfirmationBcc());
    }
  }

}
