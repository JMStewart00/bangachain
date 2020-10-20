<?php

namespace Drupal\commerce_shipping;

use Drupal\commerce_shipping\Controller\ShipmentController;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for the Shipment entity.
 */
class ShipmentRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    if ($resend_confirmation_form_route = $this->getResendConfirmationFormRoute($entity_type)) {
      $collection->add("entity.commerce_shipment.resend_confirmation_form", $resend_confirmation_form_route);
    }

    return $collection;
  }

  /**
   * Gets the resend-confirmation-form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getResendConfirmationFormRoute(EntityTypeInterface $entity_type) {
    $route = new Route($entity_type->getLinkTemplate('resend-confirmation-form'));
    $route
      ->addDefaults([
        '_entity_form' => 'commerce_shipment.resend-confirmation',
        '_title_callback' => '\Drupal\Core\Entity\Controller\EntityController::title',
      ])
      ->setRequirement('_entity_access', 'commerce_order.resend_receipt')
      ->setRequirement('commerce_order', '\d+')
      ->setRequirement('commerce_shipment', '\d+')
      ->setOption('parameters', [
        'commerce_order' => [
          'type' => 'entity:commerce_order',
        ],
        'commerce_shipment' => [
          'type' => 'entity:commerce_shipment',
        ],
      ])
      ->setOption('_admin_route', TRUE);

    return $route;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAddFormRoute(EntityTypeInterface $entity_type) {
    $route = parent::getAddFormRoute($entity_type);
    if ($route) {
      $route->setOption('parameters', [
        'commerce_order' => [
          'type' => 'entity:commerce_order',
        ],
        'commerce_shipment_type' => [
          'type' => 'entity:commerce_shipment_type',
        ],
      ]);
    }
    return $route;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAddPageRoute(EntityTypeInterface $entity_type) {
    $route = parent::getAddPageRoute($entity_type);
    if ($route) {
      $route->setDefault('_controller', ShipmentController::class . '::addShipmentPage');
      $route->setOption('parameters', [
        'commerce_order' => [
          'type' => 'entity:commerce_order',
        ],
      ]);
    }
    return $route;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    $route = parent::getCollectionRoute($entity_type);
    if ($route) {
      $route->setOption('parameters', [
        'commerce_order' => [
          'type' => 'entity:commerce_order',
        ],
      ]);
      $route->setRequirement('_shipment_collection_access', 'TRUE');
    }
    return $route;
  }

}
