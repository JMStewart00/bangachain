<?php

namespace Drupal\commerce_promotion;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Applies promotions to orders during the order refresh process.
 */
class PromotionOrderProcessor implements OrderProcessorInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new PromotionOrderProcessor object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    // Remove coupons that are no longer valid (due to availability/conditions.)
    $coupons_field_list = $order->get('coupons');
    $constraints = $coupons_field_list->validate();
    /** @var \Symfony\Component\Validator\ConstraintViolationInterface $constraint */
    foreach ($constraints as $constraint) {
      list($delta, $property_name) = explode('.', $constraint->getPropertyPath());
      $coupons_field_list->removeItem($delta);
    }

    $content_langcode = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    /** @var \Drupal\commerce_promotion\Entity\CouponInterface[] $coupons */
    $coupons = $order->get('coupons')->referencedEntities();
    foreach ($coupons as $index => $coupon) {
      $promotion = $coupon->getPromotion();
      $promotion->apply($order);
    }

    // Non-coupon promotions are loaded and applied separately.
    /** @var \Drupal\commerce_promotion\PromotionStorageInterface $promotion_storage */
    $promotion_storage = $this->entityTypeManager->getStorage('commerce_promotion');
    $promotions = $promotion_storage->loadAvailable($order);
    foreach ($promotions as $promotion) {
      if (!$promotion->applies($order)) {
        continue;
      }
      // Ensure the promotion is in the right language, to ensure promotions
      // adjustments labels are correctly translated.
      if ($promotion->hasTranslation($content_langcode)) {
        $promotion = $promotion->getTranslation($content_langcode);
      }
      $promotion->apply($order);
    }
  }

}
