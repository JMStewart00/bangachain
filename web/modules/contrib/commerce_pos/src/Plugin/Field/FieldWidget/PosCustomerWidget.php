<?php

namespace Drupal\commerce_pos\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Drupal\user\UserStorageInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;

/**
 * Plugin implementation of the 'pos_customer_widget' widget.
 *
 * @FieldWidget(
 *   id = "pos_customer_widget",
 *   label = @Translation("Pos customer widget"),
 *   field_types = {
 *     "entity_reference"
 *   },
 * )
 */
class PosCustomerWidget extends WidgetBase implements WidgetInterface, ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Dependency handler for User.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs a EntityReferenceEntityFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   Used to handle UserStorage.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository, UserStorageInterface $user_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->userStorage = $user_storage;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * Creates Container.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   A contract for the Container.
   * @param array $configuration
   *   Current config.
   * @param string $plugin_id
   *   Current plugin_id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   *
   * @return \Drupal\commerce_pos\Plugin\Field\FieldWidget\PosCustomerWidget|\Drupal\Core\Plugin\ContainerFactoryPluginInterface
   *   Your new ContainerFactoryPluginInterface. Use wisely.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('entity_type.manager')->getStorage('user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings += [
      'size' => 60,
      'placeholder' => 'Enter a name, email, or phone number',
      'num_results' => 10,
    ];
    return $settings;
  }

  /**
   * Builds settings form for customer widget.
   *
   * @param array $form
   *   The current form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The element to be added to parent.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    parent::settingsForm($form, $form_state);
    $elements = [];

    $elements['size'] = [
      '#type' => 'number',
      '#title' => $this->t('Size of textfield'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
    ];
    $elements['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];
    $elements['num_results'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of search results'),
      '#default_value' => $this->getSetting('num_results'),
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 50,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    parent::settingsSummary();
    $summary = [];

    $summary[] = $this->t('Textfield size: @size', ['@size' => $this->getSetting('size')]);
    if (!empty($this->getSetting('placeholder'))) {
      $summary[] = $this->t('Placeholder: @placeholder', ['@placeholder' => $this->getSetting('placeholder')]);
    }
    $summary[] = $this->t('Number of results: @num_results', ['@num_results' => $this->getSetting('num_results')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    // Error if this empty function is deleted or parent function is called.
  }

  /**
   * Builds fieldset and textfield for customer autocomplete.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   Items in the field.
   * @param int $delta
   *   Delta Alpha Theta Alpha.
   * @param array $element
   *   The array the fieldset will be attached to.
   * @param array $form
   *   The form arary.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The formstate object.
   *
   * @return array
   *   The altered element array with new values.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $form_state->getFormObject()->getEntity();

    if ($form_state->getTriggeringElement()) {
      $this->processFormSubmission($form, $form_state);
    }

    $wrapper_id = Html::getUniqueId(__CLASS__);
    $element['order_customer'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Search for Customer'),
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
      '#attributes' => ['class' => ['customer-fieldset']],
    ];

    $customer = $order->getCustomer();

    // Add an existing user as customer.
    $element['order_customer']['customer_textfield'] = [
      '#type' => 'textfield',
      '#disabled' => !$customer->isAnonymous(),
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#default_value' => $customer->isAnonymous() ? NULL : $customer->getDisplayName(),
      '#autocomplete_route_name' => 'commerce_pos.pos_customer_widget_autocomplete',
      '#autocomplete_route_parameters' => [
        'count' => $this->getSetting('num_results'),
      ],
      '#ajax' => [
        'event' => 'autocompleteclose',
        'callback' => [$this, 'ajaxRefreshAddMessage'],
        'wrapper' => $wrapper_id,
      ],
    ];

    if (!$customer->isAnonymous()) {
      $element['order_customer']['remove_customer'] = [
        '#type' => 'button',
        '#name' => 'remove_customer',
        '#value' => $this->t('Remove Customer'),
        '#attributes' => [
          'style' => 'display: block;',
        ],
        '#ajax' => [
          'callback' => [$this, 'ajaxRefreshAddMessage'],
          'wrapper' => $wrapper_id,
        ],
        '#limit_validation_errors' => [],
      ];
    }

    return ['target_id' => $element];
  }

  /**
   * Submit handler for the POS order customer select form.
   *
   * If a new, valid email is entered, POSForm will create new user.
   *
   * @param array $form
   *   The parent form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function processFormSubmission(array $form, FormStateInterface &$form_state) {
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $form_state->getFormObject()->getEntity();

    // Handle 'Remove' button to set user to 'Anonymous'.
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element['#name'] == 'remove_customer') {
      /** @var \Drupal\user\Entity\User $anon_user */
      $anon_user = $this->userStorage->load(0);
      $order->setCustomer($anon_user)->save();
      $form_state->setRebuild();
      return;
    }

    // Get input value from form state.
    $input_value = $form_state->getValue('uid')[0]['target_id']['order_customer']['customer_textfield'] ?? NULL;
    if (empty($input_value)) {
      return;
    }

    // If input is not a valid email, pull user from what autocomplete returned.
    $user_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($input_value);

    // If user_id is a legit user, add to order.
    if ($user_id) {
      /** @var \Drupal\user\Entity\User $user */
      $user = $this->userStorage->load($user_id);
      if ($user && $user->isActive()) {
        $order->setCustomer($user)->save();
        $form_state->setRebuild();
        return;
      }
    }
  }

  /**
   * Handles error elements.
   *
   * @param array $element
   *   Element array.
   * @param \Symfony\Component\Validator\ConstraintViolationInterface $error
   *   Constraint violations.
   * @param array $form
   *   The current form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return array|bool|mixed
   *   Either an array if 'target_id' is set, false if array not set.
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    parent::errorElement($element, $error, $form, $form_state);
    return isset($element['target_id']) ? $element['target_id'] : FALSE;
  }

  /**
   * Ajax callback; adds a message below the customer widget field.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormState object.
   *
   * @return array
   *   Returns the form array with added message.
   */
  public function ajaxRefreshAddMessage(array &$form, FormStateInterface $form_state) {
    $message = NULL;

    // Get input from customer widget.
    $customer_username = $form_state->getValue(['uid'])[0]['target_id']['order_customer']['customer_textfield'];
    $customer_user_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($customer_username);

    // If customer widget has input.
    if (!empty($customer_username)) {

      // Get order customer information.
      /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
      $order = $form_state->getFormObject()->getEntity();

      $uid = $order->getCustomerId();

      // If customer widget has not matched input with a user.
      if ($customer_user_id != $uid) {
        $message = $this->t('Account not found. Provide an email to create an account.');
      }

      // If input is a valid email address.
      if (filter_var($customer_username, FILTER_VALIDATE_EMAIL)) {
        $message = $this->t('A customer account will be created with this email.');
      }
    }

    $form['uid']['widget'][0]['target_id']['order_customer']['message'] = [
      '#type' => 'container',
      '#markup' => $message,
    ];
    return $form['uid']['widget'];
  }

}
