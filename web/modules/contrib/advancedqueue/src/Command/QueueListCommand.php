<?php

namespace Drupal\advancedqueue\Command;

// @codingStandardsIgnoreStart
use Drupal\advancedqueue\Job;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Console\Core\Style\DrupalStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
// @codingStandardsIgnoreEnd

/**
 * Queue list command.
 *
 * @package Drupal\advancedqueue
 *
 * @DrupalCommand (
 *   extension="advancedqueue",
 *   extensionType="module"
 * )
 */
class QueueListCommand extends Command {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new QueueListCommand object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct();

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('advancedqueue:queue:list')
      ->setDescription($this->trans('commands.advancedqueue.queue.list.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $count_labels = [
      Job::STATE_QUEUED => $this->trans('commands.advancedqueue.queue.list.counts.queued'),
      Job::STATE_PROCESSING => $this->trans('commands.advancedqueue.queue.list.counts.processing'),
      Job::STATE_SUCCESS => $this->trans('commands.advancedqueue.queue.list.counts.success'),
      Job::STATE_FAILURE => $this->trans('commands.advancedqueue.queue.list.counts.failure'),
    ];

    $queue_storage = $this->entityTypeManager->getStorage('advancedqueue_queue');
    $rows = [];
    foreach ($queue_storage->loadMultiple() as $queue) {
      /** @var \Drupal\advancedqueue\Entity\QueueInterface $queue */
      $jobs = [];
      foreach ($queue->getBackend()->countJobs() as $state => $count) {
        $jobs[] = sprintf($count_labels[$state], $count);
      }

      $rows[] = [
        'id' => $queue->id(),
        'label' => $queue->label(),
        'jobs' => implode(' | ', $jobs),
      ];
    }

    $io = new DrupalStyle($input, $output);
    $io->table([
      $this->trans('commands.advancedqueue.queue.list.table-headers.id'),
      $this->trans('commands.advancedqueue.queue.list.table-headers.label'),
      $this->trans('commands.advancedqueue.queue.list.table-headers.jobs'),
    ], $rows, 'default');
  }

}
