<?php

declare(strict_types = 1);

namespace Drupal\bangachain_layout\Helper;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\media\MediaInterface;

/**
 * Provides a set of helper traits for blocks that reference media.
 */
trait MediaHelperTrait {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Build the media entity.
   *
   * @param int|null $entity_id
   *   The media entity id.
   *
   * @return array
   *   A renderable media entity.
   */
  protected function buildMediaEntity(?int $entity_id): array {
    $media = $this->loadMedia($entity_id);

    if ($media instanceof MediaInterface) {
      return $this->getEntityTypeManager()
        ->getViewBuilder('media')
        ->view($media, 'full');
    }

    return [];
  }

  /**
   * Get the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  protected function getEntityTypeManager(): EntityTypeManagerInterface {
    if (!$this->entityTypeManager) {
      $this->entityTypeManager = \Drupal::entityTypeManager();
    }

    return $this->entityTypeManager;
  }

  /**
   * Load a media entity by its id.
   *
   * @param int|null $entity_id
   *   The media entity id.
   *
   * @return \Drupal\media\MediaInterface|null
   *   The media entity or NULL if it does not exist.
   */
  protected function loadMedia(?int $entity_id): ?MediaInterface {
    return $this->getEntityTypeManager()
      ->getStorage('media')
      ->load($entity_id);
  }

}
