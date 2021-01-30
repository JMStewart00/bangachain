<?php

namespace Drupal\commerce_pos;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Prevents uninstall if there are orders depending on registers.
 */
class CommercePosUninstallValidator implements ModuleUninstallValidatorInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CommercePosUninstallValidator.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(TranslationInterface $string_translation, EntityTypeManagerInterface $entity_type_manager) {
    $this->stringTranslation = $string_translation;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module) {
    $reasons = [];
    if ($module == 'commerce_pos' && $this->posOrdersExist()) {
      $reasons[] = $this->t('To uninstall Commerce POS delete all Point of Sale orders.');
    }
    return $reasons;
  }

  /**
   * Checks if there are any orders of type "pos".
   *
   * @return bool
   *   TRUE if there are pos type orders, FALSE if not.
   */
  protected function posOrdersExist() {
    $order_ids = $this->entityTypeManager->getStorage('commerce_order')->getQuery()
      ->condition('type', 'pos')
      ->range(0, 1)
      ->execute();
    return !empty($order_ids);
  }

}
