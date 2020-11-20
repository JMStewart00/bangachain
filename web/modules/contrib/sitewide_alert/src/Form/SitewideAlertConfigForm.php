<?php

namespace Drupal\sitewide_alert\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SitewideAlertConfigForm.
 */
class SitewideAlertConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sitewide_alert.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sitewide_alert_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sitewide_alert.settings');
    $form['show_on_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show on Administration Pages'),
      '#description' => $this->t('This will allow the alerts to show on backend admin pages as well as the frontend.'),
      '#default_value' => $config->get('show_on_admin'),
    ];

    $form['alert_styles'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Available alert styles'),
      '#default_value' => $config->get('alert_styles'),
      '#description' => '<p>' . $this->t(
          'Enter the list of key|value pairs of alert styles separated by new line, where key is the alert style class name without prefix, and the value is displayed to the alert editor. <br/><strong>For example:</strong><ul><li>To add the class <em>alert-info</em>, use <code>info|Info</code></li><li>To add the class <em>alert-danger</em>, use <code>danger|Very Important</code></li></ul><strong>Warning!</strong> Pre-existing values will be reset.'
        ) . '<br><br></p>',
    ];

    $form['automatic_refresh'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically Update (Refresh) Alerts'),
      '#default_value' => $config->get('automatic_refresh'),
      '#description' => $this->t('If enabled, the browser will periodically check and display any added, removed, or updated alerts without requiring the visitor to refresh the page. This is recommend for time sensitive alerts. When disabled, alerts are only updated once per page view.'),
    ];

    $form['refresh_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Browser Refresh Interval (in seconds)'),
      '#default_value' => $config->get('refresh_interval'),
      '#description' => $this->t('How often should an open page request information on any new or changed alerts. If you have a good full page / reverse proxy caching strategy in effect, this can be set this to a low number (5-15 seconds) to have a more of an "immediate" update. If you do not have a good caching strategy in place, or most of your traffic is authenticated and can\'t be cached, a larger time (60 or 120 seconds) may be warranted to reduce a potential performance impact on the web server.'),
      '#states' => [
        'visible' => [
          ':input[name="automatic_refresh"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('sitewide_alert.settings')
      ->set('show_on_admin', $form_state->getValue('show_on_admin'))
      ->set('alert_styles', $form_state->getValue('alert_styles'))
      ->set('refresh_interval', $form_state->getValue('refresh_interval'))
      ->set('automatic_refresh', $form_state->getValue('automatic_refresh'))
      ->save();
  }

}
