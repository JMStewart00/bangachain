/**
 * @file
 * commerce_pos UI behaviors.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  /**
   * Order Item input:
   * Chooses first autocomplete option when 'Enter' is pressed.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the condition summaries.
   */
  Drupal.behaviors.commercePosOrderItemQuickAdd = {
    attach: function (context, settings) {
      $('input.form-autocomplete')
        .keypress(function (event) {
            if (event.which == 13) {
              $(this).trigger("autocompleteclose");
            }
          }
        );
    }
  };

  /**
   * Order Item input:
   * Auto highlight quantity field when clicked.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the condition summaries.
   */
  Drupal.behaviors.commercePosOrderItemQuantity = {
    attach: function (context) {
      $(context).find('input.commerce-pos-order-item-quantity')
        .once('addOnClick')
        .each(function () {
          var _this = $(this);
          _this.on('click', function () {
            _this.select();
          });
        });
    }
  };

  /**
   * Override to stop cursor from being reset to 0 once autocomplete dropdown
   * closes when no match is found.
   *
   * @type {{attach: attach}}
   */
  Drupal.behaviors.commercePosCursorReset = {
    attach: function (context, settings) {
      var $input = $(context).find('input.form-autocomplete');
      $input.once().each(function () {

        if ($input.val()) {
          // Some browsers are inconsistent about .length values.
          $input[0].setSelectionRange(Number.MAX_SAFE_INTEGER, Number.MAX_SAFE_INTEGER)
        }
      });
    }
  };
}(jQuery, Drupal, drupalSettings));
