/**
 * @file
 * Provides Colorbox integration.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Splide Colorbox utility functions.
   *
   * @namespace
   */
  Drupal.splideColorbox = Drupal.splideColorbox || {

    /**
     * Reacts on Colorbox events.
     *
     * @name set
     *
     * @param {string} method
     *   The method to react on.
     */
    set: function (method) {
      var $box = $.colorbox.element();
      var $slider = $box.closest('.splide');
      var $clone = $slider.find('.splide__slide--clone .litebox');
      var total = $slider.find('.splide__slide:not(.splide__slide--clone) .litebox').length;
      var $counter = $('#cboxCurrent');
      var curr;

      if (!$slider.length) {
        return;
      }

      // Fixed for unwanted clones with type == 'loop'.
      // This basically tells Colorbox to not count/ process clones.
      var attach = function (attach) {
        if ($clone.length) {
          $clone.each(function (i, box) {
            $(box)[attach ? 'addClass' : 'removeClass']('cboxElement');
            Drupal[attach ? 'attachBehaviors' : 'detachBehaviors'](box);
          });
        }
      };

      curr = Math.abs($box.closest('.splide__slide').data('delta'));
      if (isNaN(curr)) {
        curr = 0;
      }

      if (method === 'cbox_load') {
        attach(false);
      }
      else if (method === 'cbox_complete') {
        // Actually only needed at first launch, but no first launch event.
        if ($counter.length) {
          var current = drupalSettings.colorbox.current || false;
          if (current) {
            current = current.replace('{current}', (curr + 1)).replace('{total}', total);
          }
          else {
            current = Drupal.t('@curr of @total', {
              '@curr': (curr + 1),
              '@total': total
            });
          }
          $counter.text(current);
        }
      }
      else if (method === 'cbox_closed') {
        // @fixme, randomly weird messed up DOM (blank slides) after closing.
        window.setTimeout(function () {
          attach(true);
        }, 10);
      }
    }
  };

  /**
   * Adds each slide a reliable ordinal to get correct current with clones.
   *
   * @param {int} i
   *   The index of the current element.
   * @param {HTMLElement} elm
   *   The splide HTML element.
   */
  function doSplideColorbox(i, elm) {
    $('.splide__slide:not(.splide__slide--clone)', elm).each(function (j, el) {
      $(el).attr('data-delta', j);
    });
  }

  /**
   * Attaches splide behavior to HTML element identified by .splide--colorbox.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.splideColorbox = {
    attach: function (context) {
      var me = Drupal.splideColorbox;

      $(context).on('cbox_load', function () {
        me.set('cbox_load');
      });

      $(context).on('cbox_complete', function () {
        me.set('cbox_complete');
      });

      $(context).on('cbox_closed', function () {
        me.set('cbox_closed');
      });

      $('.splide.is-colorbox', context).once('splide-colorbox').each(doSplideColorbox);
    }
  };

}(jQuery, Drupal));
