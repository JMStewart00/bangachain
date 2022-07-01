<?php

namespace Drupal\advancedqueue\Command;

// @codingStandardsIgnoreStart
use Drupal\advancedqueue\Job;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Database\Connection;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Core\Entity\EntityTypeManagerInterface;
// @codingStandardsIgnoreEnd

/**
 * Class ClearQueueCommand.
 *
 * @DrupalCommand (
 *   extension="advancedqueue",
 *   extensionType="module"
 * )
 */
class QueueClearCommand extends Command {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Allowed job states.
   *
   * @var array
   */
  protected $jobStates = [
    Job::STATE_QUEUED,
    Job::STATE_PROCESSING,
    Job::STATE_SUCCESS,
    Job::STATE_FAILURE,
  ];

  /**
   * Constructs a new QueueClearCommand object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database) {
    parent::__construct();

    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('advancedqueue:queue:clear')
      ->setDescription($this->trans('commands.advancedqueue.queue.clear.description'))
      ->addArgument(
        'queue_id',
        InputArgument::REQUIRED,
        $this->trans('commands.advancedqueue.queue.clear.options.queue-id')
      )
      ->addOption(
        'state',
        's',
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.advancedqueue.queue.clear.arguments.job-state')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $queue_id = $input->getArgument('queue_id');
    $state = strtolower($input->getOption('state'));

    $queue_storage = $this->entityTypeManager->getStorage('advancedqueue_queue');
    /** @var \Drupal\advancedqueue\Entity\QueueInterface $queue */
    $queue = $queue_storage->load($queue_id);
    if (!$queue) {
      $message = $this->trans('commands.advancedqueue.queue.clear.messages.not-found');
      throw new \RuntimeException(sprintf($message, $queue_id));
    }
    if ($state && !in_array($state, $this->jobStates)) {
      $message = $this->trans('commands.advancedqueue.queue.clear.messages.unknown-state');
      throw new \RuntimeException(sprintf($message, $state));
    }

    $query = $this->database->delete('advancedqueue');
    $query->condition('queue_id', $queue_id);
    if ($state) {
      $query->condition('state', $state, '=');
    }
    else {
      // All job states.
      $query->condition('state', $this->jobStates, 'IN');
    }
    $num_rows = $query->execute();

    $io = new DrupalStyle($input, $output);
    if ($num_rows) {
      $io->success(
        sprintf(
          $this->trans('commands.advancedqueue.queue.clear.messages.finished'),
          $num_rows,
          $queue->get('label')
        )
      );
    }
    else {
      $io->success(
        sprintf(
          $this->trans('commands.advancedqueue.queue.clear.messages.nothing-to-do'),
          $queue->get('label')
        )
      );
    }
  }

}
