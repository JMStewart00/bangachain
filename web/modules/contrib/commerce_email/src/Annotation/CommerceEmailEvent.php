<?php

namespace Drupal\commerce_email\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the email event plugin annotation object.
 *
 * Plugin namespace: Plugin\Commerce\EmailEvent.
 *
 * @Annotation
 */
class CommerceEmailEvent extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The email event label.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The Symfony event name.
   *
   * For example: 'commerce_order.place.post_transition'.
   *
   * @var string
   */
  protected $event_name;

  /**
   * The email event entity type ID.
   *
   * This is the entity type ID of the entity the event is fired for.
   * For example: 'commerce_order'.
   *
   * @var string
   */
  public $entity_type;

}
