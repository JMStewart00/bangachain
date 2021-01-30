<?php

namespace Drupal\commerce_pos_barcode_scanning\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_pos_barcode_scanning_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_pos_barcode_scanning.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_pos_barcode_scanning.settings');

    $form['status_on_load'] = [
      '#type' => 'radios',
      '#title' => $this->t('Scanning interface open by default'),
      '#description' => $this->t('When the POS interface is loaded, should
        the barcode scanning interface start open or closed.'),
      '#default_value' => $config->get('status_on_load'),
      '#options' => [
        'open' => $this->t('Open'),
        'closed' => $this->t('Closed'),
      ],
    ];

    $form['close_after_scanning'] = [
      '#type' => 'radios',
      '#title' => $this->t('Close after successful scanning'),
      '#description' => $this->t('After a barcode is successfully scanned,
        should the scanning interface close or remain open for additional scans'),
      '#default_value' => $config->get('close_after_scanning'),
      '#options' => [
        'open' => $this->t('Leave open'),
        'closed' => $this->t('Close'),
      ],
    ];

    $form['delay'] = [
      '#type' => 'number',
      '#title' => $this->t('Delay in milliseconds before next item'),
      '#description' => $this->t('After a barcode is successfully scanned,
        there is a delay before the next barcode will be read, so it is not scanned twice.'),
      '#default_value' => $config->get('delay'),
      '#min' => 0,
      '#max' => 10000,
      '#step' => 100,
      '#size' => 5,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory()->getEditable('commerce_pos_barcode_scanning.settings')
      ->set('status_on_load', $form_state->getValue('status_on_load'))
      ->set('close_after_scanning', $form_state->getValue('close_after_scanning'))
      ->set('delay', $form_state->getValue('delay'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
