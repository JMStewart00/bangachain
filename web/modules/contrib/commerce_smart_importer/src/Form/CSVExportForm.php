<?php

namespace Drupal\commerce_smart_importer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_smart_importer\Plugin\CommerceSmartImporerService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\commerce_store\Entity\Store;

/**
 * Form for exporting products in CSV format.
 *
 * Later can be used in update.
 */
class CSVExportForm extends FormBase {

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
   * Returns form id.
   */
  public function getFormId() {
    return 'csvExportFormSmartImporter';
  }

  /**
   * CSVExportForm constructor.
   */
  public function __construct(Connection $connection,
                              CommerceSmartImporerService $service,
                              ConfigFactory $configFactory,
                              EntityTypeManagerInterface $entityTypeManager) {
    $this->database = $connection;
    $this->smartImporterService = $service;
    $this->configFactory = $configFactory;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Create.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('commerce_smart_importer.service'),
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Builds form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options['all'] = 'All';
    $config = $this->smartImporterService->getConfig();
    $taxonomies = $this->smartImporterService->getReferencedTaxonomyTerms('product', $config['commerce_product_bundle']);

    foreach ($taxonomies as $taxonomy) {
      $options[$taxonomy['machine_name']] = $taxonomy['name'];
    }

    $form['export_by'] = [
      '#type' => 'select',
      '#options' => $options,
    ];

    foreach ($options as $exportBy => $option) {
      $options2 = [];

      $options2['all'] = 'All';
      if ($exportBy != 'all') {
        foreach ($taxonomies as $taxonomy) {
          if ($taxonomy['machine_name'] == $exportBy) {
            foreach ($taxonomy['target_bundles'] as $bundle) {
              $terms = $this->entityTypeManager
                ->getStorage('taxonomy_term')
                ->loadTree($bundle);
              foreach ($terms as $term) {
                $options2[$term->tid] = $term->name;
              }
            }
          }
        }
      }

      $form['export_tax_' . $exportBy] = [
        '#type' => 'select',
        '#options' => $options2,
        '#states' => [
          'visible' => [
            ':input[name="export_by"]' => ['value' => $exportBy],
          ],
        ],
      ];
    }

    $fields = $this->smartImporterService->getFieldDefinition(TRUE);
    $identifier_fields = $this->smartImporterService->getIdentifierFields();
    $product_identifiers = [];
    $product_fields = [];
    foreach ($fields['product'] as $field) {
      if (in_array($field['machine_names'], $identifier_fields['product'])) {
        $product_identifiers[$field['machine_names']] = ['machine_name' => $field['machine_names'], 'field_name' => $field['label']];
      }
      else {
        $product_fields[$field['machine_names']] = ['machine_name' => $field['machine_names'], 'field_name' => $field['label']];
      }
    }
    $variation_identifiers = [];
    $variation_fields = [];
    foreach ($fields['variation'] as $field) {
      if (in_array($field['machine_names'], $identifier_fields['variation'])) {
        $variation_identifiers[$field['machine_names']] = ['machine_name' => $field['machine_names'], 'field_name' => $field['label']];
      }
      else {
        $variation_fields[$field['machine_names']] = ['machine_name' => $field['machine_names'], 'field_name' => $field['label']];
      }
    }
    $headers = ['field_name' => $this->t('Field name'), 'machine_name' => $this->t('Machine name')];
    $form['product'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Product Fields'),
    ];
    $form['product']['product_identifiers_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Product identifier Fields'),
    ];
    $form['product']['product_identifiers_fieldset']['product_identifiers'] = [
      '#type' => 'tableselect',
      '#header' => $headers,
      '#options' => $product_identifiers,
    ];

    $form['product']['product_fields_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Product identifier Fields'),
    ];

    $form['product']['product_fields_fieldset']['product_fields'] = [
      '#type' => 'tableselect',
      '#header' => $headers,
      '#options' => $product_fields,
    ];

    $form['variation'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Variation Fields'),
    ];
    $form['variation']['variation_identifiers_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Variation identifier Fields'),
    ];
    $form['variation']['variation_identifiers_fieldset']['variation_identifiers'] = [
      '#type' => 'tableselect',
      '#header' => $headers,
      '#options' => $variation_identifiers,
    ];

    $form['variation']['variation_fields_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Variation identifier Fields'),
    ];

    $form['variation']['variation_fields_fieldset']['variation_fields'] = [
      '#type' => 'tableselect',
      '#header' => $headers,
      '#options' => $variation_fields,
    ];

    $form['export'] = [
      '#value' => 'Export',
      '#type' => 'submit',
    ];

    $form['download_export'] = [
      '#value' => 'Download last export',
      '#type' => 'submit',
      '#submit' => [[$this, 'downloadCsv']],
    ];

    $form['#attached']['library'] = [
      'commerce_smart_importer/commerce-smart-importer-importer-load-library',
    ];

    if (isset($_GET['download'])) {
      $this->downloadCsv();
    }

    return $form;
  }

  /**
   * Validates form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if ($this->isFieldChecked($values['variation_fields'])) {
      if (!$this->isFieldChecked($values['variation_identifiers'])) {
        $form_state->setErrorByName('variation_identifiers', $this->t('If you want to export variation, you need at least one identifier.'));
      }
    }

    if ($this->isFieldChecked($values['product_fields'])) {
      if (!$this->isFieldChecked($values['product_identifiers'])) {
        $form_state->setErrorByName('product_identifiers', $this->t('If you want to export product, you need at least one identifier.'));
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * Submits form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->smartImporterService->getConfig();
    $export_fields_names = [];
    foreach ($values['product_fields'] as $field) {
      if (!empty($field)) {
        $export_fields_names[] = $field;
      }
    }
    foreach ($values['product_identifiers'] as $field) {
      if (!empty($field)) {
        $export_fields_names[] = $field;
      }
    }
    foreach ($values['variation_identifiers'] as $field) {
      if (!empty($field)) {
        $export_fields_names[] = $field;
      }
    }
    foreach ($values['variation_fields'] as $field) {
      if (!empty($field)) {
        $export_fields_names[] = $field;
      }
    }
    $fields = $this->smartImporterService->getFieldDefinition(TRUE);

    $this->leaveOnlyCheckedFields($fields, $export_fields_names);
    if ($values['export_by'] != 'all' && $values['export_tax_' . $values['export_by']] != 'all') {
      $query = $this->database->query("SELECT cp.product_id FROM commerce_product cp JOIN commerce_product__" . $values['export_by'] . " tax ON tax.entity_id=cp.product_id WHERE tax." . $values['export_by'] . "_target_id=" . $values['export_tax_' . $values['export_by']])->fetchAll();
      $field_value = [$values['export_by'], $values['export_tax_' . $values['export_by']]];
    }
    else {
      $query = $this->database->select('commerce_product')
        ->fields('commerce_product', ['product_id'])
        ->execute()->fetchAll();
      $field_value = [];
    }

    $product_number = count($query);
    $per_batch = 5;
    $number_of_batches = ceil($product_number / $per_batch);

    $batch = [
      'title' => $this->t('Exporting all products'),
      'init_message' => $this->t('Beginning...'),
      'progress_message' => $this->t('exported @current out of @total product groups'),
      'error_message' => $this->t('Something went wrong'),
      'progressive' => FALSE,
      'operations' => [],
    ];

    if ($config['store'] != 'all') {
      $store = Store::load($config['store']);
      $name = $store->getName();
    } else {
      $name = $this->config('system.site')->get('name');
    }

    $file = fopen('temporary://export-' . str_replace(' ', '-', $name) . '.csv', 'w');
    $header = [];
    foreach ($fields['product'] as $product_header) {
      $header[] = $product_header['label'];
    }
    foreach ($fields['variation'] as $variation_header) {
      $header[] = $variation_header['label'];
    }
    fputcsv($file, $header);
    fputcsv($file, ['']);
    fclose($file);
    for ($i = 0; $i < $number_of_batches; $i++) {
      $batch['operations'][] = [
        [
          $this,
          'putExportedProductsInCsv',
        ],
        [
          $i * $per_batch,
          $per_batch,
          $fields,
          $field_value,
        ],
      ];
    }

    batch_set($batch);
  }

  /**
   * Writes formated product to CSV file.
   */
  public function putExportedProductsInCsv($start, $limit, $field_definitions, array $field_value) {
    $config = $this->smartImporterService->getConfig();
    if ($config['store'] != 'all') {
      $store = Store::load($config['store']);
      $name = $store->getName();
    } else {
      $name = $this->config('system.site')->get('name');
    }
    $file = fopen('temporary://export-' . str_replace(' ', '-', $name) . '.csv', 'a');
    $rows = $this->exportProducts($start, $limit, $field_definitions, $field_value);
    foreach ($rows as $row) {
      fputcsv($file, $row);
    }
    fclose($file);
  }

  /**
   * Export whole product to CSV fromatted.
   */
  public function exportProducts($start, $limit, $field_definitions, array $field_value) {

    $product_data = [];
    if (empty($field_value)) {
      $query = $this->database->select('commerce_product')
        ->fields('commerce_product', ['product_id'])
        ->range($start, $limit)->execute()->fetchAll();
    }
    else {
      $query = $this->database->query("SELECT cp.product_id FROM commerce_product cp JOIN commerce_product__" . $field_value[0] . " tax ON tax.entity_id=cp.product_id WHERE tax." . $field_value[0] . "_target_id=" . $field_value[1])->fetchAll();
    }

    foreach ($query as $product) {
      $product_temp_data = [];
      $product_temp_data['product'] = $product->product_id;

      $variation_query = $this->database->query('SELECT variation_id FROM commerce_product_variation_field_data WHERE product_id=' . $product->product_id)
        ->fetchAll();
      foreach ($variation_query as $variation) {
        $product_temp_data['variation'][] = $variation->variation_id;
      }
      $product_data[] = $product_temp_data;
    }

    $empty_product = array_fill(0, count($field_definitions['product']), '');
    $rows = [];
    foreach ($product_data as $product_datum) {
      $first = TRUE;
      foreach ($product_datum['variation'] as $variation_id) {
        if ($first) {
          $temp_row = $this->smartImporterService->exportMultipleFields('commerce_product', $product_datum['product'], $field_definitions['product']);
          $first = FALSE;
        }
        else {
          $temp_row = $empty_product;
        }
        $temp_row = array_merge($temp_row, $this->smartImporterService->exportMultipleFields('commerce_product_variation', $variation_id, $field_definitions['variation']));
        $rows[] = $temp_row;
      }
    }
    return $rows;
  }

  /**
   * Checks if field is checked.
   */
  private function isFieldChecked(array $fields) {
    foreach ($fields as $field) {
      if (!empty($field)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Unsets fields that are not checked.
   */
  private function leaveOnlyCheckedFields(&$fields, $field_list) {
    foreach ($fields['product'] as $key => $field) {
      if (!in_array($field['machine_names'], $field_list)) {
        unset($fields['product'][$key]);
      }
    }
    foreach ($fields['variation'] as $key => $field) {
      if (!in_array($field['machine_names'], $field_list)) {
        unset($fields['variation'][$key]);
      }
    }
  }

  /**
   * Downloads CSV.
   */
  public function downloadCsv() {
    $config = $this->smartImporterService->getConfig();
    if ($config['store'] != 'all') {
      $store = Store::load($config['store']);
      $name = $store->getName();
    } else {
      $name = $this->config('system.site')->get('name');
    }
    $filename = 'temporary://export-' . str_replace(' ', '-', $name) . '.csv';
    if (is_file($filename)) {
      $csv_file = file_get_contents($filename);
      header('Content-Description: File Transfer');
      header('Expires: 0');
      header('Cache-Control: must-revalidate');
      header('Pragma: public');
      header('Content-Type: application/csv');
      header("Content-length: " . filesize($filename));
      header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
      echo $csv_file;
      exit();
    }
    else {
      $this->messenger()->addStatus($this->t('Export first'));
    }
  }

}
