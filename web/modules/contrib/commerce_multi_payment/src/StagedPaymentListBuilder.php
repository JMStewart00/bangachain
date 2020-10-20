<?php

namespace Drupal\commerce_multi_payment;

use Drupal\commerce_price\Entity\Currency;
use Drupal\commerce_price\NumberFormatterFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of Staged payment entities.
 *
 * @ingroup commerce_multi_payment
 */
class StagedPaymentListBuilder extends EntityListBuilder {

  /**
   * The number formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\NumberFormatterInterface
   */
  protected $numberFormatter;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new ProductVariationListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, RouteMatchInterface $route_match, NumberFormatterFactoryInterface $number_formatter_factory) {
    parent::__construct($entity_type, $storage);

    $this->routeMatch = $route_match;
    $this->numberFormatter = $number_formatter_factory->createInstance();
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('current_route_match'),
      $container->get('commerce_price.number_formatter_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->routeMatch->getParameter('commerce_order');
    $staged_payments = $this->storage->loadByProperties(['order_id' => $order->id()]);

    return $staged_payments;
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Staged payment ID');
    $header['payment_gateway'] = $this->t('Payment Gateway');
    $header['amount'] = $this->t('Amount');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\commerce_multi_payment\Entity\StagedPayment */
    $row['id'] = $entity->id();
    $row['payment_gateway'] = $entity->getPaymentGateway()->label();
    $currency = Currency::load($entity->getAmount()->getCurrencyCode());
    $row['amount'] = $this->numberFormatter->formatCurrency($entity->getAmount()->getNumber(), $currency);
    $row['status'] = ($entity->isActive()) ? $this->t('Active') : $this->t('Inactive');
    return $row + parent::buildRow($entity);
  }

}
