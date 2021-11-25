<?php

namespace Drupal\bangachain_custom_blocks\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Hours' Block.
 *
 * @Block(
 *   id = "bangachain_hours_block",
 *   admin_label = @Translation("Hours Block")
 * )
 */
class HoursBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    return [
      '#markup' => $config['content']['value'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['content'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Content'),
      '#description' => $this->t('The text displayed in the content area of the block.'),
      '#rows' => 10,
      '#default_value' => isset($config['content']['value']) ? $config['content']['value'] : NULL,
      '#format' => 'full_html',
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['content'] = $values['content'];
  }

}
