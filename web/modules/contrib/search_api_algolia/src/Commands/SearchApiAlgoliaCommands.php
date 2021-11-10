<?php

namespace Drupal\search_api_algolia\Commands;

use Algolia\AlgoliaSearch\SearchClient;
use Drupal\Core\Database\Database;
use Drush\Commands\DrushCommands;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Database\Connection;

/**
 * Class Search Api Algolia commands.
 *
 * @package Drupal\search_api_algolia\Commands
 */
class SearchApiAlgoliaCommands extends DrushCommands {

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $drupalLogger;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * SearchApiAlgoliaCommands constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   Logger factory.
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection object.
   */
  public function __construct(
    LoggerChannelFactoryInterface $loggerChannelFactory,
    Connection $connection ) {
    $this->drupalLogger = $loggerChannelFactory->get('SearchApiAlgoliaCommands');
    $this->connection = $connection;
  }

  /**
   * Delete multiple objects from algolia.
   *
   * @param array $options
   *   (optional) An array of options.
   *
   * @command search_api_algolia:delete
   *
   * @aliases sapia-d
   *
   * @option batch-size
   *   The number of items to check per batch run.
   *
   * @usage drush sapia-d
   *   Fetch and delete objects  in algolia.
   * @usage drush sapia-d --batch-size=100
   *   Fetch and delete objects  in algolia with batch of 100.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function deleteFromAlgolia(array $options = ['batch-size' => NULL]) {
    $batch_size = $options['batch-size'] ?? 100;

    $batch = [
      'finished' => [__CLASS__, 'batchFinish'],
      'title' => dt('Deleting multiple Objects algolia'),
      'init_message' => dt('Starting objects deleting...'),
      'progress_message' => dt('Completed @current step of @total.'),
      'error_message' => dt('encountered error while deleting objects.'),
    ];

    $items = $this->connection->select('search_api_algolia_deleted_items', 'sapi')
      ->fields('sapi', ['index_id', 'object_id'])
      ->execute()->fetchAll();

    if (empty($items)) {
      $this->drupalLogger->notice('No items left to process.');
      return;
    }

    foreach ($items as $item) {
      $itemsByIndex[$item->index_id][] = $item->object_id;
    }

    $batch['operations'][] = [[__CLASS__, 'batchStart'], [count($items)]];
    foreach ($itemsByIndex ?? [] as $index => $item_ids) {
      foreach (array_chunk($item_ids, $batch_size) as $chunk) {
        $batch['operations'][] = [
          [__CLASS__, 'batchProcess'],
          [$chunk, $index],
        ];
      }
    }

    // Prepare the output of processed items and show.
    batch_set($batch);
    drush_backend_batch_process();
  }

  /**
   * Batch callback; initialize the batch.
   *
   * @param int $total
   *   The total number of nids to process.
   * @param mixed|array $context
   *   The batch current context.
   */
  public static function batchStart($total, &$context) {
    $context['results']['total'] = $total;
    $context['results']['count'] = 0;
    $context['results']['timestart'] = microtime(TRUE);
  }

  /**
   * Batch API callback; delete objects in algolia.
   *
   * @param array $object_ids
   *   A batch size.
   * @param string $index_name
   *   Algolia index name.
   * @param mixed|array $context
   *   The batch current context.
   *
   * @throws \AlgoliaSearch\AlgoliaException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function batchProcess(array $object_ids, $index_name, &$context) {
    // Store Algolia Connection credentials in context if not available.
    if (empty($context['app_id']) || empty($context['app_secret_admin'])) {
      $backend_config_algolia = \Drupal::config('search_api.server.algolia')->get('backend_config');
      $context['app_id'] = $backend_config_algolia['application_id'];
      $context['app_secret_admin'] = $backend_config_algolia['api_key'];
    }

    // Load the Algolia Index.
    $client = SearchClient::create($context['app_id'], $context['app_secret_admin']);
    $index = $client->initIndex($index_name);

    // Delete the objects in bulk.
    $response = $index->deleteObjects($object_ids);
    \Drupal::logger('SearchApiAlgoliaCommands')->notice('Deletion requested for IDs: @ids on Algolia for Index: @index, Response: @response.', [
      '@response' => json_encode($response->getBody()),
      '@index'    => $index_name,
      '@ids'      => implode(',', $object_ids),
    ]);

    // Update the count of items processed.
    $context['results']['count'] += count($object_ids);

    // Remove the processed object ids from DB for the current index.
    \Drupal::database()->delete('search_api_algolia_deleted_items')
      ->condition('object_id', $object_ids, 'IN')
      ->condition('index_id', $index_name)
      ->execute();

    // Nice message for user / console.
    $context['message'] = dt('Deleted items @count out of @total.', [
      '@count' => $context['results']['count'],
      '@total' => $context['results']['total'],
    ]);
  }

  /**
   * Finishes the update process and prints the results.
   *
   * @param bool $success
   *   Indicate that the batch API tasks were all completed successfully.
   * @param array $results
   *   An array of all the results that were updated.
   * @param array $operations
   *   A list of all the operations that had not been completed by batch API.
   */
  public static function batchFinish($success, array $results, array $operations) {
    $logger = \Drupal::logger('search_api_algolia');
    if ($success) {
      if ($results['count']) {
        // Display Script execution time.
        $time_end = microtime(TRUE);
        $execution_time = ($time_end - $results['timestart']) / 60;

        $logger->notice('Total @count items processed in time: @time.', [
          '@count' => $results['count'],
          '@time' => $execution_time,
        ]);
      }
      else {
        $logger->notice('No items processed.');
      }
    }
    else {
      $error_operation = reset($operations);
      $logger->error('An error occurred while processing @operation with arguments : @args', [
        '@operation' => $error_operation[0],
        '@args' => print_r($error_operation[0], TRUE),
      ]);
    }
  }

}
