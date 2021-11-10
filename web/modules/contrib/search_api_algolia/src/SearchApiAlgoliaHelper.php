<?php

namespace Drupal\search_api_algolia;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\search_api\datasource\ContentEntity;
use Drupal\Core\Language\LanguageManager;
use Drupal\search_api\Utility\Utility;

/**
 * Class Search Api Algolia Helper.
 */
class SearchApiAlgoliaHelper {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection object.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager,
                              LanguageManager $language_manager,
                              Connection $connection) {
    $this->entityTypeManager = $entityTypeManager;
    $this->languageManager = $language_manager;
    $this->connection = $connection;
  }

  /**
   * Implements hook_entity_delete().
   *
   * Deletes all entries for this entity from the tracking table for each index
   * that tracks this entity type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The deleted entity.
   *
   * @see search_api_algolia_entity_delete()
   */
  public function entityDelete(EntityInterface $entity) {
    // Check if the entity is a content entity.
    if (!($entity instanceof ContentEntityInterface) || $entity->search_api_skip_tracking) {
      return;
    }

    $indexes = ContentEntity::getIndexesForEntity($entity);
    if (!$indexes) {
      return;
    }

    // Remove the search items for all the entity's translations.
    foreach ($indexes as $index) {
      $object_id_field = $index->getOption('object_id_field');
      if ($object_id_field) {
        $object_id = $entity->get($object_id_field)->getString();

        if ($object_id) {
          // @todo make this work with indexes having language suffix.
          $this->scheduleForDeletion($index, [$object_id]);
        }
      }
    }
  }

  /**
   * Store deleted items in search_api_algolia_deleted_items table.
   *
   * This items will be deleted via drush command sapia-d.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   Index.
   * @param array $item_ids
   *   Items to delete.
   * @param string|null $language
   *   Language code if multi-lingual.
   */
  public function scheduleForDeletion(IndexInterface $index, array $item_ids, $language = NULL) {
    if ($index->get('read_only')) {
      return;
    }
    foreach ($item_ids as $objectid) {
      $indexId = $index->getOption('algolia_index_name');
      if ($index->getOption('algolia_index_apply_suffix') && !empty($language)) {
        $indexId .= '_' . $language;
      }

      $this->connection->insert('search_api_algolia_deleted_items')
        ->fields(['index_id', 'object_id'])
        ->values([
          'index_id' => $indexId,
          'object_id' => $objectid,
        ])
        ->execute();
    }
  }

}
