<?php

namespace Drupal\commerce_order\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OrderSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new OrderSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['commerce_order.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_order_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_order.settings');
    $form['log_version_mismatch_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Order version mismatch settings'),
    ];
    $form['log_version_mismatch_wrapper']['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('<p>An order version mismatch happens when an order being saved is out of sync. Saving the order could cause invalid data.</p><p>By default, order version mismatches are only logged, allowing the order to be saved but logging the mismatch for debugging.</p>'),
    ];
    $form['log_version_mismatch_wrapper']['log_version_mismatch'] = [
      '#type' => 'radios',
      '#title' => $this->t('Order version mismatch handling'),
      '#options' => [
        1 => $this->t('Allow errors with version mismatch to be saved.'),
        0 => $this->t('Prevent orders with version mismatch from being saved.'),
      ],
      '#default_value' => (int) $config->get('log_version_mismatch'),
    ];
    $form['log_version_mismatch_wrapper']['log_warning'] = [
      '#type' => 'item',
      '#markup' => $this->t('<p>Orders with a version mismatch will be saved, and a warning will be logged.</p>'),
      '#states' => [
        'visible' => [
          ':input[name="log_version_mismatch"]' => ['value' => 1],
        ],
      ],
    ];
    $form['log_version_mismatch_wrapper']['exception_warning'] = [
      '#type' => 'item',
      '#markup' => $this->t('<p>Orders with a version mismatch will not be saved, and an exception will be thrown, halting processing.</p>'),
      '#states' => [
        'visible' => [
          ':input[name="log_version_mismatch"]' => ['value' => 0],
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
    $config = $this->config('commerce_order.settings');
    $config->set('log_version_mismatch', (bool) $form_state->getValue('log_version_mismatch'));
    $config->save();

    // Rebuild entity type definitions so that the commerce_order entity type
    // definition respects the `log_version_mismatch` value.
    $this->entityTypeManager->clearCachedDefinitions();
  }

}
