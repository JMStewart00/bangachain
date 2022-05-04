<?php

namespace Drupal\integer_to_decimal\Service;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use \Drupal\Core\Entity\EntityTypeManagerInterface;
use \Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * FieldUpdaterService manages the conversion of one field type to another.
 *
 *
 * @package Drupal\field_updater\Service
 */
class FieldUpdaterService implements FieldUpdaterServiceInterface {
  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;
  /**
   * @var \Drupal\Core\Messenger\MessengerInterface $messenger
   */
  protected $messenger;

  /**
   * FieldUpdaterService constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   */
  public function __construct(Connection $connection, EntityTypeManagerInterface $entityTypeManager,
                              ConfigFactoryInterface $configFactory, MessengerInterface $messenger) {
    $this->connection = $connection;
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   *
   */
  public function fieldUpdater($field, $type, $entity_type, $bundles, $precision, $scale) {
    $database = $this->connection;
    $existingData = [];
    $tables = [];

    $entity_type_manager = \Drupal::getContainer()->get('entity_type.manager');
    // Get an instance of Drupal\Core\Entity\Sql\DefaultTableMapping class
    $table_mapping = $entity_type_manager->getStorage('commerce_product')->getTableMapping();
    $storage_definitions = $entity_type_manager->getFieldStorageDefinitions($entity_type);
    $data_table = $table_mapping->getDedicatedDataTableName($storage_definitions[$field]);
    if ($data_table) {
      $tables[] = $data_table;
    }
    $revision_table = $table_mapping->getDedicatedRevisionTableName($storage_definitions[$field]);
    if ($revision_table) {
      $tables[] = $revision_table;
    }

    if (empty($tables)) {
      return;
    }

    $settings = [
      'precision' => $precision,
      'scale' => $scale,
    ];

    foreach ($tables as $table) {
      $existingData[$table] = $database->select($table)
        ->fields($table)
        ->execute()
        ->fetchAll(\PDO::FETCH_ASSOC);

      $database->truncate($table)->execute();
    }
    $config = $this->configFactory->getEditable('field.storage.' . $entity_type . '.' . $field);
    $config->set('settings', $settings)
      ->set('type', 'decimal')->save();

    $fieldStorage = FieldStorageConfig::loadByName($entity_type, $field);
    $fieldStorage->set('settings', $settings)
      ->set('type', 'decimal');
    $fieldStorage->save();

    foreach ($bundles as $bundle) {
      $fieldConfig = FieldConfig::loadByName($entity_type, $bundle, $field);
      $fieldConfig->set('field_type', 'decimal')->save();
      $this->entityTypeManager->clearCachedDefinitions();
    }

    // Restore the data.
    foreach ($tables as $table) {
      $insert_query = $database
        ->insert($table)
        ->fields(array_keys(end($existingData[$table])));
      foreach ($existingData[$table] as $row) {
        $insert_query->values(array_values($row));
      }
      $insert_query->execute();
    }

    foreach ($bundles as $bundle) {
      $this->entityTypeManager->getStorage('entity_form_display')
        ->load($entity_type . '.' . $bundle . '.' . 'default')
        ->setComponent($field, ['region' => 'content'])->save();
      $this->entityTypeManager->getStorage('entity_view_display')
        ->load($entity_type . '.' . $bundle . '.' . 'default')
        ->setComponent($field, ['region' => 'content'])->save();

      $this->entityTypeManager->clearCachedDefinitions();
      $this->messenger->addMessage($this->t('Successfully converted from integer to decimal.'));
    }
  }
}
