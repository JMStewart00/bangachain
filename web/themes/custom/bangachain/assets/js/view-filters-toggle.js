"use strict";

/**
 * @file
 * Accordion icon change on click.
 */
(function ($, Drupal) {
  Drupal.behaviors.viewFiltersToggle = {
    attach: function attach(context) {
      $(".c-view-filters__toggle > button", context).once("viewFiltersToggle").each(function (i, el) {
        var buttonToggle = $(el);
        buttonToggle.on("click", function () {
          console.log('click BITCH!');
          var filters = $('.l-grid__sidebar').find(".c-view-filters");
          filters.toggleClass("open");
        });
      });
    }
  };
})(jQuery, Drupal);