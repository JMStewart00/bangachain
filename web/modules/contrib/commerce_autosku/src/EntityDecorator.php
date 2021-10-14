<?php

/**
 * @file
 * Contains \Drupal\commerce_autosku\EntityDecorator.
 */

namespace Drupal\commerce_autosku;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides an content entity decorator for automatic label generation.
 */
class EntityDecorator implements EntityDecoratorInterface {

  /**
   * The content entity that is decorated.
   *
   * @var ContentEntityInterface
   */
  protected $entity;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The generator manager.
   *
   * @var \Drupal\commerce_autosku\CommerceAutoSkuGeneratorManagerInterface
   */
  protected $generatorManager;

  /**
   * Constructs an EntityDecorator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager
   * @param \Drupal\commerce_autosku\CommerceAutoSkuGeneratorManagerInterface $generator_manager
   *   The generator manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CommerceAutoSkuGeneratorManagerInterface $generator_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->generatorManager = $generator_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function decorate(ContentEntityInterface $entity) {
    $this->entity = new CommerceAutoSkuManager($entity, $this->entityTypeManager, $this->generatorManager);
    return $this->entity;
  }

}
