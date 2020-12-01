"use strict";

/**
 * @file
 * Accordion icon change on click.
 */
(function ($, Drupal) {
  Drupal.behaviors.defaultImagePlacement = {
    attach: function attach(context) {
      $('body', context).once("defaultImagePlacement").on('DOMSubtreeModified', '.c-product__image', function () {
        console.log('changed');
      });
    }
  };
})(jQuery, Drupal);