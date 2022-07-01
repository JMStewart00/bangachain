<?php

namespace Drupal\commerce_email\EventSubscriber;

use Drupal\commerce_email\EmailEventManager;
use Drupal\commerce_email\EmailSenderInterface;
use Drupal\commerce_email\Entity\EmailInterface;
use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\advancedqueue\Job;

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
   * The email event plugin manager.
   *
   * @var \Drupal\commerce_email\EmailEventManager
   */
  protected $emailEventManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The commerce_email_queue queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EmailSubscriber object.
   *
   * @param \Drupal\commerce_email\EmailSenderInterface $email_sender
   *   The email sender.
   * @param \Drupal\commerce_email\EmailEventManager $email_event_manager
   *   The email event plugin manager.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EmailSenderInterface $email_sender, EmailEventManager $email_event_manager, EventDispatcherInterface $event_dispatcher, QueueFactory $queue_factory, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    $this->emailSender = $email_sender;
    $this->emailEventManager = $email_event_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->queue = $queue_factory->get('commerce_email_queue');
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    // Subscribe to kernel request very early.
    $events[KernelEvents::REQUEST][] = ['onRequest', 900];

    return $events;
  }

  /**
   * Add the plugin events at this stage - early in the process.
   *
   * @see https://drupal.stackexchange.com/questions/274177/plugins-before-event-subscribers
   */
  public function onRequest() {
    // Find every event mentioned in a plugin...
    foreach ($this->emailEventManager->getDefinitions() as $definition) {
      // Add the event to the dispatcher as a listener, to call that routine...
      $this->eventDispatcher->addListener(
        $definition['event_name'],
        [$this, 'onEvent']
      );
    }
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
    $email_storage = $this->entityTypeManager->getStorage('commerce_email');
    /** @var \Drupal\commerce_email\Entity\EmailInterface[] $emails */
    $emails = $email_storage->loadByProperties(['status' => TRUE]);
    foreach ($emails as $email) {
      $email_event = $email->getEvent();
      if ($email_event->getEventName() == $event_name) {
        $entity = $email_event->extractEntityFromEvent($event);
        if ($email->applies($entity)) {
          $this->sendEmail($email, $entity);
        }
      }
    }
  }

  /**
   * Sends emails in a queue or immediately.
   *
   * @param \Drupal\commerce_email\Entity\EmailInterface $email
   *   The email.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   */
  protected function sendEmail(EmailInterface $email, ContentEntityInterface $entity) {
    if ($email->shouldQueue()) {
      $queue_item = [
        'email_id' => $email->id(),
        'entity_id' => $entity->id(),
        'entity_type_id' => $entity->getEntityTypeId(),
      ];
      if ($this->moduleHandler->moduleExists('advancedqueue')) {
        $queue_storage = $this->entityTypeManager->getStorage('advancedqueue_queue');
        /** @var \Drupal\advancedqueue\Entity\QueueInterface $queue */
        $queue = $queue_storage->load('commerce_email');
        $job = Job::create('commerce_email_queue', $queue_item);
        $queue->enqueueJob($job);
      }
      else {
        $this->queue->createItem($queue_item);
      }
    }
    else {
      $this->emailSender->send($email, $entity);
    }
  }

}
