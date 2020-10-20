<?php

namespace Drupal\commerce_shipping\Form;

use Drupal\commerce_shipping\Mail\ShipmentConfirmationMailInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form for resending order shipment confirmations.
 */
class ShipmentConfirmationResendForm extends ContentEntityConfirmFormBase {

  /**
   * The shipment confirmation mail service.
   *
   * @var \Drupal\commerce_shipping\Mail\ShipmentConfirmationMailInterface
   */
  protected $shipmentConfirmationMail;

  /**
   * Constructs a new ShipmentConfirmationResendForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\commerce_shipping\Mail\ShipmentConfirmationMailInterface $shipment_confirmation_mail
   *   The shipment confirmation mail service.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, ShipmentConfirmationMailInterface $shipment_confirmation_mail) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);

    $this->shipmentConfirmationMail = $shipment_confirmation_mail;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('commerce_shipping.shipment_confirmation_mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to resend the shipment confirmation for %shipment for order %order?', [
      '%shipment' => $this->entity->label(),
      '%order' => $this->entity->getOrder()->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Resend confirmation');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->toUrl('collection');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
    $shipment = $this->entity;
    $result = $this->shipmentConfirmationMail->send($shipment);
    // Drupal's MailManager sets an error message itself, if the sending failed.
    if ($result) {
      $this->messenger()->addMessage($this->t('Shipment confirmation resent.'));
    }
  }

}
