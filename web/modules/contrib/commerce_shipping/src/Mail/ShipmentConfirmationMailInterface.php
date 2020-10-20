<?php

namespace Drupal\commerce_shipping\Mail;

use Drupal\commerce_shipping\Entity\ShipmentInterface;

interface ShipmentConfirmationMailInterface {

  /**
   * Sends the shipment confirmation email.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment
   *   The shipment.
   * @param string $to
   *   The address the email will be sent to. Must comply with RFC 2822.
   *   Defaults to the order email.
   * @param string $bcc
   *   The BCC address or addresses (separated by a comma).
   *
   * @return bool
   *   TRUE if the email was sent successfully, FALSE otherwise.
   */
  public function send(ShipmentInterface $shipment, $to = NULL, $bcc = NULL);

}
