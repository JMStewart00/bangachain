<?php

namespace Drupal\commerce_email\Plugin\Commerce\EmailEvent;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\EventDispatcher\Event;

/**
 * Defines the interface for email events.
 */
interface EmailEventInterface extends PluginInspectionInterface {

  /**
   * Gets the email event label.
   *
   * @return string
   *   The email event label.
   */
  public function getLabel();

  /**
   * Gets the Symfony event name.
   *
   * @return string
   *   The Symfony event name.
   */
  public function getEventName();

  /**
   * Gets the email event entity type ID.
   *
   * This is the entity type ID of the entity the event is fired for.
   *
   * @return string
   *   The email event entity type ID.
   */
  public function getEntityTypeId();

  /**
   * Extracts the entity from the given event.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The event.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The extracted entity.
   */
  public function extractEntityFromEvent(Event $event);

}
