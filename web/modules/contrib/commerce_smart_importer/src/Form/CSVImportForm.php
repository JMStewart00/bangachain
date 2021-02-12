<?php

namespace Drupal\commerce_smart_importer\Form;

use Drupal\commerce_smart_importer\Plugin\CommerceSmartImporerService;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_smart_importer\ImportingParameters;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\commerce_smart_importer\CommerceSmartImporterConstants;

/**
 * Class CSVImportForm enales you upload CSV and import products from it.
 */
class CSVImportForm extends FormBase {

  /**
   * Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Smart importer service.
   *
   * @var \Drupal\commerce_smart_importer\Plugin\CommerceSmartImporerService
   */
  protected $smartImporterService;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'CommerceCsvImportForm';
  }

  /**
   * CSVIntroductionForm constructor.
   */
  public function __construct(Connection $connection,
                              CommerceSmartImporerService $service,
                              ConfigFactory $configFactory,
                              EntityTypeManagerInterface $entityTypeManager,
                              Renderer $renderer) {
    $this->database = $connection;
    $this->smartImporterService = $service;
    $this->configFactory = $configFactory;
    $this->entityTypeManager = $entityTypeManager;
    $this->renderer = $renderer;
  }

  /**
   * Create.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('commerce_smart_importer.service'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $sql = $this->database->query("SELECT store_id FROM commerce_store_field_data LIMIT 1")
      ->fetchAll();
    if (count($sql) != 0) {
      if ($_GET['action'] == 'check') {

        $form['csv_file'] = [
          '#type' => 'managed_file',
          '#title' => $this->t('Upload CSV files here'),
          '#upload_validators' => [
            'file_validate_extensions' => ['csv'],
          ],
          '#required' => TRUE,
        ];

        $form['image_upload'] = [
          '#markup' => '<div id="dropzone" class="dropzone needsclick dz-clickable dz-started"></div>',
        ];
        $form['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Upload'),
        ];
        $form['#attached']['library'] = [
          'commerce_smart_importer/commerce-smart-importer-dropzone-library',
          'commerce_smart_importer/commerce-smart-importer-import-library',
        ];
      }
      elseif ($_GET['action'] == 'load') {
        $form['#attached']['library'] = [
          'commerce_smart_importer/commerce-smart-importer-importer-load-library',
        ];
        $form['importing_rules'] = [
          '#type' => 'checkboxes',
          '#options' => [
            'duplicates' => $this->t('Products will be imported but duplicate values will be eliminated'),
            'cardinality' => $this->t('If allowed numbers of values are exceeded, only part will be used'),
            'default_value' => $this->t('Import products with system default values(if invalid or empty)'),
            'incorrect_value' => $this->t('Import products with invalid - not mandatory fields'),
            'variations' => $this->t('Create product even if some variations are not valid'),
            'sku' => $this->t('If checked Importer will generate new sku for products where sku is already taken, else will skip'),
          ],
          '#title' => $this->t('Select options'),
          '#default_value' => [
            'duplicates',
            'cardinality',
            'default_value',
            'incorrect_value',
            'variations',
          ],
        ];

        $form['import'] = [
          '#type' => 'submit',
          '#value' => $this->t('Import'),
        ];
        $save = CommerceSmartImporterConstants::TEMP_DIR . '/' . $_GET['import_name'];
        $errors = json_decode(file_get_contents($save . '/log.json'), TRUE);
        $field_defintions = json_decode(file_get_contents($save . '/field_definitions.json'), TRUE);

        $log = [
          '#theme' => 'commerce_smart_importer_error_logger',
          '#error_log' => $errors,
          '#field_definitions' => $field_defintions,
        ];
        $form['log'] = [
          '#markup' => '<div class="log-section" id="log-section" align="center"> <h2 class="log-section__title">Notices and errors in CSV</h2>' .
          $this->renderer->render($log) . '</div>',
          '#allowed_tags' => [
            'div',
            'section',
            'input',
            'span',
            'b',
            'br',
            'button',
            'fieldset',
            'legend',
            'p',
            'label',
            'strong',
            'h2',
          ],
        ];
      }
    }
    else {
      $this->messenger()->addStatus($this->t("In order to use this module you'll have to create at least one store"));
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $action = $_GET['action'];

    if ($action == 'load') {
      // Building parameters.
      $importingRules = $form_state->getValue('importing_rules');
      $importParameters = new ImportingParameters();
      if (empty($importingRules['duplicates'])) {
        $importParameters->duplicateValues = FALSE;
      }
      if (empty($importingRules['cardinality'])) {
        $importParameters->exceedsCardinality = FALSE;
      }
      if (empty($importingRules['default_value'])) {
        $importParameters->defaultValues = FALSE;
      }
      if (empty($importingRules['incorrect_value'])) {
        $importParameters->incorrectValues = FALSE;
      }
      if (empty($importingRules['variations'])) {
        $importParameters->notValidVariations = FALSE;
      }
      if (empty($importingRules['sku'])) {
        $importParameters->sku = FALSE;
      }
      $uri = CommerceSmartImporterConstants::TEMP_DIR . '/' . $_GET['import_name'] . '/products.csv';
      $external_folders = [CommerceSmartImporterConstants::TEMP_DIR . '/' . $_GET['import_name']];
    }

    if ($action == 'check') {
      $fid = $form_state->getValue('csv_file')[0];
      $file = $this->entityTypeManager->getStorage('file')->load($fid);
      $uri = $file->getFileUri();
      $importParameters = new ImportingParameters();
      $importParameters->disableAll();
      $external_folders = [CommerceSmartImporterConstants::TEMP_DIR . '/'];
    }
    $config = $this->smartImporterService->getConfig();
    $external_folders = array_merge($external_folders, $config['external_folders']);
    $count = $this->smartImporterService->countProductsAndVariations($uri);

    // Indexing labels.
    $csvData = fopen($uri, 'r');
    $headers = fgetcsv($csvData, 1024);
    fclose($csvData);

    $fields = $this->smartImporterService->getFieldDefinition();
    foreach ($headers as $index => $header) {
      foreach ($fields['product'] as $key => $field) {
        if ($field['label'] == $header) {
          $fields['product'][$key]['index'] = $index;
        }
      }
      foreach ($fields['variation'] as $key => $field) {
        if ($field['label'] == $header) {
          $fields['variation'][$key]['index'] = $index;
        }
      }
    }
    $required_unindexed = FALSE;
    foreach ($fields['product'] as $key => $field) {
      if (!array_key_exists('index', $field)) {
        if ($field['required']) {
          $this->messenger()->addError($this->t('Unindexed field') . ' ' . $field['label'] . $this->t(', this is required field'));
          $required_unindexed = TRUE;
        }
        else {
          unset($fields['product'][$key]);
          $this->messenger()->addWarning($this->t('Unindexed field') . ' ' . $field['label']);
        }
      }
    }
    foreach ($fields['variation'] as $key => $field) {
      if (!array_key_exists('index', $field)) {
        if ($field['required']) {
          $this->messenger()->addError($this->t('Unindexed field') . ' ' . $field['label'] . ', this is required field');
          $required_unindexed = TRUE;
        }
        else {
          unset($fields['variation'][$key]);
          $this->messenger()->addWarning($this->t('Unindexed field') . ' ' . $field['label']);
        }
      }
    }

    if ($required_unindexed) {
      $this->messenger()->addStatus($this->t('You will not be able to continue because there are some required fields that cant be indexed in given CSV'));
      return;
    }

    if ($action == 'check') {
      $upload_name = uniqid('Smart_Importer_temp_');
      if (!is_dir(CommerceSmartImporterConstants::TEMP_DIR)) {
        mkdir(CommerceSmartImporterConstants::TEMP_DIR);
      }
      mkdir(CommerceSmartImporterConstants::TEMP_DIR . '/' . $upload_name);
      $save = CommerceSmartImporterConstants::TEMP_DIR . '/' . $upload_name;
      $this->smartImporterService->changeFilePathInFieldDefinition($fields, CommerceSmartImporterConstants::TEMP_DIR . '/' . $upload_name);
      if (!copy($uri, $save . '/products.csv')) {
        throw new Exception('Could not save file to temp folder');
      }
    }
    elseif ($action == 'load') {
      $save = CommerceSmartImporterConstants::TEMP_DIR . '/' . $_GET['import_name'];
    }

    // Making batch.
    $batch = [
      'title' => $this->t('Importing all products'),
      'init_message' => $this->t('Beginning...'),
      'progress_message' => $this->t('Imported @current out of @total products groups'),
      'error_message' => $this->t('Something went wrong'),
      'progressive' => FALSE,
      'operations' => [],
    ];
    if ($action == 'check') {
      $batch['finished'] = [$this, 'finished'];
    }
    if ($action == 'load') {
      $batch['finished'] = [$this, 'finishedImporting'];
    }

    $limit_products = $config['batch_products'];

    $number_of_batches = ceil($count['product_count'] / $limit_products);

    for ($i = 0; $i < $number_of_batches; $i++) {
      $batch['operations'][] = [
        [$this, 'importProducts'],
        [$uri, $fields, $importParameters, $external_folders, $save],
      ];
    }
    batch_set($batch);
  }

  /**
   * Reads part of CSV file and imports it if it is valid.
   */
  public function importProducts($uri, $fields, ImportingParameters $parameters, $external_folders, $save, &$context) {
    $file = fopen($uri, 'r');
    if (!array_key_exists('last_stop', $context['results'])) {
      $import_name = explode('/', $save);
      // Initializing context.
      $context['results']['import_name'] = end($import_name);
      $context['results']['time'] = 0;
      fgetcsv($file);
      fgetcsv($file);
      $context['results']['current_row'] = 3;
      $context['results']['created'] = 0;
      if ($parameters->createProduct === FALSE) {
        touch($save . '/log.json');
        touch($save . '/override_values.json');
        file_put_contents($save . '/field_definitions.json', json_encode($fields, JSON_UNESCAPED_UNICODE));
      }
    }
    else {
      fseek($file, $context['results']['last_stop']);
    }
    if ($parameters->createProduct === FALSE) {
      $log = file_get_contents($save . '/log.json');
      if (!empty($log)) {
        $log = json_decode($log, TRUE);
      }
      else {
        $log = [];
      }
    }

    $current_stop = ftell($file);
    $first_variation_index = $this->getFirstVariationIndex($fields);
    $products = [];
    $product = [];
    $config = $this->smartImporterService->getConfig();
    $limit_products = $config['batch_products'];
    $override_values = [];
    if ($parameters->createProduct === TRUE) {
      $override_values = file_get_contents($save . '/override_values.json');
      if (empty($override_values)) {
        $override_values = [];
      }
      else {
        $override_values = json_decode($override_values, TRUE);
      }
    }
    // Getting data from CSV.
    $override_values_formatted = [];
    $temp_override_values = [];
    while (($line = fgetcsv($file)) !== FALSE) {
      if (!empty($line[0])) {
        if (empty($product)) {
          $product['product'] = array_slice($line, 0, $first_variation_index);
          $product['row'] = $context['results']['current_row'];
          $product['variations'][] = array_slice($line, $first_variation_index, count($line), TRUE);
          $temp_override_values['product'] = $this->getOverrideValue($override_values, $context['results']['current_row'], 'product');
          $temp_override_values['variations'][] = $this->getOverrideValue($override_values, $context['results']['current_row'], 'variation');
        }
        else {
          $products[] = $product;
          $override_values_formatted[] = $temp_override_values;
          if (count($products) == $limit_products) {
            $product = [];
            $temp_override_values = [];
            break;
          }
          $product = [];
          $temp_override_values = [];
          $product['product'] = array_slice($line, 0, $first_variation_index);
          $product['row'] = $context['results']['current_row'];
          $product['variations'][] = array_slice($line, $first_variation_index, count($line), TRUE);
          $temp_override_values['product'] = $this->getOverrideValue($override_values, $context['results']['current_row'], 'product');
          $temp_override_values['variations'][] = $this->getOverrideValue($override_values, $context['results']['current_row'], 'variation');
        }
      }
      else {
        $product['variations'][] = array_slice($line, $first_variation_index, count($line), TRUE);
        $temp_override_values['variations'][] = $this->getOverrideValue($override_values, $context['results']['current_row'], 'variation');
      }
      $context['results']['current_row']++;
      $current_stop = ftell($file);
    }
    if (!empty($product)) {
      $products[] = $product;
      $override_values_formatted[] = $temp_override_values;
    }
    $context['results']['time']++;
    $context['results']['last_stop'] = $current_stop;
    fclose($file);
    // Handling data.
    foreach ($products as $key => $product) {
      $temp_log = $this->smartImporterService->createNewProduct($fields, $product, $parameters, $external_folders, $override_values_formatted[$key]);
      if ($temp_log !== TRUE && $parameters->createProduct === FALSE) {
        $log[$product['row']] = $temp_log['error_log'];
      }
      else {
        if ($temp_log['created'] && $parameters->createProduct) {
          $context['results']['created']++;
        }
      }
    }
    // Putting error log.
    if ($parameters->createProduct === FALSE) {
      file_put_contents($save . '/log.json', json_encode($log, JSON_UNESCAPED_UNICODE));
    }
  }

  /**
   * Reads from file if there are override values.
   */
  private function getOverrideValue($override_values, $row, $type) {
    if (isset($override_values[$row][$type])) {
      return $override_values[$row][$type];
    }
    else {
      return [];
    }
  }

  /**
   * Called after check batch finishes.
   */
  public function finished($success, $results, $operations) {
    global $base_url;
    return new RedirectResponse($base_url . '/admin/commerce-csv-import?action=load&import_name=' . $results['import_name']);
  }

  /**
   * Called after import batch finishes.
   */
  public function finishedImporting($success, $results, $operations) {
    global $base_url;
    $this->messenger()->addStatus($this->t('Successfully created') . ' ' . $results['created']);
    return new RedirectResponse($base_url . '/admin/commerce-csv-import?action=check');
  }

  /**
   * Searches for first field of variation.
   */
  public function getFirstVariationIndex($fields) {
    $index = FALSE;
    foreach ($fields['variation'] as $field) {
      if (!array_key_exists('index', $field)) {
        continue;
      }
      if ($index === FALSE) {
        $index = $field['index'];
      }
      elseif ($index > $field['index']) {
        $index = $field['index'];
      }
    }
    return $index;
  }

}
