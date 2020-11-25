<?php

declare(strict_types = 1);

namespace Drupal\bangachain_menu\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a custom mobile menu block.
 *
 * @Block(
 *   id = "bangachain_mobile_menu_block",
 *   admin_label = @Translation("Mobile Menu"),
 * )
 */
class MobileMenuBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#markup' => $this->t('This is placeholder content. It is overridden with the block template.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account): object {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $config = $this->getConfiguration();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['bangachain_mobile_menu_settings'] = $form_state->getValue('bangachain_mobile_menu_settings');
  }

}
