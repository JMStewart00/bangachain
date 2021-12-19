/**
 * @file
 * Provides Splide extensions for Blazy.
 */

(function (Drupal, _db, _ds) {

  'use strict';

  var Blazy = function (Splide, Components) {
    var root = Splide.root;

    return {
      mount: function () {
        var me = this;

        Splide.on('mounted.spb', function (e) {
          me.preload(false);
        });

        Splide.on('move.spb', function (e) {
          me.preload(true);
        });

      },

      /**
       * Blazy is not loaded on perPage > 1 with type `loop`/ infinite, reload.
       *
       * @param {bool} ahead
       *   Whether to lazyload ahead (at move/ beforeChange event), or not.
       */
      preload: function (ahead) {
        if (!('blazy' in Drupal)) {
          return;
        }

        window.setTimeout(function () {
          var blazy = '.b-lazy:not(.b-loaded)';
          var visible = '.is-visible ' + blazy;
          if (root.querySelector(blazy) !== null) {
            var els = root.querySelectorAll(ahead ? blazy : visible);
            if (els.length) {
              Drupal.blazy.init.load(els);
            }
          }

          // Cleans up preloader if any named b-loader due to clones.
          var preloader = root.querySelector('.b-loaded ~ .b-loader');
          if (preloader !== null && preloader.parentNode !== null) {
            preloader.parentNode.removeChild(preloader);
          }
        }, 100);
      }
    };
  };

  _ds.extend({
    Blazy: Blazy
  });

})(Drupal, dBlazy, dSplide);
