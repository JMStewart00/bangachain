/**
 * @file
 * Contain code for header elements
 */

(function ($, Drupal) {
  'use strict';

  // Configure header Category block.
  Drupal.behaviors.estoreHeaderCategoryMenu = {
    attach: function (context) {
      var $categoryMenu = $('header.navbar .category-menu', context);

      $('.block-title', $categoryMenu).on('click', function () {
        $('ul.menu', $categoryMenu).slideToggle('slow');
        $(this).toggleClass('active');

        return false;
      });
    }
  };

  // Configure header search block.
  Drupal.behaviors.estoreHeaderSearchBlock = {
    attach: function (context) {
      var $searchBlock = $('header.navbar .block-search-navigation', context);

      $('.search-icon', $searchBlock).on('click', function () {
        $('.search-contents', $searchBlock).toggle();
        $(this).toggleClass('active');

        return false;
      });
    }
  };

  // Configure main menu for mobile screen.
  Drupal.behaviors.estoreHeaderMainMenu = {
    attach: function (context) {
      var $mainMenuWrapp = $('nav.main-menu', context);
      var $toggleEl = $('.toggle', $mainMenuWrapp);
      var $mainMenu = $('ul.menu', $mainMenuWrapp);

      $toggleEl.on('click', function () {
        $mainMenu.slideToggle('slow');
      });
    }
  };

})(jQuery, Drupal);
