/**
 * @file
 * Provides Splide vanilla where options are directly injected via data-splide.
 */

(function ($, Drupal, _ds) {

  'use strict';

  var _id = 'splide';
  var _idOnce = 'splide-vanilla';
  var _vid = _id + '--vanilla';
  var _mounted = 'is-sv-mounted';
  var _element = '.' + _vid + ':not(.' + _id + '--default)';

  /**
   * Splide utility functions.
   *
   * @param {HTMLElement} elm
   *   The .splide--vanilla HTML element.
   */
  function process(elm) {
    var track = $.find(elm, '.splide__track');

    // Prevents theme_item_list() CSS rules from screwing up.
    if ($.isElm(track)) {
      $.removeClass(track, 'item-list');
    }

    var instance = new Splide(elm);
    _ds.initExtensions();
    _ds.initListeners(instance);

    // Main display with navigation is deferred at splide.nav.min.js.
    if (!$.hasClass(elm, 'splide--main')) {
      instance.mount(_ds.extensions || {});
    }

    // Saves instance in DOM to sync navigations without re-instantiation.
    if (!elm.splide) {
      elm.splide = instance;
    }

    $.addClass(elm, _mounted);
  }

  /**
   * Attaches splide behavior to HTML element identified by .splide--vanilla.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.splideVanilla = {
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

})(dBlazy, Drupal, dSplide);
