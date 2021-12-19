/**
 * @file
 * Provides Splide vanilla where options are directly injected via data-splide.
 */

(function (Drupal, _db, _ds) {

  'use strict';

  var _splide = 'splide--vanilla';
  var _mounted = 'is-mounted';

  /**
   * Splide utility functions.
   *
   * @param {HTMLElement} elm
   *   The .splide--vanilla HTML element.
   */
  function doVanilla(elm) {
    var track = elm.querySelector('.splide__track');

    // Prevents theme_item_list() CSS rules from screwing up.
    if (track !== null) {
      track.classList.remove('item-list');
    }

    var instance = new Splide(elm);

    // Main display with navigation is deferred at splide.nav.min.js.
    if (!elm.classList.contains('splide--main')) {
      instance.mount(_ds.extensions || {});
    }

    // Saves instance in DOM to sync navigations without re-instantiation.
    if (!elm.splide) {
      elm.splide = instance;
    }

    elm.classList.add(_mounted);
  }

  /**
   * Attaches splide behavior to HTML element identified by .splide--vanilla.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.splideVanilla = {
    attach: function (context) {

      // Context is unreliable with AJAX contents like product variations, etc.
      context = context instanceof HTMLDocument ? context : document;

      var items = context.querySelectorAll('.' + _splide + ':not(.splide--default)');
      if (items.length) {
        _db.once(_db.forEach(items, doVanilla, context));
      }

    }
  };

})(Drupal, dBlazy, dSplide);
