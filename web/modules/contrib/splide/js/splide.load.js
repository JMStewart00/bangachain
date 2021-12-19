/**
 * @file
 * Provides Splide loader.
 */

(function (Drupal, drupalSettings, _db, _ds) {

  'use strict';

  var _splide = 'splide--default';
  var _mounted = 'is-mounted';
  var _lazyImg = 'img[data-splide-lazy]';

  /**
   * Splide public methods.
   *
   * @namespace
   */
  _ds = _db.extend(_ds || {}, {

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
    var d = elm.getAttribute('data-splide');
    var s = d ? _db.parse(d) : false;
    var o = _db.extend({}, drupalSettings.splide || {}, s || {});
    var isSplide = o.lazyLoad !== 'blazy';
    var unSplide = _db.hasClass(elm, 'unsplide');
    var instance;

    if (isSplide) {
      // @todo remove, built-in lazyLoad doesn't work when SRC available.
      var imgs = elm.querySelector(_lazyImg) === null ? [] : elm.querySelectorAll(_lazyImg);
      if (imgs.length) {
        _db.forEach(imgs, function (img) {
          img.removeAttribute('src');
          // Splide has its own splide__spinner, remove Blazy one.
          _ds.clearLoading(img);
        });
      }
    }

    /**
     * The event must be bound prior to splide being called.
     */
    function beforeSplide() {
      randomize();

      var append = function (prev, sel) {
        var el = instance.root.querySelector(sel);
        if (el !== null) {
          prev.insertAdjacentElement('afterend', el);
        }
      };

      instance.on('arrows:mounted', function (prev, next) {
        if (prev === null) {
          return;
        }

        window.setTimeout(function () {
          // Puts dots inbetween arrows for easy theming like this: < ooooo >.
          if (o.pagination === '.splide__arrows') {
            append(prev, '.splide__pagination');
          }

          // Puts arrow down inbetween arrows for easy theming like this: < v >.
          if (o.down) {
            append(prev, '.splide__arrow--down');
          }
        }, 100);
      });

      instance.on('lazyload:loaded', _ds.clearLoading);
    }

    /**
     * The event must be bound after splide being called.
     */
    function afterSplide() {
      // Arrow down jumper.
      _db.on(elm, 'click', '.splide__arrow--down', function (e) {
        e.preventDefault();
        var tg = e.target.getAttribute('data-target');
        var el = tg ? document.querySelector(tg) : null;
        if (el !== null) {
          window.scroll({
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
        var list = elm.querySelector('.splide__list');
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

    beforeSplide();

    // Main display with navigation is deferred at splide.nav.min.js.
    if (!elm.classList.contains('splide--main')) {
      var fx = o.type ? _ds.getTransition(o.type) : null;
      instance.mount(_ds.extensions || {}, fx);
    }

    afterSplide();

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

      // Context is unreliable with AJAX contents like product variations, etc.
      context = context instanceof HTMLDocument ? context : document;

      var items = context.querySelectorAll('.' + _splide + ':not(.' + _mounted + ')');
      if (items.length) {
        _db.once(_db.forEach(items, _ds.init, context));
      }

    }
  };

})(Drupal, drupalSettings, dBlazy, dSplide);
