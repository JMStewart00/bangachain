<?php

namespace Drupal\commerce_email\EventSubscriber;

use Drupal\commerce_email\EmailSenderInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to Symfony events and maps them to email events.
 *
 * @todo Optimize performance by implementing an event map in \Drupal::state().
 *       This would allow us to subscribe only to events which have emails
 *       defined, and to load only those emails (instead of all of them).
 */
class EmailSubscriber implements EventSubscriberInterface {

  /**
   * The email sender.
   *
   * @var \Drupal\commerce_email\EmailSenderInterface
   */
  protected $emailSender;

  /**
   * Constructs a new EmailSubscriber object.
   *
   * @param \Drupal\commerce_email\EmailSenderInterface $email_sender
   *   The email sender.
   */
  public function __construct(EmailSenderInterface $email_sender) {
    $this->emailSender = $email_sender;
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    // This method could be called early when the container is built, so the
    // email event manager might not be available yet.
    if (!\Drupal::hasService('plugin.manager.commerce_email_event')) {
      return [];
    }

    /** @var \Drupal\Core\Plugin\DefaultPluginManager $email_event_manager */
    $email_event_manager = \Drupal::service('plugin.manager.commerce_email_event');
    $email_events = $email_event_manager->getDefinitions();
    $events = [];
    foreach ($email_events as $definition) {
      $events[$definition['event_name']][] = ['onEvent'];
    }

    return $events;
  }

  /**
   * Sends emails associated with the given event.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The event.
   * @param string $event_name
   *   The event name.
   */
  public function onEvent(Event $event, $event_name) {
    $email_storage = \Drupal::entityTypeManager()->getStorage('commerce_email');
    /** @var \Drupal\commerce_email\Entity\EmailInterface[] $emails */
    $emails = $email_storage->loadMultiple();
    foreach ($emails as $email) {
      $email_event = $email->getEvent();
      if ($email_event->getEventName() == $event_name) {
        $entity = $email_event->extractEntityFromEvent($event);
        if ($email->applies($entity)) {
          $this->emailSender->send($email, $entity);
        }
      }
    }
  }

}
