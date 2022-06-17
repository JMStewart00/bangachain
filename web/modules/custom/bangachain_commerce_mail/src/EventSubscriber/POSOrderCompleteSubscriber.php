<?php

namespace Drupal\bangachain_commerce_mail\EventSubscriber;

use Drupal\commerce_order\Entity\Order;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Mail\MailManagerInterface;

/**
 * Class POSOrderCompleteSubscriber.
 *
 * @package Drupal\bangachain_commerce_mail
 */
class POSOrderCompleteSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Constructor.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   */
  public function __construct(
    EntityTypeManager $entity_type_manager,
    MailManagerInterface $mail_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events['commerce_order.place.post_transition'] = ['orderCompleteHandler'];
    return $events;
  }

  /**
   * This method is called whenever the commerce_order.place.post_transition event is
   * dispatched.
   *
   * @param WorkflowTransitionEvent $event
   */
  public function orderCompleteHandler(WorkflowTransitionEvent $event): void {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    // Order items in the cart.
    $items = $order->getItems();
    // $this->sendEmail($order, $items);

    // Write your custom logic here.

  }


  // /**
  //  * Sends the email.
  //  *
  //  * @param Order $order
  //  *   The transition event.
  //  */
  // public function sendEmail(Order $order, array $items): void {
  //   // Create the email.
  //   $to = $order->getEmail();
  //   $params = [
  //     'from' => $order->getStore()->getEmail(),
  //     'subject' => t(
  //       'Your order [#@number]',
  //       ['@number' => $order->getOrderNumber()]
  //     ),
  //     'body' => ['#markup' => t(
  //       'Thank you for your recent stop into Bangachain. We hope you enjoyed your shopping experience. Here is your receipt for order [#@number]',
  //       ['@number' => $order->getOrderNumber()]
  //     )],
  //   ];

  //   // Send the email.
  //   $this->mailManager->mail('bangachain_commerce_mail', 'send_pos_receipt', $to, $order->getStore()->getDefaultLangcode(), $params);
  // }

}
