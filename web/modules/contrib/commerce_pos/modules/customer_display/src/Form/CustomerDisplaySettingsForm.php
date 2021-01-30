<?php

namespace Drupal\commerce_pos_customer_display\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class CustomerDisplaySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_pos_customer_display_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_pos_customer_display.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_pos_customer_display.settings');

    $form['websocket']['host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Websocket Host'),
      '#description' => $this->t('The websocket host name that your server.php is available at, please include the port if needed.'),
      '#default_value' => $config->get('websocket_host'),
    ];

    $form['websocket']['external_port'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Websocket External Port'),
      '#description' => $this->t('The port to connect to, often not a standard 80 or 443.'),
      '#maxlength' => 6,
      '#size' => 6,
      '#default_value' => $config->get('websocket_external_port'),
    ];

    $form['websocket']['internal_port'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Websocket Internal Port'),
      '#description' => $this->t('The port to connect to, often not a standard 80 or 443.'),
      '#maxlength' => 6,
      '#size' => 6,
      '#default_value' => $config->get('websocket_internal_port'),
    ];

    $form['websocket']['address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Websocket Client Address(s)'),
      '#description' => $this->t("Addresses that can connect to the service, if you can't control this, just leave it 0.0.0.0"),
      '#default_value' => $config->get('websocket_address'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->configFactory()->getEditable('commerce_pos_customer_display.settings')
      // Set the submitted configuration setting.
      ->set('websocket_host', $form_state->getValue('host'))
      ->set('websocket_internal_port', $form_state->getValue('internal_port'))
      ->set('websocket_external_port', $form_state->getValue('external_port'))
      ->set('websocket_address', $form_state->getValue('address'))
      /* Need to verify if form values and settings are correct and reflect the nature of how settings will be handled before any save functionality is done. */
      ->save();

    // Validation of course needed as well.
    parent::submitForm($form, $form_state);
  }

}
