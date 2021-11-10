<?php

namespace Drupal\commerce_wishlist\Form;

use Drupal\commerce\EntityHelper;
use Drupal\commerce_wishlist\Entity\WishlistType;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the wishlist settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a new SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($config_factory);

    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_wishlist_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['commerce_wishlist.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('commerce_wishlist.settings');
    $form['wishlist'] = [
      '#type' => 'details',
      '#title' => 'Wishlist settings',
      '#open' => TRUE,
    ];
    $form['wishlist']['allow_multiple'] = [
      '#type' => 'checkbox',
      '#default_value' => $config->get('allow_multiple'),
      '#title' => $this->t('Allow multiple wishlists'),
      '#description' => $this->t('Determines whether multiple wishlists are allowed.'),
    ];

    $wishlist_types = WishlistType::loadMultiple();
    $options = EntityHelper::extractLabels($wishlist_types);
    $form['wishlist']['default_type'] = [
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $config->get('default_type'),
      '#title' => $this->t('Default wishlist type'),
      '#description' => $this->t('The default wishlist type to use when creating a new wishlist.'),
    ];
    $form['view_modes'] = [
      '#type' => 'details',
      '#title' => 'View modes',
      '#description' => $this->t('The view mode to use when rendering wishlist items.'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    foreach (commerce_wishlist_get_purchasable_entity_types() as $entity_type_id => $entity_type) {
      $view_modes = $this->entityDisplayRepository->getViewModes($entity_type_id);
      $view_mode_labels = array_map(function ($view_mode) {
        return $view_mode['label'];
      }, $view_modes);
      $default_view_mode = $config->get('view_modes.' . $entity_type_id);
      $default_view_mode = $default_view_mode ?: 'cart';

      $form['view_modes'][$entity_type_id] = [
        '#type' => 'select',
        '#title' => $entity_type->getLabel(),
        '#options' => $view_mode_labels,
        '#default_value' => $default_view_mode,
        '#required' => TRUE,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('commerce_wishlist.settings');
    $values = $form_state->getValues();
    foreach (['allow_multiple', 'default_type', 'view_modes'] as $key) {
      if (!isset($values[$key])) {
        continue;
      }
      $config->set($key, $values[$key]);
    }
    $config->save();
  }

}
