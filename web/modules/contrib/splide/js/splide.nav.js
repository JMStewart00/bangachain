/**
 * @file
 * Provides Splide loader.
 */

(function (Drupal, drupalSettings, _db, _ds) {

  'use strict';

  var _wrapper = 'splide-wrapper';
  var _mounted = 'is-mounted';

  /**
   * Splide wrapper utility functions.
   *
   * @param {HTMLElement} elm
   *   The .splide-wrapper HTML element.
   */
  function doWrapper(elm) {
    // Respects nested.
    var main = elm.querySelectorAll('.splide--main');
    var nav = elm.querySelectorAll('.splide--nav');
    if (main === null || nav === null) {
      return;
    }

    var ok = false;
    var valid = typeof main[0] !== 'undefined' && 'splide' in main[0];

    if (valid) {
      var splide = main[0].splide;
      var o = splide.options || {};
      var extensions = _ds.extensions || {};
      var fx = o.type ? _ds.getTransition(o.type) : null;

      if (typeof nav[0] === 'undefined') {
        splide.mount(extensions, fx);
      }
      else {
        if ('splide' in nav[0]) {
          ok = true;
          splide.sync(nav[0].splide).mount(extensions, fx);
        }
      }
    }

    // Ensures sitewide option with improper synching doesn't screw up.
    if (ok) {
      elm.classList.add(_mounted);
    }
  }

  /**
   * Attaches behavior to HTML element identified by CSS selector .splide.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.splideNav = {
    attach: function (context) {

      // Context is unreliable with AJAX contents like product variations, etc.
      context = context instanceof HTMLDocument ? context : document;

      var items = context.querySelectorAll('.' + _wrapper + ':not(.' + _mounted + ')');
      if (items.length) {
        _db.once(_db.forEach(items, doWrapper, context));
      }

    }
  };

})(Drupal, drupalSettings, dBlazy, dSplide);
