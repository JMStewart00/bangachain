<?php

namespace Drupal\commerce_smart_importer\Form;

use Drupal\Core\File\FileSystem;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_smart_importer\Plugin\CommerceSmartImporerService;
use Drupal\Core\Extension\ModuleHandler;

/**
 * Smart importer config form.
 */
class SmartImporterConfigurationForm extends ConfigFormBase {

  /**
   * Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $smartImporterService;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * File system.
   *
   * @var FileSystem
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_smart_importer_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['commerce_smart_importer.settings'];
  }

  /**
   * SmartImporterConfigurationForm constructor.
   */
  public function __construct(Connection $connection,
                              CommerceSmartImporerService $service,
                              ModuleHandler $moduleHandler,
                              FileSystem $fileSystem) {
    $this->database = $connection;
    $this->smartImporterService = $service;
    $this->moduleHandler = $moduleHandler;
    $this->fileSystem = $fileSystem;
  }

  /**
   * Create.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('commerce_smart_importer.service'),
      $container->get('module_handler'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('commerce_smart_importer.settings');
    $importer_config = $this->smartImporterService->getConfig();
    $sql = $this->database->query("SELECT store_id, name FROM commerce_store_field_data")->fetchAll();

    $options['all'] = 'All';
    foreach ($sql as $stores) {
      $options[$stores->store_id] = $stores->name;
    }
    if (count($sql) != 0) {

      if ($config->get('store') == NULL) {
        $default_value = 1;
      }
      else {
        $default_value = $config->get('store');
      }

      $form['store'] = [
        '#type' => 'select',
        '#title' => $this->t('Choose your store'),
        '#options' => $options,
        '#default_value' => $default_value,
      ];

      $form['expose_store'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Expose store in CSV template'),
        '#default_value' => $config->get('expose_store'),
      ];

      $bundles = $this->smartImporterService->getEntityBundles('commerce_product');
      $form['commerce_product_bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Choose product bundle'),
        '#options' => $bundles,
        '#default_value' => $importer_config['commerce_product_bundle'],
      ];

      $bundles = $this->smartImporterService->getEntityBundles('commerce_product_variation');
      $form['commerce_product_variation_bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Choose product variation bundle'),
        '#options' => $bundles,
        '#default_value' => $importer_config['commerce_product_variation_bundle'],
      ];

      if ($config->get('batch_products') == NULL) {
        $default_value = 50;
      }
      else {
        $default_value = $config->get('batch_products');
      }

      $form['batch'] = [
        '#type' => 'number',
        '#title' => $this->t('Choose how many products you want to import per one batch operation'),
        '#description' => $this->t('Higher this number is faster import will be, but it is more likely that timeout error will occur'),
        '#default_value' => $default_value,
      ];

      $form['sku_container'] = [
        '#type' => 'fieldset',
        '#title' => $this->t("SKU generate"),
        '#description' => $this->t('SKU will only be generated if field is left empty'),
      ];

      if ($config->get('sku_prefix') == NULL) {
        $default_value = 'si_';
      }
      else {
        $default_value = $config->get('sku_prefix');
      }

      $form['sku_container']['sku_prefix'] = [
        '#prefix' => '<div class="container-inline">',
        '#suffix' => '</div>',
        '#type' => 'textfield',
        '#title' => $this->t('SKU prefix'),
        '#size' => 5,
        '#default_value' => $default_value,
      ];

      if ($config->get('sku_method') == NULL) {
        $default_value = 1;
      }
      else {
        $default_value = $config->get('sku_method');
      }

      $form['sku_container']['sku_method'] = [
        '#prefix' => '<div class="container-inline">',
        '#suffix' => '</div>',
        '#type' => 'radios',
        '#title' => $this->t('Choose generating method'),
        '#options' => [
          0 => $this->t('Auto increment'),
          1 => $this->t('Random digits'),
        ],
        '#default_value' => $default_value,
      ];

      if ($config->get('sku_random_digits') == NULL) {
        $default_value = 6;
      }
      else {
        $default_value = $config->get('sku_random_digits');
      }
      $form['sku_container']['sku_digits'] = [
        '#prefix' => '<div class="container-inline">',
        '#suffix' => '</div>',
        '#type' => 'textfield',
        '#title' => $this->t('Number of random digits'),
        '#size' => 4,
        '#description' => $this->t('(default: 6, must be between 3 and 20)'),
        '#states' => [
          'invisible' => [
            ':input[name="sku_method"]' => ['value' => 0],
          ],
        ],
        '#default_value' => $default_value,
      ];
      $form['external_folders'] = [
        '#type' => 'textfield',
        '#title' => $this->t('External folders'),
        '#default_value' => implode(',', $config->get('external_folders')),
        '#description' => $this->t('Define external folders in Public file system folder, that will be scanned for images and files. Delimit with comma.'),
        '#suffix' => $this->t('Your current  Public file system folder is ') . str_replace($_SERVER['DOCUMENT_ROOT'] . '/','', $this->fileSystem->realpath('public://')),
      ];

      $form['flush_image_cache'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Flush image styles when adding new image'),
      ];

    }
    else {
      $this->messenger()->addStatus($this->t("In order to use this module you'll have to create at least one store"));
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();
    $config = $this->config('commerce_smart_importer.settings');

    if ($values['store'] != '') {
      $config->set('store', $values['store']);
    }
    elseif ($config->get('store') == NULL) {
      $config->set('store', 1);
    }

    if ($values['sku_prefix'] != '') {
      $config->set('sku_prefix', $values['sku_prefix']);
    }
    elseif ($config->get('sku_prefix') == NULL) {
      $config->set('sku_prefix', 'si_');
    }

    if ($values['sku_method'] != '') {
      $config->set('sku_method', $values['sku_method']);
    }
    elseif ($config->get('sku_method') == NULL) {
      $config->set('sku_method', 1);
    }

    if ($values['sku_digits'] != '' && $values['sku_digits'] > 2 && $values['sku_digits'] < 20 && is_numeric($values['sku_digits'])) {
      $config->set('sku_random_digits', floor($values['sku_digits']));
    }
    elseif ($values['sku_digits'] > 2 && $values['sku_digits'] <= 20 && is_numeric($values['sku_digits'])) {
      if ($config->get('sku_random_digits') == NULL) {
        $config->set('sku_random_digits', 6);
      }
    }
    elseif ($config->get('sku_random_digits') == NULL) {
      $config->set('sku_random_digits', 6);
    }
    if (!empty($values['batch'])) {
      $config->set('batch_products', $values['batch']);
    }
    elseif ($config->get('batch_products') == NULL) {
      $config->set('batch_products', 50);
    }
    if (!empty($values['commerce_product_variation_bundle'])) {
      $config->set('commerce_product_variation_bundle', $values['commerce_product_variation_bundle']);
    }
    elseif ($config->get('commerce_product_variation_bundle') == NULL) {
      $config->set('commerce_product_variation_bundle', 'default');
    }
    if (!empty($values['commerce_product_bundle'])) {
      $config->set('commerce_product_bundle', $values['commerce_product_bundle']);
    }
    elseif ($config->get('commerce_product_bundle') == NULL) {
      $config->set('commerce_product_bundle', 'default');
    }
    if (!empty($values['external_folders'])) {
      $config->set('external_folders', explode(',', $values['external_folders']));
    }
    elseif ($config->get('external_folders') == NULL) {
      $config->set('external_folders', []);
    }
    if (array_key_exists('flush_image_cache', $values)) {
      $config->set('flush_image_cache', $values['flush_image_cache']);
    }
    $config->set('expose_store', $values['expose_store']);
    $config->save();
  }

}
