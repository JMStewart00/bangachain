<?php

namespace Drupal\sitewide_alert;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\sitewide_alert\Entity\SitewideAlertInterface;

/**
 * Defines the storage handler class for Sitewide Alert entities.
 *
 * This extends the base storage class, adding required special handling for
 * Sitewide Alert entities.
 *
 * @ingroup sitewide_alert
 */
class SitewideAlertStorage extends SqlContentEntityStorage implements SitewideAlertStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(SitewideAlertInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {sitewide_alert_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {sitewide_alert_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(SitewideAlertInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {sitewide_alert_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('sitewide_alert_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
