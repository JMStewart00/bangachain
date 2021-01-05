"use strict";

/**
 * @file
 * Accordion icon change on click.
 */
(function ($, Drupal) {
  Drupal.behaviors.accordionIconChange = {
    attach: function attach(context) {
      $(".c-accordion-item > a", context).once("accordionIconChange").each(function (i, el) {
        var accordionItem = $(el);
        accordionItem.on("click", function () {
          var icon = accordionItem.find(".c-accordion-item__icon").children();
          icon.toggleClass("fa-minus fa-plus");
        });
      });
    }
  };
})(jQuery, Drupal);