/**
 * @file
 * Provides Splide loader.
 */

(function ($, Drupal, drupalSettings, _ds) {

  'use strict';

  var _id = 'splide-wrapper';
  var _mounted = 'is-sw-mounted';
  var _element = '.' + _id + ':not(.' + _mounted + ')';

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
    if (!main.length || !nav.length) {
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

      // @todo replace by dBlazy.context post Blazy:2.6+.
      context = _ds.context(context);

      var elms = context.querySelectorAll(_element);
      if (elms.length) {
        $.once($.forEach(elms, doWrapper));
      }
    }
  };

})(dBlazy, Drupal, drupalSettings, dSplide);
