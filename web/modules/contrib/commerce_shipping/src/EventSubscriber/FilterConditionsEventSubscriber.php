<?php

namespace Drupal\commerce_shipping\EventSubscriber;

use Drupal\commerce\Event\FilterConditionsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Removes unnecessary conditions on the shipping method form.
 */
class FilterConditionsEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'commerce.filter_conditions' => 'onFilterConditions',
    ];
  }

  /**
   * Removes unnecessary shipping conditions on shipping method form.
   *
   * @param \Drupal\commerce\Event\FilterConditionsEvent $event
   *   The event.
   */
  public function onFilterConditions(FilterConditionsEvent $event) {
    if ($event->getParentEntityTypeId() === 'commerce_shipping_method') {
      $definitions = $event->getDefinitions();
      unset($definitions['order_shipping_address'], $definitions['order_shipping_method']);
      $event->setDefinitions($definitions);
    }
  }

}
