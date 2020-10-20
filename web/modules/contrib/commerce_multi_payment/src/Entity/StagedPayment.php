<?php

namespace Drupal\commerce_multi_payment\Entity;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Staged payment entity.
 *
 * @ingroup commerce_multi_payment
 *
 * @ContentEntityType(
 *   id = "commerce_staged_multi_payment",
 *   label = @Translation("Staged payment"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_multi_payment\StagedPaymentListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *
 *     "form" = {
 *       "delete" = "Drupal\commerce_multi_payment\Form\StagedPaymentDeleteForm",
 *     },
 *     "access" = "Drupal\commerce_multi_payment\StagedPaymentAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\commerce_multi_payment\StagedPaymentHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "commerce_staged_multi_payment",
 *   admin_permission = "administer staged payment entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/orders/{commerce_order}/staged-payments/{commerce_staged_multi_payment}",
 *     "delete-form" = "/admin/commerce/orders/{commerce_order}/staged-payments/{commerce_staged_multi_payment}/delete",
 *     "collection" = "/admin/commerce/orders/{commerce_order}/staged-payments",
 *   },
 *   field_ui_base_route = "commerce_staged_multi_payment.settings"
 * )
 */
class StagedPayment extends ContentEntityBase implements StagedPaymentInterface {
  
  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    $payment_gateway = $this->getPaymentGateway();
    if (!$payment_gateway) {
      throw new EntityMalformedException(sprintf('Required payment field "payment_gateway" is empty.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentGateway() {
    return $this->get('payment_gateway')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentGatewayId() {
    return $this->get('payment_gateway')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrder() {
    return $this->get('order_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderId() {
    return $this->get('order_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getPayment() {
    if (!$this->get('payment_id')->isEmpty()) {
      return $this->get('payment_id')->entity;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentId() {
    if (!$this->get('payment_id')->isEmpty()) {
      return $this->get('payment_id')->target_id;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setPayment(PaymentInterface $payment) {
    $this->set('payment_id', $payment);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPaymentId($payment_id) {
    $this->set('payment_id', $payment_id);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function emptyPayment() {
    $this->set('payment_id', NULL);
  }


  /**
   * {@inheritdoc}
   */
  public function isExpired() {
    $expires = $this->getExpiresTime();
    return $expires > 0 && $expires <= \Drupal::time()->getRequestTime();
  }

  /**
   * {@inheritdoc}
   */
  public function getExpiresTime() {
    return $this->get('expires')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setExpiresTime($timestamp) {
    $this->set('expires', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmount() {
    if (!$this->get('amount')->isEmpty()) {
      return $this->get('amount')->first()->toPrice();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setAmount(Price $amount) {
    $this->set('amount', $amount);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getData($key, $default = NULL) {
    $data = [];
    if (!$this->get('data')->isEmpty()) {
      $data = $this->get('data')->first()->getValue();
    }
    return isset($data[$key]) ? $data[$key] : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function setData($key, $value) {
    $this->get('data')->__set($key, $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return $this->get('state')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setState($state_id) {
    $this->set('state', $state_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['payment_gateway'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Payment gateway'))
      ->setDescription(t('The payment gateway.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_payment_gateway');

    $fields['order_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Order'))
      ->setDescription(t('The parent order.'))
      ->setSetting('target_type', 'commerce_order');

    $fields['payment_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Payment'))
      ->setDescription(t('The linked real payment.'))
      ->setSetting('target_type', 'commerce_payment');

    $fields['amount'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Amount'))
      ->setDescription(t('The payment amount.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);
    
    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('A serialized array of additional data.'));

    $fields['state'] = BaseFieldDefinition::create('state')
      ->setLabel(t('State'))
      ->setDescription(t('The staged payment state.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'list_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('workflow', 'commerce_staged_multi_payment');

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDescription(t('Whether this staged payment is active and creates an order adjustment.'))
      ->setDefaultValue(TRUE);

    $fields['expires'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Expires'))
      ->setDescription(t('The time when the payment expires. 0 for never.'))
      ->setDisplayConfigurable('view', TRUE)
      ->setDefaultValue(0);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    $uri_route_parameters['commerce_order'] = $this->getOrderId();
    return $uri_route_parameters;
  }

}
