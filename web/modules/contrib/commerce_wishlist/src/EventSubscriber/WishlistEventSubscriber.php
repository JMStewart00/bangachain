<?php

namespace Drupal\commerce_wishlist\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\commerce_wishlist\Event\WishlistEntityAddEvent;
use Drupal\commerce_wishlist\Event\WishlistEvents;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines the wishlist event subscriber.
 *
 * On adding an item to wishlist, the "added to wishlist" message will be shown.
 */
class WishlistEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new WishlistEventSubscriber object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   */
  public function __construct(MessengerInterface $messenger, TranslationInterface $string_translation) {
    $this->messenger = $messenger;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      WishlistEvents::WISHLIST_ENTITY_ADD => 'displayAddToWishlistMessage',
    ];
    return $events;
  }

  /**
   * Displays an add to wishlist message.
   *
   * @param \Drupal\commerce_wishlist\Event\WishlistEntityAddEvent $event
   *   The add to wishlist event.
   */
  public function displayAddToWishlistMessage(WishlistEntityAddEvent $event) {
    $purchased_entity = $event->getEntity();
    $this->messenger->addStatus($this->t('@entity added to <a href=":url">your wishlist</a>.', [
      '@entity' => $purchased_entity->label(),
      ':url' => Url::fromRoute('commerce_wishlist.page')->toString(),
    ]));
  }

}
