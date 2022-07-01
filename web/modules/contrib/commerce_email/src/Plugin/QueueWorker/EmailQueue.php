<?php

namespace Drupal\commerce_email\Plugin\QueueWorker;

use Drupal\commerce_email\EmailSenderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A queue worker for sending emails.
 *
 * @QueueWorker(
 *  id = "commerce_email_queue",
 *  title = @Translation("Commerce Email Queue"),
 *  cron = {"time" = 60}
 * )
 */
class EmailQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

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
  public function processItem($data) {
    /** @var \Drupal\commerce_email\Entity\EmailInterface $email */
    $email = $this->entityTypeManager->getStorage('commerce_email')->load($data['email_id']);
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->entityTypeManager->getStorage($data['entity_type_id'])->load($data['entity_id']);

    if ($email && $entity) {
      $this->emailSender->send($email, $entity);
    }
  }

}
