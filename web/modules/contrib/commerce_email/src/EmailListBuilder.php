<?php

namespace Drupal\commerce_email;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines the list builder for emails.
 */
class EmailListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Email');
    $header['event'] = $this->t('Event');
    $header['status'] = $this->t('Status');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\commerce_email\Entity\EmailInterface $entity */
    $row['label'] = $entity->label();
    $row['event'] = $entity->getEvent()->getLabel();
    $row['status'] = $entity->status() ? $this->t('Enabled') : $this->t('Disabled');

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $operations = parent::getDefaultOperations($entity);

    if ($this->entityType->hasKey('status')) {
      if (!$entity->status() && $entity->hasLinkTemplate('enable')) {
        $operations['enable'] = [
          'title' => t('Enable'),
          'weight' => -10,
          'url' => $this->ensureDestination($entity->toUrl('enable')),
        ];
      }
      elseif ($entity->hasLinkTemplate('disable')) {
        $operations['disable'] = [
          'title' => t('Disable'),
          'weight' => 40,
          'url' => $this->ensureDestination($entity->toUrl('disable')),
        ];
      }
    }

    $operations['test'] = [
      'title' => t('Test email'),
      'weight' => 50,
      'url' => $this->ensureDestination(Url::fromRoute('entity.commerce_email.test_form', ['commerce_email' => $entity->id()])),
    ];

    return $operations;
  }

}
