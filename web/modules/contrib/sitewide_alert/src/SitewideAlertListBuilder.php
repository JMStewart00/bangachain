<?php

namespace Drupal\sitewide_alert;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Sitewide Alert entities.
 *
 * @ingroup sitewide_alert
 */
class SitewideAlertListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['style'] = $this->t('Style');
    $header['active'] = $this->t('Active');
    $header['scheduled'] = $this->t('Scheduled');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\sitewide_alert\Entity\SitewideAlert $entity */
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.sitewide_alert.edit_form',
      ['sitewide_alert' => $entity->id()]
    );
    $row['style'] = AlertStyleProvider::alertStyleName($entity->getStyle());
    $row['active'] = $entity->isPublished() ? '✔' : '✘';
    $row['scheduled'] = $entity->isScheduled() ? '✔' : '✘';
    return $row + parent::buildRow($entity);
  }

}
