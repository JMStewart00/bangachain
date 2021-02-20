"use strict";
/**
 * @file
 * Control the behavior of the search takeover flyout.
 */

(function ($, Drupal) {
  Drupal.behaviors.searchFlyout = {
    attach: function attach() {
      $("#search").once("searchFlyout").submit(function (e) {
        e.preventDefault();
        var q = encodeURI($("#searchInput").val());
        window.location.href = "/search?query=".concat(q);
      });
    }
  };
})(jQuery, Drupal);