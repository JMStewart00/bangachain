/**
 * @file
 * Provides CSS3 Native Grid treated as Masonry based on Grid Layout.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Grid_Layout
 * The two-dimensional Native Grid does not use JS until treated as a Masonry.
 * If you need GridStack kind, avoid inputting numeric value for Grid.
 * Below is the cheap version of GridStack.
 */

(function ($, Drupal, _win) {

  'use strict';

  var _id = 'block-nativegrid';
  var _masonry = 'is-b-masonry';
  var _mounted = _masonry + '--on';
  var _element = '.' + _id + '.' + _masonry + ':not(.' + _mounted + ')';

  Drupal.blazy = Drupal.blazy || {};

  var _opts = {
    gap: 15,
    height: 15,
    rows: 10
  };

  /**
   * Applies the correct span to each grid item.
   *
   * @param {HTMLElement|Event} el
   *   The item HTML element, or event object on blazy.done.
   * @param {in|undefiued} i
   *   The element index, or undefined for a resize event.
   */
  function processItem(el, i) {
    var target = el.target;
    var box = 'target' in el ? $.closest(target, '.grid') : el;

    if (!$.isElm(box)) {
      return;
    }

    var cn = $.find(box, '.grid__content');

    if ($.isElm(cn)) {
      if (_opts.gap === 0) {
        _opts.gap = 0.0001;
      }

      // Once setup, we rely on CSS to make it responsive.
      var layout = function () {
        var rect = $.rect(cn);
        var span = Math.ceil((rect.height + _opts.gap) / (_opts.height + _opts.gap));

        // Sets the grid row span based on content and gap height.
        box.style.gridRowEnd = 'span ' + span;

        $.addClass(box, 'is-b-grid');
      };

      if ($.isUnd(i)) {
        _win.setTimeout(layout, 200);
      }
      else {
        layout();
      }
    }
  }

  /**
   * Applies grid row end to each grid item.
   *
   * @param {HTMLElement} elm
   *   The container HTML element.
   */
  function process(elm) {
    var selector = '.grid:not(.is-b-grid)';
    // The is-b-grid is flag to not re-do with VIS, views infinite scroll/ IO.
    var items = $.findAll(elm, selector);

    var init = function () {
      var style = $.computeStyle(elm);
      var gap = style.getPropertyValue('grid-row-gap');
      var rows = style.getPropertyValue('grid-auto-rows');

      if (gap) {
        _opts.gap = parseInt(gap, 10);
      }
      if (rows) {
        _opts.height = parseInt(rows, 10);
      }

      if (items.length) {
        // Process on page load.
        $.each(items, processItem);

        // Process on resize.
        Drupal.blazy.checkResize(items, processItem, elm, processItem);
      }
    };

    _win.setTimeout(init, 100);
    $.addClass(elm, _mounted);
  }

  /**
   * Attaches Blazy behavior to HTML element identified by .block-nativegrid.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyNativeGrid = {
    attach: function (context) {

      context = $.context(context);

      $.once(process, _element, context);
    }
  };

}(dBlazy, Drupal, this));
