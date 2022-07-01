<?php

namespace Drupal\advancedqueue\Command;

// @codingStandardsIgnoreStart
use Drupal\advancedqueue\ProcessorInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Console\Core\Style\DrupalStyle;
// @codingStandardsIgnoreEnd

/**
 * Class ProcessQueueCommand.
 *
 * @DrupalCommand (
 *   extension="advancedqueue",
 *   extensionType="module"
 * )
 */
class QueueProcessCommand extends Command {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The queue processor.
   *
   * @var \Drupal\advancedqueue\ProcessorInterface
   */
  protected $processor;

  /**
   * Constructs a new QueueProcessCommand object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\advancedqueue\ProcessorInterface $processor
   *   The queue processor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ProcessorInterface $processor) {
    parent::__construct();

    $this->entityTypeManager = $entity_type_manager;
    $this->processor = $processor;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('advancedqueue:queue:process')
      ->addArgument('queue_id', InputArgument::REQUIRED, 'The queue ID')
      ->setDescription($this->trans('commands.advancedqueue.queue.process.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $queue_id = $input->getArgument('queue_id');
    $queue_storage = $this->entityTypeManager->getStorage('advancedqueue_queue');
    /** @var \Drupal\advancedqueue\Entity\QueueInterface $queue */
    $queue = $queue_storage->load($queue_id);
    if (!$queue) {
      $message = $this->trans('commands.advancedqueue.queue.process.messages.not-found');
      throw new \RuntimeException(sprintf($message, $queue_id));
    }

    $io = new DrupalStyle($input, $output);
    $start = microtime(TRUE);
    $num_processed = $this->processor->processQueue($queue);
    $elapsed = microtime(TRUE) - $start;

    $io->success(sprintf(
      $this->trans('commands.advancedqueue.queue.process.messages.finished'),
      $num_processed,
      $queue->label(),
      round($elapsed, 2)
    ));
  }

}
