<?php

namespace Drupal\commerce_email;

use Drupal\commerce\MailHandlerInterface;
use Drupal\commerce_email\Entity\EmailInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EmailSender object.
   *
   * @param \Drupal\commerce\MailHandlerInterface $mail_handler
   *   The mail handler.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(MailHandlerInterface $mail_handler, Token $token, EntityTypeManagerInterface $entity_type_manager) {
    $this->mailHandler = $mail_handler;
    $this->token = $token;
    $this->entityTypeManager = $entity_type_manager;
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
      'id' => 'commerce_email',
      'from' => $this->replaceTokens($email->getFrom(), $entity),
      'cc' => $this->replaceTokens($email->getCc(), $entity),
      'bcc' => $this->replaceTokens($email->getBcc(), $entity),
    ];
    $result = $this->mailHandler->sendMail($to, $subject, $body, $params);

    if ($email->getLogIntoOrder() && $event_entity_type_id === 'commerce_order') {
      /** @var \Drupal\commerce_log\LogStorageInterface $log_storage */
      $log_storage = $this->entityTypeManager->getStorage('commerce_log');
      $email_id = $email->id();
      $template_id = $result ? 'mail_' . $email_id : 'mail_' . $email_id . '_failure';
      if (!isset($definitions[$template_id])) {
        $template_id = $result ? 'order_mail' : 'order_mail_failure';
      }

      $log_params = [
        'id' => $email->label(),
        'to_email' => $to,
      ];
      $log_storage->generate($entity, $template_id, $log_params)->save();
    }

    return $result;

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
