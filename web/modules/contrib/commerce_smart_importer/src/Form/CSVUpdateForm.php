<?php

namespace Drupal\commerce_smart_importer\Form;

use Drupal\commerce_smart_importer\ImportingParameters;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Masterminds\HTML5\Exception;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\Product;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_smart_importer\Plugin\CommerceSmartImporerService;
use Drupal\Core\Render\Renderer;
use Drupal\commerce_smart_importer\CommerceSmartImporterConstants;

/**
 * Class CSVUpdateForm.
 */
class CSVUpdateForm extends FormBase {

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
   * Returns form id.
   */
  public function getFormId() {
    return 'CommerceCsvUpdateForm';
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
   * Builds form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $sql = $this->database->query("SELECT store_id FROM commerce_store_field_data LIMIT 1")->fetchAll();
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
        ];
      }
      elseif ($_GET['action'] == 'load') {
        $save = CommerceSmartImporterConstants::TEMP_DIR . '/' . $_GET['import_name'];
        $errors = json_decode(file_get_contents($save . '/log.json'), TRUE);

        $form['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Update everything that is accepted'),
        ];
        $form['image_action'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Append images'),
          '#description' => $this->t('If this box is checked images provided in file will be appended to current images if allowed number of values is not reached, else it will just be replaced'),
        ];
        $field_defintions = json_decode(file_get_contents($save . '/field_definitions.json'), TRUE);
        $log = [
          '#theme' => 'commerce_smart_importer_error_logger',
          '#error_log' => $errors,
          '#field_definitions' => $field_defintions,
          '#log_type' => 'update',
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
        $form['#attached']['library'] = [
          'commerce_smart_importer/commerce-smart-importer-import-library',
        ];
      }
    }

    return $form;
  }

  /**
   * Form submit.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $action = $_GET['action'];
    if ($action == 'check') {
      $fid = $form_state->getValue('csv_file')[0];
      $file = $this->entityTypeManager->getStorage('file')->load($fid);
      $uri = $file->getFileUri();
      $importing_parameters = new ImportingParameters();
      $importing_parameters->disableAll();
      $external_folders = [CommerceSmartImporterConstants::TEMP_DIR . '/'];
    }

    if ($action == 'load') {
      $uri = CommerceSmartImporterConstants::TEMP_DIR . '/' . $_GET['import_name'] . '/products.csv';
      $external_folders = [CommerceSmartImporterConstants::TEMP_DIR . '/' . $_GET['import_name']];
      $importing_parameters = new ImportingParameters();
      $importing_parameters->incorrectValues = FALSE;
      $importing_parameters->defaultValues = FALSE;
      $importing_parameters->exceedsCardinality = FALSE;
      $importing_parameters->duplicateValues = FALSE;
      if ($form_state->getValue('image_action') == 0) {
        $importing_parameters->appendImages = FALSE;
      }
    }

    $config = $this->smartImporterService->getConfig();
    $external_folders = array_merge($external_folders, $config['external_folders']);

    // Read CSV.
    $csvData = fopen($uri, 'r');
    $headers = fgetcsv($csvData, 1024);
    // Empty that extra line.
    fgetcsv($csvData, 1024);
    fclose($csvData);

    foreach ($headers as $key => $header) {
      if (mb_detect_encoding($header) == 'UTF-8') {
        $headers[$key] = mb_convert_encoding(trim($header), 'ASCII');
        $headers[$key] = str_replace('?', '', $headers[$key]);
      } else {
        $headers[$key] = trim($header);
      }
    }

    // Indexing labels.
    $fields = $this->smartImporterService->getFieldDefinition(TRUE);
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

    foreach ($fields['product'] as $key => $field) {
      if (!array_key_exists('index', $field)) {
        unset($fields['product'][$key]);
      }
    }
    foreach ($fields['variation'] as $key => $field) {
      if (!array_key_exists('index', $field)) {
        unset($fields['variation'][$key]);
      }
    }

    if (count($fields['variation']) > 0) {
      $variation_has_identifier = $this->hasIdentifier($fields, 'variation');
    }
    else {
      $variation_has_identifier = TRUE;
    }

    if ($variation_has_identifier && count($fields['variation']) > 0) {
      $product_has_identifier = TRUE;
    }
    elseif (count($fields['product']) > 0) {
      $product_has_identifier = $this->hasIdentifier($fields, 'product');
    }
    else {
      $product_has_identifier = TRUE;
    }

    if ($product_has_identifier === FALSE) {
      $this->messenger()->addError($this->t('Product has no identifier, and cannot be identified'));
    }

    if ($variation_has_identifier === FALSE) {
      $this->messenger()->addError($this->t('Variation has no identifier, and cannot be identified'));
    }

    if (!$variation_has_identifier && !$product_has_identifier) {
      return;
    }

    $count = $this->smartImporterService->countProductsAndVariations($uri);
    $products_per_batch = 25;
    $number_of_batches = ceil($count['variation_count'] / $products_per_batch);

    if ($action == 'check') {
      $upload_name = uniqid('Smart_Importer_temp_');
      if (!is_dir(CommerceSmartImporterConstants::TEMP_DIR . '/')) {
        mkdir(CommerceSmartImporterConstants::TEMP_DIR . '/');
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
      'title' => $this->t('Updating all products'),
      'init_message' => $this->t('Beginning...'),
      'progress_message' => $this->t('Checked @current out of @total product groups'),
      'error_message' => $this->t('Something went wrong'),
      'finished' => [$this, 'finished'],
      'progressive' => FALSE,
      'operations' => [],
    ];

    if ($action == 'check') {
      $batch['finished'] = [$this, 'finished'];
    }
    if ($action == 'load') {
      $batch['finished'] = [$this, 'finishedImporting'];
    }

    for ($i = 0; $i < $number_of_batches; $i++) {
      $batch['operations'][] = [
        [
          $this,
          'readCsvProductsToUpdate',
        ],
        [
          $uri,
          $fields,
          25,
          $importing_parameters,
          $external_folders,
          $save,
        ],
      ];
    }

    batch_set($batch);
  }

  /**
   * Reads and formats products for update.
   */
  public function readCsvProductsToUpdate($uri, $fields, $offset, ImportingParameters $parameters, $external_folders, $save, &$context) {

    $identifiersIndex = $this->getIdentifiersIndex($fields);

    $file = fopen($uri, 'r');
    if (!array_key_exists('last_stop', $context['results'])) {
      $import_name = explode('/', $save);
      $context['results']['import_name'] = end($import_name);
      $context['results']['current_row'] = 3;
      fgetcsv($file);
      fgetcsv($file);
      if ($parameters->createProduct === FALSE) {
        touch($save . '/log.json');
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

    $counter = 0;
    while (($line = fgetcsv($file)) !== FALSE) {
      $temp_log = [
        'product' => [
          'has_log' => FALSE,
          'initial' => TRUE,
        ],
        'variations' => [
          [
            'has_log' => FALSE,
            'initial' => TRUE,
          ],
        ],
      ];
      if ($counter == $offset) {
        break;
      }
      $entity_variation = FALSE;
      if (!empty($fields['variation'])) {
        $entity_variation = $this->loadVariationByIndex($fields['variation'], $identifiersIndex['variation'], $line);
        if ($entity_variation !== FALSE) {
          $this->reformatLine($line, $fields['variation']);
          $has_log = $this->smartImporterService->updateProduct($entity_variation, $fields['variation'], $line, $external_folders, $parameters);
          if ($has_log !== TRUE) {
            $temp_log['variations'][0] = $has_log;
            $temp_log['product']['has_log'] = TRUE;
          }
        }
      }
      if (!empty($fields['product'])) {
        if (!$this->isEmptyLine($fields['product'], $line)) {
          $entity = $this->loadProductByIndex($fields['product'], $identifiersIndex['product'], $line, $entity_variation);
          if ($entity !== FALSE) {
            $this->reformatLine($line, $fields['product']);
            $temp_log['product'] = $this->smartImporterService->updateProduct($entity, $fields['product'], $line, $external_folders, $parameters);
          }
        }
      }
      $log[$context['results']['current_row']] = $temp_log;
      $context['results']['last_stop'] = ftell($file);
      $context['results']['current_row']++;
      $counter++;
    }
    if ($parameters->createProduct === FALSE) {
      file_put_contents($save . '/log.json', json_encode($log, JSON_UNESCAPED_UNICODE));
    }
  }

  /**
   * Reformat price and currency.
   */
  public function reformatLine(&$line, $fields) {
    foreach ($fields as $field) {
      if ($field['machine_names'] == 'currency') {
        $currency = $line[$field['index']];
        unset($line[$field['index']]);
        break;
      }
    }
    if (!isset($currency)) {
      return;
    }
    foreach ($fields as $field) {
      if ($field['field_types'] == 'commerce_price') {
        $line[$field['index']] .= ' ' . $currency;
      }
    }
  }

  /**
   * Called when check update finishes.
   */
  public function finished($success, $results, $operations) {
    global $base_url;
    return new RedirectResponse($base_url . '/admin/commerce-csv-update?action=load&import_name=' . $results['import_name']);
  }

  /**
   * Called when load update finishes.
   */
  public function finishedImporting($success, $results, $operations) {
    global $base_url;
    return new RedirectResponse($base_url . '/admin/commerce-csv-update?action=check');
  }

  /**
   * Checks if array contains at least one product identifier.
   */
  public function hasIdentifier($field_definitions, $type) {
    $identifiers = $this->smartImporterService->getIdentifierFields();
    foreach ($field_definitions[$type] as $field_definition) {
      if (in_array($field_definition['machine_names'], $identifiers[$type])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Returnst which indexes are identifiers.
   */
  private function getIdentifiersIndex($fields) {
    $identifiers = $this->smartImporterService->getIdentifierFields();
    $identifier_index = ['product' => [], 'variation' => []];

    foreach ($fields['variation'] as $key => $field) {
      if (in_array($field['machine_names'], $identifiers['variation'])) {
        $identifier_index['variation'][] = $key;
      }
    }
    if (count($identifier_index['variation']) > 0) {
      $identifier_index['product'][] = 'variation';
    }
    foreach ($fields['product'] as $key => $field) {
      if (in_array($field['machine_names'], $identifiers['product'])) {
        $identifier_index['product'][] = $key;
      }
    }
    return $identifier_index;
  }

  /**
   * Checks if line is empty.
   */
  private function isEmptyLine($fields, $line) {
    foreach ($fields as $field) {
      if (!empty($line[$field['index']])) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Loads product.
   */
  private function loadProductByIndex($field_definitions, $identifier_index, $line, $variation) {
    if (empty($identifier_index)) {
      return FALSE;
    }
    $entity = FALSE;
    if (in_array('variation', $identifier_index) && $variation !== FALSE) {
      $entity = $variation->getProduct();
    }
    if (empty($entity)) {
      foreach ($identifier_index as $index) {
        if (is_numeric($index) && $field_definitions[$index]['machine_names'] == 'product_id') {
          if (empty($line[$field_definitions[$index]['index']])) {
            return FALSE;
          }
          $entity = Product::load($line[$field_definitions[$index]['index']]);
        }
      }
    }

    if (empty($entity)) {
      $this->messenger()->addStatus($this->t('Could not identify product with') . ' ' . $line[$field_definitions[$index]['index']]);
    }
    return !empty($entity) ? $entity : FALSE;
  }

  /**
   * Loads variation.
   */
  private function loadVariationByIndex($field_definitions, $identifier_index, $line) {
    if (empty($identifier_index)) {
      return FALSE;
    }
    $entity = FALSE;
    foreach ($identifier_index as $index) {
      if ($field_definitions[$index]['machine_names'] == 'sku') {
        $id = $this->smartImporterService->getVariationIdBySku(trim($line[$field_definitions[$index]['index']]));
        if ($id !== FALSE) {
          $entity = ProductVariation::load($id);
          if (!empty($entity)) {
            return $entity;
          }
        }
      }
      elseif ($field_definitions[$index]['machine_names'] == 'variation_id') {
        $entity = ProductVariation::load(trim($line[$field_definitions[$index]['index']]));
        if (!empty($entity)) {
          return $entity;
        }
      }
    }
    if (empty($entity)) {
      $this->messenger()->addStatus($this->t('Could not identify variation with') . ' ' . $line[$field_definitions[$index]['index']]);
    }
    return !empty($entity) ? $entity : FALSE;
  }

}
