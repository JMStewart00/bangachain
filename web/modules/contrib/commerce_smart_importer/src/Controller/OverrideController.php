<?php

namespace Drupal\commerce_smart_importer\Controller;

use Drupal\commerce_smart_importer\ImportingParameters;
use Drupal\commerce_smart_importer\Plugin\CommerceSmartImporerService;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\commerce_smart_importer\CommerceSmartImporterConstants;

/**
 * Controller used to override values in import.
 */
class OverrideController extends ControllerBase {

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Smart importer general service.
   *
   * @var \Drupal\commerce_smart_importer\Plugin\CommerceSmartImporerService
   */
  protected $importerService;

  /**
   * OverrideController constructor.
   */
  public function __construct(AccountProxy $user, CommerceSmartImporerService $service) {
    $this->currentUser = $user;
    $this->importerService = $service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('current_user'),
      $container->get('commerce_smart_importer.service')
    );
  }

  /**
   * Main function for overriding.
   */
  public function overrideValue(Request $request) {

    $import = $request->request->get('import_name');
    $row = $request->request->get('row');
    $index = $request->request->get('index');
    $value = $request->request->get('value');
    $entity_type = $request->request->get('field_type');
    $dir = $this->importFolder($import);

    if ($dir === FALSE) {
      return new Response('No import under this name');
    }

    $field_definitions = $this->importerService->getFieldDefinition();
    $field_definition = $field_definitions[$entity_type][$index];
    $values = explode('|', $value);
    $parameters = new ImportingParameters();
    $parameters->incorrectValues = FALSE;
    $parameters->defaultValues = FALSE;
    $parameters->exceedsCardinality = FALSE;
    $parameters->duplicateValues = FALSE;
    $field_log = $this->importerService->formatMultipleFieldValues($values, $field_definition, $parameters, []);
    $this->importerService->duplicateValuesPass($field_log);
    $this->importerService->cardinalityPass($field_log, $field_definition);
    $this->importerService->useDefaultValuePass($field_log, $field_definition);
    $this->importerService->requiredPass($field_log, $field_definition);
    $accepted = $parameters->matchOneFieldLog($field_log);
    if ($accepted) {
      $this->putOverrideValue($row, $value, $field_definition, $entity_type, $dir);
      return new Response('Successfully overridden value');
    }
    else {
      return new Response('Override was unsuccessful');
    }

  }

  /**
   * Finds import folder.
   */
  private function importFolder($import_name) {
    $dir = scandir(CommerceSmartImporterConstants::TEMP_DIR);
    foreach ($dir as $imports) {
      if (is_dir(CommerceSmartImporterConstants::TEMP_DIR . '/' . $imports)) {
        if ($imports == $import_name) {
          return CommerceSmartImporterConstants::TEMP_DIR . '/' . $imports;
        }
      }
    }
    return FALSE;
  }

  /**
   * Puts override value in save folder.
   */
  private function putOverrideValue($row, $value, $field_definition, $type, $save_folder) {
    if (!is_file($save_folder . '/override_values.json')) {
      touch($save_folder . '/override_values.json');
      $json = [];
    }
    else {
      $json = json_decode(file_get_contents($save_folder . '/override_values.json'), TRUE);
    }
    $json[$row][$type][$field_definition['machine_names']] = $value;
    file_put_contents($save_folder . '/override_values.json', json_encode($json, JSON_UNESCAPED_UNICODE));
  }

}
