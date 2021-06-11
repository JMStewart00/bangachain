<?php

namespace Drupal\commerce_pos_loyalty_points_support\Plugin\Field\FieldWidget;

use Drupal\commerce_pos\Plugin\Field\FieldWidget\PosCustomerWidget;
use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'pos_customer_loyalty_points_widget' widget.
 *
 * @FieldWidget(
 *   id = "pos_customer_loyalty_points_widget",
 *   label = @Translation("Pos customer widget with Loyalty points"),
 *   field_types = {
 *     "entity_reference"
 *   },
 * )
 */
class PosCustomerLoyaltyPointsWidget extends PosCustomerWidget {

  use LoyaltyPointsTrait;

  /**
   * {@inheritdoc}
   *
   * Most of this code is re-used from FieldWidget class PosCustomerWidget.
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $form_state->getFormObject()->getEntity();

    // Make a wrapper for the entire form.
    // @todo this feels off. There must be a better way.
    if (empty($form_state->wrapper_id)) {
      $wrapper_id = Html::getUniqueId(__CLASS__);
      $form['#prefix'] = '<div id="' . $wrapper_id . '">';
      $form['#suffix'] = '</div>';
    }
    else {
      $wrapper_id = $form_state->wrapper_id;
    }

    if ($form_state->getTriggeringElement()) {
      $this->processFormSubmission($form, $form_state);
    }

    $element['order_customer'] = [
      '#type' => 'fieldset',
      '#title' => t('Customer'),
    ];

    // If the customer for the order is already set.
    $customer = $order->getCustomer();
    if ($customer->id() != 0) {
      $element['order_customer']['current_customer'] = [
        '#type' => 'textfield',
        '#default_value' => $customer->getAccountName(),
        '#disabled' => TRUE,
        '#size' => 30,
      ];

      $element['order_customer']['remove_current_user'] = [
        '#type' => 'button',
        '#value' => t('Remove'),
        '#name' => 'remove-current-user',
        '#ajax' => [
          'callback' => [$this, 'ajaxRefresh'],
          'wrapper' => $wrapper_id,
        ],
        '#limit_validation_errors' => [],
      ];
      $element['order_customer']['loyalty_points'] = [
        '#type' => 'item',
        '#title' => $this->getLoyaltyPoints($customer->id()),
        '#weight' => 99,
      ];
    }
    // Else, if no customer has been set for the order.
    else {
      $selected_customer_type = $form_state->getValue([
        'uid',
        '0',
        'target_id',
        'order_customer',
        'customer_type',
      ], 'existing');

      $element['order_customer']['customer_type'] = [
        '#type' => 'radios',
        '#title' => t('Order for'),
        '#title_display' => 'invisible',
        '#attributes' => [
          'class' => ['container-inline'],
        ],
        '#required' => TRUE,
        '#options' => [
          'existing' => t('Existing customer'),
          'new' => t('New customer'),
        ],
        '#default_value' => $selected_customer_type,
        '#ajax' => [
          'callback' => [$this, 'ajaxRefresh'],
          'wrapper' => $wrapper_id,
        ],
        '#limit_validation_errors' => [],
      ];

      // Add an existing customer.
      if ($selected_customer_type == 'existing') {
        $element['order_customer']['user'] = [
          '#type' => 'textfield',
          '#size' => $this->getSetting('size'),
          '#placeholder' => $this->getSetting('placeholder'),
          '#default_value' => NULL,
          '#autocomplete_route_name' => 'commerce_pos.pos_customer_widget_autocomplete',
          '#autocomplete_route_parameters' => [
            'count' => $this->getSetting('num_results'),
          ],
          '#ajax' => [
            'event' => 'autocompleteclose',
            'callback' => [$this, 'ajaxRefresh'],
            'wrapper' => $wrapper_id,
          ],
          '#required' => TRUE,
        ];

        if (!empty($form_state->getUserInput()['uid'][0]['target_id']['order_customer']['user'])) {
          $autocomplete_result = $form_state->getUserInput()['uid'][0]['target_id']['order_customer']['user'];
          $start = strpos($autocomplete_result, '(') + 1;
          $end = strpos($autocomplete_result, ')');
          $length = $end - $start;
          $uid = substr($autocomplete_result, $start, $length);
          $uid = trim($uid);

          $element['order_customer']['loyalty_points'] = [
            '#type' => 'item',
            '#title' => $this->getLoyaltyPoints($uid),
            '#weight' => 99,
          ];
        }
      }
      // Add new customer.
      else {
        $element['order_customer']['user'] = [
          '#type' => 'value',
          '#value' => 0,
        ];
        $element['order_customer']['email'] = [
          '#type' => 'email',
          '#title' => t('Email'),
          '#size' => $this->getSetting('size'),
          '#required' => TRUE,
        ];
        $element['order_customer']['pos_phone_number'] = [
          '#type' => 'tel',
          '#title' => t('Phone'),
          '#size' => $this->getSetting('size'),
        ];
      }

      $element['order_customer']['submit'] = [
        '#type' => 'button',
        '#value' => t('Set Customer'),
        '#name' => 'set-order-customer',
        '#ajax' => [
          'callback' => [$this, 'ajaxRefresh'],
          'wrapper' => $wrapper_id,
        ],
        '#limit_validation_errors' => [['uid']],
      ];
    }

    return ['target_id' => $element];
  }

}
