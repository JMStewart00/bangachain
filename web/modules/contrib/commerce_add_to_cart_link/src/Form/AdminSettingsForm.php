<?php

namespace Drupal\commerce_add_to_cart_link\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the admin form for editing module settings.
 */
class AdminSettingsForm extends ConfigFormBase {

  /**
   * The user role storage.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * Constructs a AdminSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->setConfigFactory($config_factory);
    $this->roleStorage = $entity_type_manager->getStorage('user_role');
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
  public function getFormId() {
    return 'commerce_add_to_cart_link_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_add_to_cart_link.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_add_to_cart_link.settings');

    $form['csrf_token'] = [
      '#type' => 'details',
      '#title' => $this->t('CSRF token'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    /** @var \Drupal\user\RoleInterface[] $roles */
    $roles = $this->roleStorage->loadMultiple();
    usort($roles, function ($a, $b) {
      /**
       * @var \Drupal\user\RoleInterface $a
       * @var \Drupal\user\RoleInterface $b
       */
      return $a->getWeight() - $b->getWeight();
    });

    $role_options = array_reduce($roles, function ($result, $item) {
      /** @var \Drupal\user\RoleInterface $item */
      $result[$item->id()] = $item->label();
      return $result;
    }, []);

    $form['csrf_token']['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#description' => $this->t('Add to cart and add to wishlist links will be protected with a session based CSRF token for users having at least one role checked here. Enabling CSRF tokens for anonymous user role can cause problems with static page cache and reverse proxies, as the tokens vary by user session.'),
      '#options' => $role_options,
      '#default_value' => $config->get('csrf_token.roles'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $roles = array_values(array_filter($form_state->getValue(['csrf_token', 'roles'])));
    $this->config('commerce_add_to_cart_link.settings')
      ->set('csrf_token.roles', $roles)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
