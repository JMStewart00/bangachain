<?php

namespace Drupal\commerce_loyalty_points;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Loyalty points entities.
 */
class LoyaltyPointsListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Loyalty Points');
//    $header['store'] = $this->t('Store');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['lpid'] = $entity->label();
//    $row['reason'] = $entity->getStore()->getName();

    return $row + parent::buildRow($entity);
  }

}
