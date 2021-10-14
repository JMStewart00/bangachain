<?php

namespace Drupal\commerce_pos_keypad\Render\Element;

use Drupal\commerce_pos_currency_denominations\Entity\CurrencyDenominations;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * The Commerce POS Keypad preRender class file.
 *
 * @package Drupal\commerce_pos_keypad\Render\Element
 */

class KeypadPreRender implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRenderKeypad'];
  }

  /**
   * Attach JS and CSS to elements as needed.
   */
  public static function preRenderKeypad($element) {
    // Only preprocess items with #commerce_pos_keypad set.
    if (empty($element['#commerce_pos_keypad'])) {
      return $element;
    }

    $element['#attached']['library'][] = 'commerce_pos_keypad/keypad';
    $element['#attributes']['class'][] = 'commerce-pos-keypad-keypad';

    // Allow 'type' to be optional by providing a default.
    if (isset($element['#commerce_pos_keypad']['type'])) {
      switch ($element['#commerce_pos_keypad']['type']) {
        case 'cash input':
          return self::preRenderCashInput($element);

        case 'keypad':
          return self::preRenderKeypadInput($element);
      }
    }

    return self::preRenderKeypadInput($element);
  }

  /**
   * Alter a form element to add a cash input box to it.
   *
   * @param array $element
   *   The form element to alter.
   *
   * @return array
   *   The altered element.
   */
  private static function preRenderCashInput(array $element) {
    /** @var \Drupal\commerce_store\CurrentStore $current_store */
    $current_store = \Drupal::getContainer()->get('commerce_store.current_store');
    $default_currency_code = $current_store->getStore()->getDefaultCurrencyCode();

    // Fetch all the available denominations.
    $denominations = CurrencyDenominations::loadMultiple();

    if (!empty($denominations) && isset($denominations[$default_currency_code])) {
      $themed_input_box = [
        '#theme' => 'commerce_pos_keypad_cash_input_box',
        '#denominations' => $denominations[$default_currency_code],
        '#currency_code' => isset($element['#commerce_pos_keypad']['currency_code']) ? $element['#commerce_pos_keypad']['currency_code'] : $default_currency_code,
      ];

      $js['commerce_pos_keypad']['commerce_pos_keypad']['commercePosKeypadKeypad']['commercePosKeypadCashInput'] = [
        'inputBox' => render($themed_input_box),
      ];

      $element['#attached']['drupalSettings'] = $js;

      $element['#attributes']['class'][] = 'commerce-pos-keypad-cash-input';
    }

    return $element;
  }

  /**
   * Alter a form element to add a keypad to it.
   *
   * @param array $element
   *   The form element to alter.
   *
   * @return array
   *   The altered element.
   */
  private static function preRenderKeypadInput(array $element) {
    // Get the themed html for the input box.
    $input_box['input_box'] = [
      '#theme' => 'commerce_pos_keypad_keypad',
      '#input_type' => ($element['#type'] == 'password') ? 'password' : 'text',
    ];
    $input_box = render($input_box);

    $js['commerce_pos_keypad']['commerce_pos_keypad']['commercePosKeypadKeypad'] = [
      'inputBox' => $input_box,
    ];

    if (isset($element['#commerce_pos_keypad']['type']) && $element['#commerce_pos_keypad']['type'] == "icon") {
      $js['commerce_pos_keypad']['commerce_pos_keypad']['commercePosKeypadIcon'] = TRUE;
    }

    // If events were declared, add them to the settings.
    if (isset($element['#commerce_pos_keypad']['events'])) {
      foreach ($element['#commerce_pos_keypad']['events'] as $selector => $events) {
        foreach ($events as $event_name => $event_properties) {
          $js['commerce_pos_keypad']['commerce_pos_keypad']['commercePosKeypadKeypad']['events'][] = [
            'selector' => $selector,
            'name' => $event_name,
            'properties' => $event_properties,
          ];
        }
      }
    }
    else {
      // Add default "blur" event.
      $js['commerce_pos_keypad']['commerce_pos_keypad']['commercePosKeypadKeypad']['events'][] = [
        'name' => 'blur',
        'properties' => [],
      ];
    }

    $element['#attached']['drupalSettings'] = $js;

    $element['#attributes']['class'][] = 'commerce-pos-keypad-keypad';

    return $element;
  }
}
