<?php

namespace Drupal\commerce_email;

use Drupal\commerce\MailHandlerInterface;
use Drupal\commerce_email\Entity\EmailInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Utility\Token;

class EmailSender implements EmailSenderInterface {

  /**
   * The mail handler.
   *
   * @var \Drupal\commerce\MailHandlerInterface
   */
  protected $mailHandler;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs a new EmailSender object.
   *
   * @param \Drupal\commerce\MailHandlerInterface $mail_handler
   *   The mail handler.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(MailHandlerInterface $mail_handler, Token $token) {
    $this->mailHandler = $mail_handler;
    $this->token = $token;
  }

  /**
   * {@inheritDoc}
   */
  public function send(EmailInterface $email, ContentEntityInterface $entity) {
    $entity_type_id = $entity->getEntityTypeId();
    $event_entity_type_id = $email->getEvent()->getEntityTypeId();
    if ($entity_type_id != $event_entity_type_id) {
      throw new \InvalidArgumentException(sprintf('The email requires a "%s" entity, but a "%s" entity was given.', $event_entity_type_id, $entity_type_id));
    }

    $short_entity_type_id = str_replace('commerce_', '', $entity_type_id);
    $to = $this->replaceTokens($email->getTo(), $entity);
    $subject = $this->replaceTokens($email->getSubject(), $entity);
    $body = [
      '#type' => 'inline_template',
      '#template' => $this->replaceTokens($email->getBody(), $entity),
      '#context' => [
        $short_entity_type_id => $entity,
      ],
    ];
    // @todo Figure out how to get the langcode generically.
    $params = [
      'id' => 'commerce_email_' . $email->id(),
      'from' => $this->replaceTokens($email->getFrom(), $entity),
      'cc' => $this->replaceTokens($email->getCc(), $entity),
      'bcc' => $this->replaceTokens($email->getBcc(), $entity),
    ];

    return $this->mailHandler->sendMail($to, $subject, $body, $params);
  }

  /**
   * Replaces tokens in the given value.
   *
   * @param string $value
   *   The value.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to use for token replacements.
   *
   * @return string
   *   The value with tokens replaced.
   */
  protected function replaceTokens($value, ContentEntityInterface $entity) {
    if (!empty($value)) {
      $value = $this->token->replace($value, [$entity->getEntityTypeId() => $entity]);
    }
    return $value;
  }

}
