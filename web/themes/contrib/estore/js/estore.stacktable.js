
/**
 * @file
 * Contain configurations for apply stacktable.js.
 */

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.estoreStacktable = {
    attach: function (context) {
      // Table in header block cart.
      $('.cart-block-wrapper table', context).cardtable(
        {myClass: 'cardtable-in'}
      );

      // Table in the /cart page.
      $('.views-view-commerce_cart_form-default table', context).stacktable(
        {myClass: 'stacktable-in'}
      );
    }
  };

})(jQuery, Drupal);
