<?php

namespace Drupal\commerce_smart_importer\Form;

use Drupal\commerce_smart_importer\Plugin\CommerceSmartImporerService;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_store\Entity\Store;

/**
 * Introduction form.
 */
class CSVIntroductionForm extends FormBase {

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
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'CommerceCsvIntroduction';
  }

  /**
   * CSVIntroductionForm constructor.
   */
  public function __construct(Connection $connection,
                              CommerceSmartImporerService $service,
                              ModuleHandler $moduleHandler) {
    $this->database = $connection;
    $this->smartImporterService = $service;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Create.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('commerce_smart_importer.service'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /**
     * @todo Module will only work on PHP 7.0.
     * @todo Enable user to change csv delimiter.
     * @todo add save option.
     * @todo excel support
     * @todo Add support for files.
     * @todo Try formatting fields with field schema.
     */
    $sql = $this->database->select('commerce_store_field_data')
      ->fields('commerce_store_field_data', ['store_id'])
      ->execute()->fetchAll();

    if (count($sql) != 0) {
      global $base_url;

      if ($this->moduleHandler->moduleExists('pathauto')) {
        $this->messenger()->addStatus($this->t('Note that if you already have defined pattern in pathauto for product, it will override URL alias added by importer.'));
      }

      // TODO Rewrite instructions.
      $instructions = '<br><br><img width="100%" src="' . $base_url . '/' .
        drupal_get_path('module', 'commerce_smart_importer') .
        '/files/smart-importer-gudlines.png" id="tutorial">';

      /*$instructions .= '<div id="instructions">' . $this->t('Instructions') . '</div><br>';
      $instructions .= '<li class="li-element" id="product-header">' .
        $this->t('Part of header rounded up with') .
        ' <span class="red">' . $this->t('red') . '</span> ' .
        $this->t('are header fields for product. Product fields are always from Title column to SKU column') . '</li>';
      $instructions .= '<li class="li-element" id="variation-header">' .
        $this->t('Part of header rounded up with') .
        ' <span class="green">' . $this->t('green') . '</span>  ' .
        $this->t('are header fields for variation. Variation fields are always from SKU column to end') . '</li>';
      $instructions .= '<li class="li-element">' .
        $this->t('If you want one product to have more than one variation just leave product data(in that row) empty and that variation will be added to last product with filled data.') .
        $this->t('For instance in our case') . ' <span class="purple">' .
        $this->t('Playstation 4') . '</span> ' . $this->t('will have 3 variations') . '<span class="blue">' . $this->t('(Pro,Slim,Classic)') . '</span> ' . $this->t('like arrows are pointing') . '</li>';
      $instructions .= '<li class="li-element" id="multiple-values">' .
        $this->t('Multiple values: if you want multiple values in one filed just delimit them with |(pipe). Ex. XL|M will result in two values XL and M') . '</li>';*/

      $form['type'] = [
        '#type' => 'vertical_tabs',
        '#title' => $this->t('Type'),
      ];

      $form['import_type'] = [
        '#type' => 'details',
        '#title' => $this->t('CSV Import'),
        '#group' => 'type',
      ];

      $form['import_type']['csv_download'] = [
        '#type' => 'submit',
        '#value' => $this->t('Download CSV template'),
        '#submit' => [[$this, 'csvDownload']],
      ];
      $form['import_type']['csv_import'] = [
        '#type' => 'submit',
        '#value' => $this->t('I have CSV file for import'),
      ];
      $form['import_type']['instructions'] = [
        '#markup' => $instructions,
      ];
      $form['update_type'] = [
        '#type' => 'details',
        '#title' => $this->t('CSV Export/Update'),
        '#group' => 'type',
      ];
      $form['update_type']['csv_update_download'] = [
        '#type' => 'submit',
        '#value' => $this->t('Export products'),
      ];

      $form['update_type']['csv_update'] = [
        '#type' => 'submit',
        '#value' => $this->t('I have CSV file for update'),
      ];

      $form['#attached']['library'] = [
        'commerce_smart_importer/commerce-smart-importer-introduction-library',
      ];
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

    $triggering_element = $form_state->getTriggeringElement()['#value']->getUntranslatedString();

    if ($triggering_element == 'I have CSV file for import') {
      $http = ['action' => 'check'];
      $form_state->setRedirect('commerce_smart_importer.csv_importer', $http);
    }
    elseif ($triggering_element == 'I have CSV file for update') {
      $http = ['action' => 'check'];
      $form_state->setRedirect('commerce_smart_importer.csv_updater', $http);
    }
    elseif ($triggering_element == 'Export products') {
      $form_state->setRedirect('commerce_smart_importer.exporter');
    }
    else {
      $form_state->setRedirect('commerce_smart_importer.configure_smart_importer');
    }
  }

  /**
   * Downloads importer template.
   */
  public function csvDownload() {

    $fields = $this->smartImporterService->getFieldDefinition();
    $ordered = [];
    foreach ($fields['product'] as $product_field) {
      $ordered[] = $product_field['label'];
    }
    $leading_header = $this->reorderBasedOnPrefered($ordered);
    $ordered = [];
    foreach ($fields['variation'] as $variation_field) {
      $ordered[] = $variation_field['label'];
    }
    $leading_header = array_merge($leading_header, $this->reorderBasedOnPrefered($ordered));

    $config = $this->smartImporterService->getConfig();

    if ($config['store'] != 'all') {
      $store = Store::load($config['store']);
      $name = $store->getName();
    } else {
      $name = $this->config('system.site')->get('name');
    }

    $path = "public://csv-" . str_replace(' ', '-', $name) . ".csv";
    $file = fopen($path, 'w');

    fputcsv($file, $leading_header);
    fputcsv($file, ['Your product data starts in next row']);
    fclose($file);

    $csv_file = file_get_contents($path);
    header('Content-Description: File Transfer');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Type: application/csv');
    header("Content-length: " . filesize($path));
    header('Content-Disposition: attachment; filename="' . basename($path) . '"');

    if (file_exists($path)) {
      unlink($path);
    }

    echo $csv_file;
    exit();
  }

  /**
   * Reorders labels.
   */
  private function reorderBasedOnPrefered($labels) {
    $identifier_fields = ['SKU', 'ID(product)', 'ID(variation)'];
    $prefered_fields = [
      'Title',
      'Body',
      'Price',
      'Sale price',
      'Currency',
      'Image',
      'Na stanju',
    ];
    $identifiers = [];
    $prefered = [];
    $others = [];
    foreach ($labels as $label) {
      if (in_array($label, $identifier_fields)) {
        $identifiers[] = $label;
      }
      elseif (in_array($label, $prefered_fields)) {
        $prefered[] = $label;
      }
      else {
        $others[] = $label;
      }
    }
    return array_merge($identifiers, $prefered, $others);
  }

}
