/**
 * @file
 * Defines behaviors for the Square payment method form.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  var commerceSquare;

  /**
   * Attaches the commerceSquareForm behavior.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the commerceSquareForm behavior.
   *
   * @see Drupal.commerceSquare
   */
  Drupal.behaviors.commerceSquareForm = {
    attach: function (context) {
      var settings = drupalSettings.commerceSquare;
      if (context === document) {
        var script = document.createElement('script');
        var scriptHostname = settings.apiMode === 'sandbox' ? 'squareupsandbox' : 'squareup';
        script.src = 'https://js.' + scriptHostname + '.com/v2/paymentform';
        script.type = 'text/javascript';
        document.getElementsByTagName('head')[0].appendChild(script);
      }

      var $form = $('.square-form', context).closest('form').once('square-attach');
      if ($form.length === 0) {
        return;
      }
      var waitForSdk = setInterval(function () {
        if (typeof SqPaymentForm !== 'undefined') {
          commerceSquare = new Drupal.commerceSquare($form, drupalSettings.commerceSquare);
          $form.data('square', commerceSquare);
          clearInterval(waitForSdk);
        }
      }, 100);
    },
    detach: function (context, settings, trigger) {
      // Detaching on the wrong trigger will clear the Square form
      // on #ajax (after changing the address country, for example).
      if (trigger !== 'unload') {
        return;
      }
      var $form = $('.square-form', context).closest('form');
      if ($form.length === 0) {
        return;
      }

      $form.closest('form').find('[name="op"]').prop('disabled', false);
      $form.removeData('square');
      $form.removeOnce('square-attach');
      var $formSubmit = $form.find('[name="op"]');
      $formSubmit.off("click.squareNonce");
    }
  };

  /**
   * Wraps the SqPaymentForm object with Commerce-specific logic.
   *
   * @constructor
   */
  Drupal.commerceSquare = function ($squareForm, settings) {
    var $parentDrupalSelector = $squareForm.find('[data-drupal-selector="' + settings.drupalSelector + '"]');
    var $formSubmit = $squareForm.find(':input.button--primary');
    $formSubmit.prop('disabled', true);
    $formSubmit.click(function () {
      $squareForm.find('.messages--error').remove();
    });
    $formSubmit.on("click.squareNonce", requestCardNonce);

    var paymentForm = new SqPaymentForm({
      applicationId: settings.applicationId,
      inputClass: 'sq-input',
      inputStyles: [
        {
          fontSize: '15px'
        }
      ],
      cardNumber: {
        elementId: 'square-card-number',
        placeholder: '•••• •••• •••• ••••'
      },
      cvv: {
        elementId: 'square-cvv',
        placeholder: 'CVV'
      },
      expirationDate: {
        elementId: 'square-expiration-date',
        placeholder: 'MM/YY'
      },
      postalCode: {
        elementId: 'square-postal-code'
      },
      callbacks: {
        // Called when the SqPaymentForm completes a request to generate a card
        // nonce, even if the request failed because of an error.
        cardNonceResponseReceived: function (errors, nonce, cardData) {
          if (errors) {
            errors.forEach(function (error) {
              $squareForm.prepend(Drupal.theme('commerceSquareError', error.message));
            });
          }
          // No errors occurred. Extract the card nonce.
          else {
            $squareForm.find('.square-nonce').val(nonce);
            $squareForm.find('.square-card-type').val(cardData.card_brand);
            $squareForm.find('.square-last4').val(cardData.last_4);
            $squareForm.find('.square-exp-month').val(cardData.exp_month);
            $squareForm.find('.square-exp-year').val(cardData.exp_year);
            $squareForm.append('<input type="hidden" name="_triggering_element_name" value="' + $formSubmit.attr('name') + '" />');
            $squareForm.append('<input type="hidden" name="_triggering_element_value" value="' + $formSubmit.val() + '" />');
            $squareForm.submit();
          }
        },

        unsupportedBrowserDetected: function () {
          // Fill in this callback to alert buyers when their browser is not supported.
        },

        // Fill in these cases to respond to various events that can occur while a
        // buyer is using the payment form.
        inputEventReceived: function (inputEvent) {
          switch (inputEvent.eventType) {
            case 'focusClassAdded':
              // Handle as desired.
              break;

            case 'focusClassRemoved':
              // Handle as desired.
              break;

            case 'errorClassAdded':
              // Handle as desired.
              break;

            case 'errorClassRemoved':
              // Handle as desired.
              break;

            case 'cardBrandChanged':
              // Handle as desired.
              break;

            case 'postalCodeChanged':
              // Handle as desired.
              break;
          }
        },

        paymentFormLoaded: function () {
          // @todo allow for people to extend and hook in.
          $formSubmit.prop('disabled', false);
        }
      }
    });
    paymentForm.build();

    // This function is called when a buyer clicks the Submit button on the webpage
    // to charge their card.
    function requestCardNonce(event) {
      // This prevents the Submit button from submitting its associated form.
      // Instead, clicking the Submit button should tell the SqPaymentForm to generate
      // a card nonce, which the next line does.
      event.preventDefault();

      // Grab postal code.
      if (typeof drupalSettings.commerceSquare.customerPostalCode !== 'undefined') {
        paymentForm.setPostalCode(drupalSettings.commerceSquare.customerPostalCode);
      }
      else {
        paymentForm.setPostalCode($parentDrupalSelector.parent().find('input.postal-code').val());
      }

      paymentForm.requestCardNonce();
    }

    return this;
  };

  $.extend(Drupal.theme, /** @lends Drupal.theme */{
    commerceSquareError: function (message) {
      return $('<div role="alert"><div class="messages messages--error">' + message + '</div></div>');
    }
  });

})(jQuery, Drupal, drupalSettings);
