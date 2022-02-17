/**
 * @file
 * Provides Splide vanilla where options are directly injected via data-splide.
 */

(function ($, Drupal, _ds) {

  'use strict';

  var _id = 'splide';
  var _vid = _id + '--vanilla';
  var _mounted = 'is-sv-mounted';
  var _element = '.' + _vid + ':not(.' + _mounted + '):not(.' + _id + '--default)';

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
    _ds.initExtensions();
    _ds.initListeners(instance);

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

      // @todo replace by dBlazy.context post Blazy:2.6+.
      context = _ds.context(context);

      var elms = context.querySelectorAll(_element);
      if (elms.length) {
        $.once($.forEach(elms, doVanilla));
      }
    }
  };

})(dBlazy, Drupal, dSplide);
