<?php

namespace Drupal\commerce_wishlist\Mail;

use Drupal\commerce\MailHandlerInterface;
use Drupal\commerce_wishlist\Entity\WishlistInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines the wishlist share email.
 */
class WishlistShareMail implements WishlistShareMailInterface {

  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The commerce mail handler.
   *
   * @var \Drupal\commerce\MailHandlerInterface
   */
  protected $mailHandler;

  /**
   * Constructs a new WishlistShareMail object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\commerce\MailHandlerInterface $mail_handler
   *   The mail handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MailHandlerInterface $mail_handler) {
    $this->configFactory = $config_factory;
    $this->mailHandler = $mail_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function send(WishlistInterface $wishlist, $to) {
    $owner = $wishlist->getOwner();
    if (!$owner || $owner->isAnonymous()) {
      // Only wishlists belonging to authenticated users can be shared.
      return FALSE;
    }

    $subject = $this->t('Check out my @site-name wishlist', [
      '@site-name' => $this->configFactory->get('system.site')->get('name'),
    ]);
    $body = [
      '#theme' => 'commerce_wishlist_share_mail',
      '#wishlist_entity' => $wishlist,
    ];
    $params = [
      'id' => 'wishlist_share',
      'from' => $owner->getEmail(),
      'wishlist' => $wishlist,
    ];

    return $this->mailHandler->sendMail($to, $subject, $body, $params);
  }

}
