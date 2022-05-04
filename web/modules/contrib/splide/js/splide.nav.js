/**
 * @file
 * Provides Splide loader.
 */

(function ($, Drupal, drupalSettings, _ds) {

  'use strict';

  var _id = 'splide-wrapper';
  var _idOnce = _id;
  var _mounted = 'is-sw-mounted';
  var _element = '.' + _id;

  /**
   * Splide wrapper utility functions.
   *
   * @param {HTMLElement} elm
   *   The .splide-wrapper HTML element.
   */
  function process(elm) {
    // Respects nested.
    var main = $.findAll(elm, '.splide--main');
    var nav = $.findAll(elm, '.splide--nav');
    if (!main.length || !nav.length) {
      return;
    }

    var ok = false;
    var valid = !$.isUnd(main[0]) && 'splide' in main[0];

    if (valid) {
      var splide = main[0].splide;
      var o = splide.options || {};
      var extensions = _ds.extensions || {};
      var fx = o.type ? _ds.getTransition(o.type) : null;

      if ($.isUnd(nav[0])) {
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
      $.addClass(elm, _mounted);
    }
  }

  /**
   * Attaches behavior to HTML element identified by CSS selector .splide.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.splideNav = {
    attach: function (context) {

      context = $.context(context);

      $.once(process, _idOnce, _element, context);
    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        $.once.removeSafely(_idOnce, _element, context);
      }
    }
  };

})(dBlazy, Drupal, drupalSettings, dSplide);
