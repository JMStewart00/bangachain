<?php

namespace Drupal\commerce_email\Plugin\AdvancedQueue\JobType;

use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
use Drupal\commerce_email\EmailSenderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the job type for sending emails.
 *
 * @AdvancedQueueJobType(
 *   id = "commerce_email_queue",
 *   label = @Translation("Commerce Email Queue"),
 *   max_retries = 3,
 *   retry_delay = 180,
 * )
 */
class EmailQueue extends JobTypeBase implements ContainerFactoryPluginInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The email sender.
   *
   * @var \Drupal\commerce_email\EmailSenderInterface
   */
  protected $emailSender;

  /**
   * Constructs a new EmailQueue object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_email\EmailSenderInterface $email_sender
   *   The email sender.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EmailSenderInterface $email_sender) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->emailSender = $email_sender;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('commerce_email.email_sender'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process(Job $job) {
    $payload = $job->getPayload();
    /** @var \Drupal\commerce_email\Entity\EmailInterface $email */
    $email = $this->entityTypeManager->getStorage('commerce_email')->load($payload['email_id']);
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->entityTypeManager->getStorage($payload['entity_type_id'])->load($payload['entity_id']);

    if (!$email || !$entity) {
      return JobResult::failure('Malformed queue entry.');
    }

    if ($this->emailSender->send($email, $entity)) {
      return JobResult::success('Email has been sent.');
    }

    return JobResult::failure('Email has not been sent.');
  }

}
