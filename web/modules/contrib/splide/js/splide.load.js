/**
 * @file
 * Provides Splide loader.
 */

(function ($, Drupal, drupalSettings, _ds, _win, _doc) {

  'use strict';

  var _id = 'splide';
  var _idOnce = _id;
  var _mounted = 'is-mounted';
  var _element = '.' + _id + '--default:not(.' + _id + '--vanilla)';
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
     * @param {undefined|object|int} opts
     *   The options, default to index or undefined.
     *
     * @return {Splide}
     *   The Splide instance.
     */
    init: function (elm, opts) {
      if (!elm.splide) {
        elm.splide = doSplide(elm, opts);
      }
      return elm.splide;
    }

  });

  /**
   * Splide private functions.
   *
   * @param {HTMLElement} elm
   *   The .splide HTML element.
   * @param {undefined|object|int} opts
   *   The options, default to index or undefined.
   *
   * @return {Splide}
   *   The Splide instance.
   */
  function doSplide(elm, opts) {
    var d = $.attr(elm, 'data-' + _id);
    var s = $.parse(d);
    var o = $.extend({}, _defaults, s, $.isObj(opts) ? opts : {});
    var r = o.breakpoints;
    var e = _defaults.extras ? _defaults.extras : _defaults;
    var isSplide = o.lazyLoad !== 'blazy';
    var unSplide = $.hasClass(elm, 'unsplide');
    var instance;
    var b;

    if (r) {
      for (b in r) {
        if ($.hasProp(r, b)) {
          var breakpoint = r[b];
          if (!breakpoint.destroy) {
            r[b] = $.extend({}, e, breakpoint);
          }
        }
      }
    }

    if (isSplide) {
      // @todo remove, built-in lazyLoad doesn't work when SRC available.
      var imgs = $.findAll(elm, _lazyImg);
      if (imgs.length) {
        $.each(imgs, function (img) {
          $.removeAttr(img, 'src');
          // Splide has its own splide__spinner, remove Blazy one.
          $.unloading(img);
        });
      }
    }

    /**
     * The event must be bound prior to splide being mounted/ initialized.
     */
    function beforeInit() {
      _ds.initExtensions();
      _ds.initListeners(instance);
    }

    /**
     * The event must be bound after splide being mounted/ initialized.
     */
    function afterInit() {
      // Arrow down jumper.
      $.on(elm, 'click', '.' + _id + '__arrow--down', function (e) {
        e.preventDefault();
        var tg = $.attr(e.target, 'data-target');
        var el = tg ? $.find(_doc, tg) : null;
        if ($.isElm(el)) {
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
        var list = $.find(elm, '.' + _id + '__list');
        if ($.isElm(list) && list.children) {
          var len = list.children.length;

          if (len) {
            var rand = Math.floor(Math.random() * len);
            if (rand >= 0 && rand < len) {
              o.start = rand;
            }
          }
        }
      }
    }

    // Build the Splide.
    randomize();
    instance = new Splide(elm, o);

    beforeInit();

    // Main display with navigation is deferred at splide.nav.min.js.
    if (!$.hasClass(elm, _id + '--main')) {
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
    $.addClass(elm, _mounted);
    return instance;
  }

  /**
   * Attaches behavior to HTML element identified by CSS selector .splide.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.splide = {
    attach: function (context) {

      context = $.context(context);

      $.once(_ds.init, _idOnce, _element, context);
    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        $.once.removeSafely(_idOnce, _element, context);
      }
    }
  };

})(dBlazy, Drupal, drupalSettings, dSplide, this, this.document);
