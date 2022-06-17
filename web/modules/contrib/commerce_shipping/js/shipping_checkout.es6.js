/**
 * @file
 * Handles the shipping rates recalculation in checkout.
 */
((Drupal, drupalSettings, once) => {

  Drupal.shippingRecalculate = {
    recalculateButtonSelector: '',
    submitButtonSelector: '[id^=edit-actions-next]',
    wrapper: '',
    onChange(element) {
      const waitForAjaxComplete = (element) => {
        setTimeout(() => {
          // Ensure no ajax request is in progress for the element
          // being updated before triggering the recalculation.
          if (element.disabled) {
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
      const requiredInputs = document.getElementById(this.wrapper).querySelectorAll('input[required], select[required], input[type=checkbox]');
      if (requiredInputs.length) {
        once('shipping-recalculate', requiredInputs, context).forEach((element) => {
          element.addEventListener('change', (el) => {
            this.onChange(el.target);
          });
        });
      }
    },
    // Determines whether the shipping rates can be recalculated.
    canRecalculateRates() {
      let canRecalculate = true;
      const requiredInputs = document.getElementById(this.wrapper).querySelectorAll('input[required], select[required]');
      Array.prototype.forEach.call(requiredInputs, function(el) {
        if (!el.value) {
          canRecalculate = false;
          return false;
        }
      });

      return canRecalculate;
    },
    recalculateRates() {
      const buttons = document.querySelectorAll(this.submitButtonSelector);
      // Disable the 'Continue to Review' button while recalculating.
      if (buttons.length) {
        buttons[0].disabled = true;
      }
      document.getElementById(this.wrapper).querySelector(this.recalculateButtonSelector).dispatchEvent(new Event('mousedown'));
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

})(Drupal, drupalSettings, once);
