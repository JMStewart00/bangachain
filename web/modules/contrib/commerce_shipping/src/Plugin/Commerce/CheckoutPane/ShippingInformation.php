<?php

namespace Drupal\commerce_shipping\Plugin\Commerce\CheckoutPane;

use Drupal\commerce\AjaxFormTrait;
use Drupal\commerce\InlineFormManager;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\OrderShipmentSummaryInterface;
use Drupal\commerce_shipping\PackerManagerInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\profile\Entity\ProfileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the shipping information pane.
 *
 * Collects the shipping profile, then the information for each shipment.
 * Assumes that all shipments share the same shipping profile.
 *
 * @CommerceCheckoutPane(
 *   id = "shipping_information",
 *   label = @Translation("Shipping information"),
 *   wrapper_element = "fieldset",
 * )
 */
class ShippingInformation extends CheckoutPaneBase implements ContainerFactoryPluginInterface {

  use AjaxFormTrait;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * The inline form manager.
   *
   * @var \Drupal\commerce\InlineFormManager
   */
  protected $inlineFormManager;

  /**
   * The packer manager.
   *
   * @var \Drupal\commerce_shipping\PackerManagerInterface
   */
  protected $packerManager;

  /**
   * The order shipment summary.
   *
   * @var \Drupal\commerce_shipping\OrderShipmentSummaryInterface
   */
  protected $orderShipmentSummary;

  /**
   * Constructs a new ShippingInformation object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface $checkout_flow
   *   The parent checkout flow.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\commerce\InlineFormManager $inline_form_manager
   *   The inline form manager.
   * @param \Drupal\commerce_shipping\PackerManagerInterface $packer_manager
   *   The packer manager.
   * @param \Drupal\commerce_shipping\OrderShipmentSummaryInterface $order_shipment_summary
   *   The order shipment summary.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfo $entity_type_bundle_info, InlineFormManager $inline_form_manager, PackerManagerInterface $packer_manager, OrderShipmentSummaryInterface $order_shipment_summary) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow, $entity_type_manager);

    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->inlineFormManager = $inline_form_manager;
    $this->packerManager = $packer_manager;
    $this->orderShipmentSummary = $order_shipment_summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $checkout_flow,
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('plugin.manager.commerce_inline_form'),
      $container->get('commerce_shipping.packer_manager'),
      $container->get('commerce_shipping.order_shipment_summary')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'auto_recalculate' => TRUE,
      'require_shipping_profile' => TRUE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationSummary() {
    if (!empty($this->configuration['require_shipping_profile'])) {
      $summary = $this->t('Hide shipping costs until an address is entered: Yes') . '<br>';
    }
    else {
      $summary = $this->t('Hide shipping costs until an address is entered: No') . '<br>';
    }
    if (!empty($this->configuration['auto_recalculate'])) {
      $summary .= $this->t('Autorecalculate: Yes');
    }
    else {
      $summary .= $this->t('Autorecalculate: No');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['require_shipping_profile'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide shipping costs until an address is entered'),
      '#default_value' => $this->configuration['require_shipping_profile'],
    ];
    $form['auto_recalculate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto recalculate shipping costs when the shipping address changes'),
      '#default_value' => $this->configuration['auto_recalculate'],
      '#states' => [
        'visible' => [
          ':input[name="configuration[panes][shipping_information][configuration][require_shipping_profile]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['require_shipping_profile'] = !empty($values['require_shipping_profile']);
      $this->configuration['auto_recalculate'] = !empty($values['auto_recalculate']) && $this->configuration['require_shipping_profile'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isVisible() {
    if (!$this->order->hasField('shipments')) {
      return FALSE;
    }

    // The order must contain at least one shippable purchasable entity.
    foreach ($this->order->getItems() as $order_item) {
      $purchased_entity = $order_item->getPurchasedEntity();
      if ($purchased_entity && $purchased_entity->hasField('weight')) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneSummary() {
    $summary = [];
    if ($this->isVisible()) {
      $summary = $this->orderShipmentSummary->build($this->order, 'checkout');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $store = $this->order->getStore();
    $available_countries = [];
    foreach ($store->get('shipping_countries') as $country_item) {
      $available_countries[] = $country_item->value;
    }
    /** @var \Drupal\commerce\Plugin\Commerce\InlineForm\EntityInlineFormInterface $inline_form */
    $inline_form = $this->inlineFormManager->createInstance('customer_profile', [
      'profile_scope' => 'shipping',
      'available_countries' => $available_countries,
      'address_book_uid' => $this->order->getCustomerId(),
      // Don't copy the profile to address book until the order is placed.
      'copy_on_save' => FALSE,
    ], $this->getShippingProfile());

    // Prepare the form for ajax.
    // Not using Html::getUniqueId() on the wrapper ID to avoid #2675688.
    $pane_form['#wrapper_id'] = 'shipping-information-wrapper';
    $pane_form['#prefix'] = '<div id="' . $pane_form['#wrapper_id'] . '">';
    $pane_form['#suffix'] = '</div>';
    // Auto recalculation is enabled only when a shipping profile is required.
    $pane_form['#auto_recalculate'] = !empty($this->configuration['auto_recalculate']) && !empty($this->configuration['require_shipping_profile']);
    $pane_form['#after_build'][] = [static::class, 'autoRecalculateProcess'];

    $pane_form['shipping_profile'] = [
      '#parents' => array_merge($pane_form['#parents'], ['shipping_profile']),
      '#inline_form' => $inline_form,
    ];
    $pane_form['shipping_profile'] = $inline_form->buildInlineForm($pane_form['shipping_profile'], $form_state);
    $triggering_element = $form_state->getTriggeringElement();
    // The shipping_profile should always exist in form state (and not just
    // after "Recalculate shipping" is clicked).
    if (!$form_state->has('shipping_profile') ||
      // For some reason, when the address selected is changed, the shipping
      // profile in form state is stale.
      (isset($triggering_element['#parents']) && in_array('select_address', $triggering_element['#parents'], TRUE))) {
      $form_state->set('shipping_profile', $inline_form->getEntity());
    }

    $class = get_class($this);
    // Ensure selecting a different address refreshes the entire form.
    if (isset($pane_form['shipping_profile']['select_address'])) {
      $pane_form['shipping_profile']['select_address']['#ajax'] = [
        'callback' => [$class, 'ajaxRefreshForm'],
        'element' => $pane_form['#parents'],
      ];
      // Selecting a different address should trigger a recalculation.
      $pane_form['shipping_profile']['select_address']['#recalculate'] = TRUE;
    }

    $pane_form['recalculate_shipping'] = [
      '#type' => 'button',
      '#value' => $this->t('Recalculate shipping'),
      '#recalculate' => TRUE,
      '#ajax' => [
        'callback' => [$class, 'ajaxRefreshForm'],
        'element' => $pane_form['#parents'],
      ],
      // The calculation process only needs a valid shipping profile.
      '#limit_validation_errors' => [
        array_merge($pane_form['#parents'], ['shipping_profile']),
      ],
      '#after_build' => [
        [static::class, 'clearValues'],
      ],
    ];
    $pane_form['removed_shipments'] = [
      '#type' => 'value',
      '#value' => [],
    ];
    $pane_form['shipments'] = [
      '#type' => 'container',
    ];

    $shipping_profile = $form_state->get('shipping_profile');
    $shipments = $this->order->get('shipments')->referencedEntities();
    $recalculate_shipping = $form_state->get('recalculate_shipping');
    $can_calculate_rates = $this->canCalculateRates($shipping_profile);

    // If the shipping recalculation is triggered, ensure the rates can
    // be recalculated (i.e a valid address is entered).
    if ($recalculate_shipping && !$can_calculate_rates) {
      $recalculate_shipping = FALSE;
      $shipments = [];
    }

    // Ensure the profile is saved with the latest address, it's necessary
    // to do that in case the profile isn't new, otherwise the shipping profile
    // referenced by the shipment won't reflect the updated address.
    if (!$shipping_profile->isNew() &&
      $shipping_profile->hasTranslationChanges() &&
      $can_calculate_rates) {
      $shipping_profile->save();
      $inline_form->setEntity($shipping_profile);
    }

    $force_packing = empty($shipments) && $can_calculate_rates;
    if ($recalculate_shipping || $force_packing) {
      // We're still relying on the packer manager for packing the order since
      // we don't want the shipments to be saved for performance reasons.
      // The shipments are saved on pane submission.
      list($shipments, $removed_shipments) = $this->packerManager->packToShipments($this->order, $shipping_profile, $shipments);

      // Store the IDs of removed shipments for submitPaneForm().
      $pane_form['removed_shipments']['#value'] = array_map(function ($shipment) {
        /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
        return $shipment->id();
      }, $removed_shipments);
    }

    $single_shipment = count($shipments) === 1;
    foreach ($shipments as $index => $shipment) {
      /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
      $pane_form['shipments'][$index] = [
        '#parents' => array_merge($pane_form['#parents'], ['shipments', $index]),
        '#array_parents' => array_merge($pane_form['#parents'], ['shipments', $index]),
        '#type' => $single_shipment ? 'container' : 'fieldset',
        '#title' => $shipment->getTitle(),
      ];
      $form_display = EntityFormDisplay::collectRenderDisplay($shipment, 'checkout');
      $form_display->removeComponent('shipping_profile');
      $form_display->buildForm($shipment, $pane_form['shipments'][$index], $form_state);
      $pane_form['shipments'][$index]['#shipment'] = $shipment;
    }

    // Update the shipments and save the order if no rate was explicitly
    // selected, that usually occurs when changing addresses, this will ensure
    // the default rate is selected/applied.
    if (!$this->hasRateSelected($pane_form, $form_state) && ($recalculate_shipping || $force_packing)) {
      array_map(function (ShipmentInterface $shipment) {
        if (!$shipment->isNew()) {
          $shipment->save();
        }
      }, $shipments);
      $this->order->set('shipments', $shipments);
      $this->order->save();
    }

    return $pane_form;
  }

  /**
   * Pane form #process callback: adds recalculation settings.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The modified element.
   */
  public static function autoRecalculateProcess(array $element, FormStateInterface $form_state) {
    if ($element['#auto_recalculate']) {
      $recalculate_button_selector = $element['recalculate_shipping']['#attributes']['data-drupal-selector'];
      $element['#attached']['library'][] = 'commerce_shipping/shipping_checkout';
      $element['#attached']['drupalSettings']['commerceShipping'] = [
        'wrapper' => $element['#wrapper_id'],
        'recalculateButtonSelector' => '[data-drupal-selector="' . $recalculate_button_selector . '"]',
      ];
      $element['recalculate_shipping']['#attributes']['class'][] = 'js-hide';
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $shipment_indexes = Element::children($pane_form['shipments']);
    $triggering_element = $form_state->getTriggeringElement();
    $recalculate = !empty($triggering_element['#recalculate']);
    $button_type = isset($triggering_element['#button_type']) ? $triggering_element['#button_type'] : '';
    /** @var \Drupal\commerce\Plugin\Commerce\InlineForm\EntityInlineFormInterface $inline_form */
    $inline_form = $pane_form['shipping_profile']['#inline_form'];
    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $inline_form->getEntity();

    // The profile in form state needs to reflect the submitted values,
    // for packers invoked on form rebuild, or "Billing same as shipping".
    $form_state->set('shipping_profile', $profile);

    $shipments = [];
    foreach ($shipment_indexes as $index) {
      if (!isset($pane_form['shipments'][$index]['#shipment'])) {
        continue;
      }
      $shipment = clone $pane_form['shipments'][$index]['#shipment'];
      $form_display = EntityFormDisplay::collectRenderDisplay($shipment, 'checkout');
      $form_display->removeComponent('shipping_profile');
      $form_display->extractFormValues($shipment, $pane_form['shipments'][$index], $form_state);
      $form_display->validateFormValues($shipment, $pane_form['shipments'][$index], $form_state);
      $shipment->setShippingProfile($profile);
      $shipments[] = $shipment;
    }

    if (!$recalculate && $button_type == 'primary' && !$shipments) {
      // The checkout step was submitted without shipping being calculated.
      // Force the recalculation now and reload the page.
      $recalculate = TRUE;
      $this->messenger()->addError($this->t('Please select a shipping method.'));
      $form_state->setRebuild(TRUE);
    }
    $form_state->set('recalculate_shipping', $recalculate);

    // If another rate was selected, update the shipments and refresh the order
    // to reflect the new rate in the order summary.
    if (!empty($triggering_element['#rate'])) {
      // Unfortunately, we have to save the shipment, otherwise
      // $order->get('shipments')->referencedEntities() will return stale
      // shipments in case the order is already referencing saved shipments.
      array_map(function (ShipmentInterface $shipment) {
        if (!$shipment->isNew()) {
          $shipment->save();
        }
      }, $shipments);
      $this->order->set('shipments', $shipments);
      $this->order->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    /** @var \Drupal\commerce\Plugin\Commerce\InlineForm\EntityInlineFormInterface $inline_form */
    $inline_form = $pane_form['shipping_profile']['#inline_form'];
    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $inline_form->getEntity();

    // Save the modified shipments.
    $shipments = [];
    foreach (Element::children($pane_form['shipments']) as $index) {
      if (!isset($pane_form['shipments'][$index]['#shipment'])) {
        continue;
      }

      /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
      $shipment = clone $pane_form['shipments'][$index]['#shipment'];
      $form_display = EntityFormDisplay::collectRenderDisplay($shipment, 'checkout');
      $form_display->removeComponent('shipping_profile');
      $form_display->extractFormValues($shipment, $pane_form['shipments'][$index], $form_state);
      $shipment->setShippingProfile($profile);
      $shipment->save();
      $shipments[] = $shipment;
    }
    $this->order->shipments = $shipments;

    // Delete shipments that are no longer in use.
    $removed_shipment_ids = $pane_form['removed_shipments']['#value'];
    if (!empty($removed_shipment_ids)) {
      $shipment_storage = $this->entityTypeManager->getStorage('commerce_shipment');
      $removed_shipments = $shipment_storage->loadMultiple($removed_shipment_ids);
      $shipment_storage->delete($removed_shipments);
    }
  }

  /**
   * Clears user input of selected shipping rates.
   *
   * This is required to prevent invalid options being selected is a shipping
   * rate is no longer available, when the selected address is updated or when
   * the rates recalculation is explicitly triggered.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The element.
   */
  public static function clearValues(array $element, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if (!$triggering_element) {
      return $element;
    }
    $triggering_element_name = end($triggering_element['#parents']);
    if (in_array($triggering_element_name, ['recalculate_shipping', 'select_address'], TRUE)) {
      $user_input = &$form_state->getUserInput();
      $parents = $element['#parents'];
      array_pop($parents);
      $parents[] = 'shipments';
      NestedArray::unsetValue($user_input, $parents);
    }
    return $element;
  }

  /**
   * Gets the shipping profile.
   *
   * The shipping profile is assumed to be the same for all shipments.
   * Therefore, it is taken from the first found shipment, or created from
   * scratch if no shipments were found.
   *
   * @return \Drupal\profile\Entity\ProfileInterface
   *   The shipping profile.
   */
  protected function getShippingProfile() {
    $shipping_profile = NULL;
    /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
    foreach ($this->order->get('shipments')->referencedEntities() as $shipment) {
      $shipping_profile = $shipment->getShippingProfile();
      break;
    }
    if (!$shipping_profile) {
      $profile_type_id = 'customer';
      // Check whether the order type has another profile type ID specified.
      $order_type_id = $this->order->bundle();
      $order_bundle_info = $this->entityTypeBundleInfo->getBundleInfo('commerce_order');
      if (!empty($order_bundle_info[$order_type_id]['shipping_profile_type'])) {
        $profile_type_id = $order_bundle_info[$order_type_id]['shipping_profile_type'];
      }

      $shipping_profile = $this->entityTypeManager->getStorage('profile')->create([
        'type' => $profile_type_id,
        'uid' => 0,
      ]);
    }

    return $shipping_profile;
  }

  /**
   * Gets whether shipping rates can be calculated for the given profile.
   *
   * Ensures that a required shipping address is present and valid.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile.
   *
   * @return bool
   *   TRUE if shipping rates can be calculated, FALSE otherwise.
   */
  protected function canCalculateRates(ProfileInterface $profile) {
    if (!empty($this->configuration['require_shipping_profile'])) {
      $violations = $profile->get('address')->validate();
      return count($violations) === 0;
    }

    return TRUE;
  }

  /**
   * Determines whether a shipping rate is currently selected.
   *
   * @param array $pane_form
   *   The pane form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the parent form.
   *
   * @return bool
   *   Whether a shipping rate is currently selected.
   */
  protected function hasRateSelected(array $pane_form, FormStateInterface $form_state) {
    $user_input = NestedArray::getValue($form_state->getUserInput(), $pane_form['#parents']);

    if (empty($user_input['shipments'])) {
      return FALSE;
    }

    // Loop over the shipments input to see if a shipping rate was selected.
    foreach ($user_input['shipments'] as $values) {
      if (!empty(array_filter($values['shipping_method']))) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
