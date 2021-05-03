/**
 * @file
 * Handles the shipping rates recalculation in checkout.
 */
(($, Drupal, drupalSettings) => {

  Drupal.shippingRecalculate = {
    recalculateButtonSelector: '',
    submitButtonSelector: '[id^=edit-actions-next]',
    wrapper: '',
    onChange(element) {
      const waitForAjaxComplete = (element) => {
        setTimeout(() => {
          // Ensure no ajax request is in progress for the element
          // being updated before triggering the recalculation.
          if (element.is(':disabled')) {
            waitForAjaxComplete(element)
            return
          }
          if (Drupal.shippingRecalculate.canRecalculateRates()) {
            Drupal.shippingRecalculate.recalculateRates()
          }
        }, 100, element)
      };

      waitForAjaxComplete(element)
    },
    init(context) {
      // Everytime a required field value is updated, attempt to trigger the
      // shipping rates recalculation if possible.
      $(this.wrapper).find(':input.required', context).once('shipping-recalculate').on('change', ({currentTarget}) => {
        this.onChange($(currentTarget));
      });

      const $selectAddress = $(Drupal.shippingRecalculate.wrapper).find("select[name$='[shipping_profile][select_address]']");
      // When the address selection changes, check to see if we can
      // recalculate shipping rates.
      if ($selectAddress.length) {
        $selectAddress.once('shipping-recalculate').on('change', ({currentTarget}) => {
          // Wait until the ajax address update is complete.
          if ($(currentTarget).val() !== '_new') {
            this.onChange($(currentTarget));
          }
        })
      }
    },
    // Determines whether the shipping rates can be recalculated.
    canRecalculateRates() {
      let canRecalculate = true;
      $(this.wrapper).find(':input.required').each((index, element) => {
        if (!$(element).val()) {
          canRecalculate = false;
          return false;
        }
      });

      return canRecalculate;
    },
    recalculateRates() {
      // Disable the 'Continue to Review' button while recalculating.
      if ($(this.submitButtonSelector).length) {
        $(this.submitButtonSelector).prop('disabled', true)
      }
      $(this.wrapper).find(this.recalculateButtonSelector).trigger('mousedown');
    }
  };

  /**
   * Handles the shipping rates recalculation in checkout.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   */
  Drupal.behaviors.shippingRatesRecalculate = {
    attach(context) {
      Drupal.shippingRecalculate.wrapper = drupalSettings.commerceShipping.wrapper;
      Drupal.shippingRecalculate.recalculateButtonSelector = drupalSettings.commerceShipping.recalculateButtonSelector;
      Drupal.shippingRecalculate.init(context);
    }
  }

})(jQuery, Drupal, drupalSettings);
