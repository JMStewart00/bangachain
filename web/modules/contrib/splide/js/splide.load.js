/**
 * @file
 * Provides Splide loader.
 */

(function ($, Drupal, drupalSettings, _ds, _win) {

  'use strict';

  var _id = 'splide';
  var _mounted = 'is-mounted';
  var _element = '.' + _id + '--default:not(.' + _mounted + '):not(.' + _id + '--vanilla)';
  var _lazyImg = 'img[data-' + _id + '-lazy]';
  var _defaults = drupalSettings.splide || {};

  /**
   * Splide public methods.
   *
   * @namespace
   */
  _ds = $.extend(_ds || {}, {

    /**
     * Initializes the Splide.
     *
     * Saves instance in DOM to sync navigations without re-instantiation.
     *
     * @param {HTMLElement} elm
     *   The .splide HTML element.
     *
     * @return {Splide}
     *   The Splide instance.
     */
    init: function (elm) {
      if (!elm.splide) {
        elm.splide = doSplide(elm);
      }
      return elm.splide;
    }

  });

  /**
   * Splide private functions.
   *
   * @param {HTMLElement} elm
   *   The .splide HTML element.
   *
   * @return {Splide}
   *   The Splide instance.
   */
  function doSplide(elm) {
    var d = elm.getAttribute('data-' + _id);
    var s = d ? $.parse(d) : false;
    var o = $.extend({}, _defaults, s || {});
    var r = o.breakpoints;
    var e = _defaults.extras ? _defaults.extras : _defaults;
    var isSplide = o.lazyLoad !== 'blazy';
    var unSplide = $.hasClass(elm, 'unsplide');
    var instance;
    var b;

    if (r) {
      for (b in r) {
        if (Object.prototype.hasOwnProperty.call(r, b)) {
          var breakpoint = r[b];
          if (!breakpoint.destroy) {
            r[b] = $.extend({}, e, breakpoint);
          }
        }
      }
    }

    if (isSplide) {
      // @todo remove, built-in lazyLoad doesn't work when SRC available.
      var imgs = elm.querySelector(_lazyImg) === null ? [] : elm.querySelectorAll(_lazyImg);
      if (imgs.length) {
        $.forEach(imgs, function (img) {
          img.removeAttribute('src');
          // Splide has its own splide__spinner, remove Blazy one.
          _ds.unloading(img);
        });
      }
    }

    /**
     * The event must be bound prior to splide being mounted/ initialized.
     */
    function beforeInit() {
      _ds.initExtensions();
      _ds.initListeners(instance);

      randomize();
    }

    /**
     * The event must be bound after splide being mounted/ initialized.
     */
    function afterInit() {
      // Arrow down jumper.
      $.on(elm, 'click', '.' + _id + '__arrow--down', function (e) {
        e.preventDefault();
        var tg = e.target.getAttribute('data-target');
        var el = tg ? document.querySelector(tg) : null;
        if (el !== null) {
          _win.scroll({
            top: el.offsetTop,
            behavior: 'smooth'
          });
        }
      });
    }

    /**
     * Randomize slide orders, for ads/products rotation within cached blocks.
     */
    function randomize() {
      if (o.randomize) {
        var list = elm.querySelector('.' + _id + '__list');
        if (list !== null && list.children !== null) {
          var len = list.children.length;

          if (len) {
            var rand = Math.floor(Math.random() * len);
            if (s && (rand >= 0 && rand < len)) {
              o.start = s.start = rand;
              elm.dataset.splide = JSON.stringify(s);
            }
          }
        }
      }
    }

    // Build the Splide.
    instance = new Splide(elm, o);

    beforeInit();

    // Main display with navigation is deferred at splide.nav.min.js.
    if (!elm.classList.contains(_id + '--main')) {
      var fx = o.type ? _ds.getTransition(o.type) : null;
      instance.mount(_ds.extensions || {}, fx);
    }

    afterInit();

    // Destroy Splide if it is an enforced unsplide.
    // This allows Splide lazyload to run, but prevents further complication.
    // Should use lazyLoaded event, but images are not always there.
    if (unSplide) {
      instance.destroy(true);
    }

    // Add helper class for arrow visibility as they are outside slider.
    elm.classList.add(_mounted);
    return instance;
  }

  /**
   * Attaches behavior to HTML element identified by CSS selector .splide.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.splide = {
    attach: function (context) {

      // @todo replace by dBlazy.context post Blazy:2.6+.
      context = _ds.context(context);

      var elms = context.querySelectorAll(_element);
      if (elms.length) {
        $.once($.forEach(elms, _ds.init));
      }
    }
  };

})(dBlazy, Drupal, drupalSettings, dSplide, this);
