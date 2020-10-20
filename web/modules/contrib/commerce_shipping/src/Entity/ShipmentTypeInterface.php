<?php

namespace Drupal\commerce_shipping\Entity;

use Drupal\commerce\Entity\CommerceBundleEntityInterface;

/**
 * Defines the interface for shipment types.
 */
interface ShipmentTypeInterface extends CommerceBundleEntityInterface {

  /**
   * Gets the profile type ID.
   *
   * @return string
   *   The profile type ID.
   */
  public function getProfileTypeId();

  /**
   * Sets the profile type ID.
   *
   * @param string $profile_type_id
   *   The profile type ID.
   *
   * @return $this
   */
  public function setProfileTypeId($profile_type_id);

  /**
   * Gets whether to email the customer when a shipment is shipped.
   *
   * @return bool
   *   TRUE if the confirmation email should be sent, FALSE otherwise.
   */
  public function shouldSendConfirmation();

  /**
   * Sets whether to email the customer a shipment confirmation.
   *
   * @param bool $send_confirmation
   *   TRUE if the confirmation email should be sent, FALSE otherwise.
   *
   * @return $this
   */
  public function setSendConfirmation($send_confirmation);

  /**
   * Gets the confirmation  BCC email.
   *
   * If provided, this email will receive a copy of the confirmation email.
   *
   * @return string
   *   The confirmation BCC email.
   */
  public function getConfirmationBcc();

  /**
   * Sets the confirmation BCC email.
   *
   * @param string $confirmation_bcc
   *   The confirmation BCC email.
   *
   * @return $this
   */
  public function setConfirmationBcc($confirmation_bcc);

}
