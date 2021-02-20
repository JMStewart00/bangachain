<?php

namespace Drupal\htmlmail\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\htmlmail\Helper\HtmlMailHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;

/**
 * Class HtmlMailTestForm.
 *
 * @package Drupal\htmlmail\Form
 */
class HtmlMailTestForm extends FormBase {

  const KEY_NAME = 'test';
  const DEFAULT_MAIL = 'user@example.com';

  /**
   * The mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The user account service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $accountInterface;

  /**
   * HtmlMailTestForm constructor.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account service.
   */
  public function __construct(MailManagerInterface $mail_manager, AccountInterface $account) {
    $this->mailManager = $mail_manager;
    $this->accountInterface = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.mail'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'htmlmail_test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('htmlmail.settings');

    $defaults = $config->get('test');
    if (empty($defaults)) {
      $defaults = [
        'to' => $config->get('site_mail') ?: self::DEFAULT_MAIL,
        'subject' => self::KEY_NAME,
        'body' => [
          'value' => self::KEY_NAME,
        ],
        'class' => HtmlMailHelper::getModuleName(),
      ];
    }

    if (empty($defaults['body']['format'])) {
      $defaults['body']['format'] = filter_default_format();
    }
    $form['to'] = [
      '#type' => 'textfield',
      '#title' => $this->t('To'),
      '#default_value' => $defaults['to'],
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $defaults['subject'],
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body'),
      '#rows' => 20,
      '#default_value' => $defaults['body']['value'],
      '#format' => $defaults['body']['format'],
      '#required' => TRUE,
    ];

    $form['class'] = [
      '#type' => 'select',
      '#title' => $this->t('Test mail sending class'),
      '#options' => $this->getOptions(),
      '#default_value' => $defaults['class'],
      '#description' => $this->t('Select the MailSystemInterface implementation to be tested.'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send test message'),
    ];

    return $form;
  }

  /**
   * Returns a list with all mail plugins.
   *
   * @return string[]
   *   List of mail plugin labels, keyed by ID.
   */
  protected function getOptions() {
    $list = [];

    // Append all MailPlugins.
    foreach ($this->mailManager->getDefinitions() as $definition) {
      $list[$definition['id']] = $definition['label'];
    }

    if (empty($list)) {
      $list['htmlmail'] = 'HtmlMailSystem';
    }

    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get the form values.
    $defaults = [
      'to' => $form_state->getValue('to'),
      'subject' => $form_state->getValue('subject'),
      'body' => $form_state->getValue('body'),
      'class' => $form_state->getValue('class'),
    ];

    // Set the defaults for reuse.
    $config = $this->configFactory()->getEditable('htmlmail.settings');
    $config->set('test', $defaults)->save();

    // Send the email.
    $params = [
      'subject' => $defaults['subject'],
      'body' => check_markup(
        $defaults['body']['value'],
        $defaults['body']['format']
      ),
    ];

    // Send the email.
    $langcode = $this->accountInterface->getPreferredLangcode();

    $config = $this->configFactory()->getEditable('mailsystem.settings');
    $config
      ->set('defaults.sender', $defaults['class'])
      ->set('defaults.formatter', $defaults['class'])
      ->save();

    $result = $this->mailManager->mail(HtmlMailHelper::getModuleName(), self::KEY_NAME, $defaults['to'], $langcode, $params, NULL, TRUE);
    if ($result['result'] === TRUE) {
      $this->messenger()->addMessage($this->t('HTML Mail test message sent.'));
    }
    else {
      $this->messenger()->addError($this->t('Something went wrong. Please check @logs for details.', [
        '@logs' => Link::createFromRoute($this->t('logs'), 'dblog.overview')->toString(),
      ]));
    }
  }

}
