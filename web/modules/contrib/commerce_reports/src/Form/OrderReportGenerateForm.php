<?php

namespace Drupal\commerce_reports\Form;

use Drupal\commerce_reports\ReportTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Profides a form for bulk generating order reports.
 */
class OrderReportGenerateForm extends FormBase {

  /**
   * The number of orders to process in each batch.
   *
   * @var int
   */
  const BATCH_SIZE = 25;

  /**
   * The report type manager.
   *
   * @var \Drupal\commerce_reports\ReportTypeManager
   */
  protected $reportTypeManager;

  /**
   * Constructs a new OrderReportGenerateForm object.
   *
   * @param \Drupal\commerce_reports\ReportTypeManager $report_type_manager
   *   The order report type manager.
   */
  public function __construct(ReportTypeManager $report_type_manager) {
    $this->reportTypeManager = $report_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.commerce_report_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_reports_generate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $plugin_types = $this->reportTypeManager->getDefinitions();
    $plugin_options = [];
    foreach ($plugin_types as $plugin_id => $plugin_definition) {
      $plugin_options[$plugin_id] = $plugin_definition['label']; 
    }
    asort($plugin_options);
    $form['plugin_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Report types'),
      '#description' => $this->t('Select all report types or a single report type to be generated.'),
      '#empty_option' => $this->t('All Reports'),
      '#empty_value' => '',
      '#options' => $plugin_options,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate'),
      '#button_type' => 'primary',
    ];

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $plugin_id = $values['plugin_id'];  	
    $batch = [
      'title' => $this->t('Generating reports'),
      'progress_message' => '',
      'operations' => [
        [
          [get_class($this), 'processBatch'],
          [$plugin_id],
        ],
      ],
      'finished' => [$this, 'finishBatch'],
    ];
    batch_set($batch);
    $form_state->setRedirect('commerce.configuration');
  }

  /**
   * Processes the batch and generates the order reports.
   *
   * @param array $context
   *   The batch context information.
   */
  public static function processBatch($plugin_id, array &$context) {
    $order_storage = \Drupal::entityTypeManager()->getStorage('commerce_order');

    // Initialization.
    if (empty($context['sandbox'])) {
      $context['results']['total_generated'] = 0;

      // Determine maximum id for a non-draft order.
      $order_ids = $order_storage->getQuery()
        ->condition('state', 'draft', '<>')
        ->sort('order_id', 'DESC')
        ->range(0, 1)
        ->execute();

      // No orders to process.
      if (empty($order_ids)) {
        $context['finished'] = 1;
        return;
      }
      $context['sandbox']['maximum_id'] = reset($order_ids);
      $context['sandbox']['current_offset'] = 0;
    }

    $maximum_id = $context['sandbox']['maximum_id'];
    $current_offset = &$context['sandbox']['current_offset'];
    $max_remaining = $maximum_id - $current_offset;

    if ($max_remaining < 1) {
      $context['finished'] = 1;
      return;
    }
    $limit = ($max_remaining < self::BATCH_SIZE) ? $max_remaining : self::BATCH_SIZE;

    // Get a batch of orders to be processed.
    $batch_orders_ids = $order_storage->getQuery()
      ->sort('order_id')
      ->range($current_offset, $limit)
      ->execute();

    // Generate order reports for the batch (after deleting any existing reports).
    $order_report_generator = \Drupal::service('commerce_reports.order_report_generator');
    $generated = $order_report_generator->refreshReports($batch_orders_ids, $plugin_id);

    $current_offset += $limit;
    $context['results']['total_generated'] += $generated;

    // Finished when order with maximum id has been processed in the batch.
    $batch_maximum_id = end($batch_orders_ids);
    if ($batch_maximum_id >= $maximum_id) {
      $context['finished'] = 1;
      return;
    }

    $context['message'] = t('Generated reports for @created orders of @total_quantity.', [
      '@created' => $batch_maximum_id,
      '@total_quantity' => $maximum_id,
    ]);
    $context['finished'] = $batch_maximum_id / $maximum_id;
  }

  /**
   * Batch finished callback: display batch statistics.
   *
   * @param bool $success
   *   Indicates whether the batch has completed successfully.
   * @param mixed[] $results
   *   The array of results gathered by the batch processing.
   * @param string[] $operations
   *   If $success is FALSE, contains the operations that remained unprocessed.
   */
  public static function finishBatch($success, array $results, array $operations) {
    if ($success) {
      \Drupal::messenger()->addMessage(\Drupal::translation()->formatPlural(
        $results['total_generated'],
        'Generated reports for 1 order.',
        'Generated reports for @count orders.'
      ));
    }
    else {
      $error_operation = reset($operations);
      \Drupal::messenger()->addError(t('An error occurred while processing @operation with arguments: @args', [
        '@operation' => $error_operation[0],
        '@args' => print_r($error_operation[0], TRUE),
      ]));
    }
  }

}
