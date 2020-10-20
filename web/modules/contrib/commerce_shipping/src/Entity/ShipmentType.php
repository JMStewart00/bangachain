<?php

namespace Drupal\commerce_shipping\Entity;

use Drupal\commerce\Entity\CommerceBundleEntityBase;

/**
 * Defines the shipment type entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_shipment_type",
 *   label = @Translation("Shipment type"),
 *   label_collection = @Translation("Shipment types"),
 *   label_singular = @Translation("shipment type"),
 *   label_plural = @Translation("shipment types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count shipment type",
 *     plural = "@count shipment types",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\commerce_shipping\ShipmentTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_shipping\Form\ShipmentTypeForm",
 *       "edit" = "Drupal\commerce_shipping\Form\ShipmentTypeForm",
 *       "delete" = "Drupal\commerce_shipping\Form\ShipmentTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer commerce_shipment_type",
 *   config_prefix = "commerce_shipment_type",
 *   bundle_of = "commerce_shipment",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *     "profileType",
 *     "traits",
 *     "sendConfirmation",
 *     "confirmationBcc",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/shipment-types/add",
 *     "edit-form" = "/admin/commerce/config/shipment-types/{commerce_shipment_type}/edit",
 *     "delete-form" = "/admin/commerce/config/shipment-types/{commerce_shipment_type}/delete",
 *     "collection" = "/admin/commerce/config/shipment-types",
 *   }
 * )
 */
class ShipmentType extends CommerceBundleEntityBase implements ShipmentTypeInterface {

  /**
   * The profile type ID.
   *
   * @var string
   */
  protected $profileType = 'customer';

  /**
   * Shipping confirmation email enabled.
   *
   * @var bool
   */
  protected $sendConfirmation;

  /**
   * Shipping confirmation BCC email address.
   *
   * @var string
   */
  protected $confirmationBcc;

  /**
   * {@inheritdoc}
   */
  public function getProfileTypeId() {
    return $this->profileType;
  }

  /**
   * {@inheritdoc}
   */
  public function setProfileTypeId($profile_type_id) {
    $this->profileType = $profile_type_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldSendConfirmation() {
    return (bool) $this->sendConfirmation;
  }

  /**
   * {@inheritdoc}
   */
  public function setSendConfirmation($send_receipt) {
    $this->sendConfirmation = (bool) $send_receipt;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmationBcc() {
    return $this->confirmationBcc;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfirmationBcc($confirmation_bcc) {
    $this->confirmationBcc = $confirmation_bcc;
    return $this;
  }

}
