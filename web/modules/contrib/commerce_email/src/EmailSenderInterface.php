<?php

namespace Drupal\commerce_email;

use Drupal\commerce_email\Entity\EmailInterface;
use Drupal\Core\Entity\ContentEntityInterface;

interface EmailSenderInterface {

  /**
   * Sends the given email.
   *
   * @param \Drupal\commerce_email\Entity\EmailInterface $email
   *   The email.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE if the email was sent successfully, FALSE otherwise.
   *
   * @throws \InvalidArgumentException
   *   Thrown when given an entity that doesn't match the email event.
   */
  public function send(EmailInterface $email, ContentEntityInterface $entity);

}
