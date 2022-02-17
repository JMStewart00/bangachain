/**
 * @file
 * Provides compat methods between Native and lazyload script.
 *
 * This file is not loaded if all below are not enabled.
 *
 * Mostly to fix for lost module features due to lazyload script being ditched:
 *   - Blur or animation in general with animate.css.
 *   - Multiple-breakpoint CSS background (DIV).
 *   - Multiple-breakpoint dynamic, or named Fluid, aspect ratio.
 *   - Local video.
 *   - Extra features: sub-module requirements. If using Slick/ Splide, etc., be
 *     sure to disable loading their loaders globally at their UIs as needed.
 */

(function ($, Drupal, _win) {

  'use strict';

  var _id = 'blazy';
  var _data = 'data-';
  var _dataAnimation = _data + 'animation';
  var _dataDimensions = _data + 'dimensions';
  var _dataRatio = _data + 'ratio';
  var _media = 'media';
  var _picture = 'picture';
  var _elMedia = '.' + _media;
  var _elRatio = _elMedia + '--ratio';
  var _isAnimated = 'is-b-animated';
  var _winData = {};
  var _opts = {};

  /**
   * Blazy public compat methods.
   *
   * @namespace
   */
  Drupal.blazy = $.extend(Drupal.blazy || {}, {

    clearCompat: function (el) {
      var me = this;
      var old = $.isBg(el) && (me.isBlazy() || $.ie);

      // Compatibility with old bLazy. Moved into fork bLazy.
      // if (old) {
      // bio.setImage(el, true);
      // }

      // Only animate when the image is fully loaded, else nonsense.
      me.pad(el, animate, old ? 50 : 0);
    },

    checkResize: function (items, cb, root, onDone) {
      var me = this;
      var bio = me.init;
      var check = function (e) {
        var details = e && e.detail ? e.detail : {};
        me.resizeTick = bio && bio.resizeTick || 0;

        _winData = details.winData || me.windowData();

        if ($.isFun(cb)) {
          $.each(items, function (entry) {
            var el = entry.target || entry;

            return cb.call(me, el);
          });
        }
      };

      // Already throttled for oldies, or RO/RAF for modern browsers.
      $.on(_win, _id + '.resizing', check);

      // When images are loaded, Flexbox or Native Grid as Masonry might need
      // info about the loaded image dimensions to calculate gaps or positions.
      if (onDone && $.isFun(onDone)) {
        me.rebind(root, onDone, me.roObserver);
      }

      me.destroyed = false;
      return _winData;
    },

    unresize: function () {
      $.unload(this);
    }
  });

  // Private non-reusable functions.
  /**
   * Callback function to animate blur, or any animated, element, if any.
   *
   * @param {Element} el
   *   The DIV or image element.
   */
  function animate(el) {
    // Blur, animate.css, for CSS background, picture, image, media.
    var an = $.closest(el, '[' + _dataAnimation + ']');
    if ($.hasAttr(el, _dataAnimation) && !$.isElm(an)) {
      an = el;
    }

    // Animate if any.
    if ($.isElm(an) && !$.hasClass(an, _isAnimated)) {
      setTimeout(function () {
        $.animate(an);
      }, 100);
    }
  }

  /**
   * Updates the dynamic multi-breakpoint aspect ratio: bg, picture or image.
   *
   * Even Native needs help since browsers do not auto-update dynamic ratio.
   *
   * This only applies to Responsive images with aspect ratio fluid.
   * Static ratio (media--ratio--169, etc.) is ignored and uses CSS instead.
   *
   * @param {Element} cn
   *   The .media--ratio[--fluid] container HTML element.
   *
   * @todo this should be at bio.js, but bLazy has no support which prevents it.
   * Unless made generic for a ping-pong.
   */
  function updateRatio(cn) {
    cn = cn.target || cn;

    if (!$.isElm(cn)) {
      return;
    }

    var me = this;
    // Blazy container (via formatter or Views style) is not always there.
    var root = $.closest(cn, '.' + _id);
    var dimensions = $.parse($.attr(cn, _dataDimensions));
    var isResized = me.resizeTick > 0;

    // Bail out if a static/ non-fluid aspect ratio.
    if (!dimensions) {
      fallbackRatio(cn);
      return;
    }

    // For picture, this is more a dummy space till the image is downloaded.
    var isPicture = $.isElm($.find(cn, _picture)) && isResized;
    var data = $.extend(_winData, {
      up: isPicture
    });
    var pad = $.activeWidth(dimensions, data);

    // Provides marker for grouping between multiple instances.
    cn.dblazy = $.isElm(root) && root.dblazy;
    if (!$.isUnd(pad)) {
      cn.style.paddingBottom = pad + '%';
    }

    // Update multi-breakpoint CSS background.
    // @todo move it out of ratio. ATM, requires ratio to update multi-BG.
    if (isResized) {
      me.update(cn, false, _winData);
    }

    // @todo refactor or remove into IO.
    // Fix for picture or bg element with resizing.
    // if (isResized && (isPicture || $.hasAttr(cn, _dataBg))) {
    // me.onIntersecting((isPicture ? $.find(cn, 'img') : cn), cn);
    // }
  }

  // Only rewrites if the style is indeed stripped out, and not set.
  // View rewrite result stripped out style attribute required by fluid ratio.
  function fallbackRatio(cn) {
    var value = $.attr(cn, _dataRatio);

    if (!$.hasAttr(cn, 'style') && value) {
      cn.style.paddingBottom = value + '%';
    }
  }

  /**
   * Resize Fluid aspect ratio.
   *
   * @todo this should be at bio.js, but bLazy has no support which prevents it.
   */
  function resize() {
    var me = this;
    var doc = me.context;
    var els = $.findAll(doc, _elRatio);

    // Update multi-breakpoint fluid aspect ratio, if any.
    if (els.length) {
      me.checkResize(els, updateRatio, doc);
    }
  }

  /**
   * Processes DOM observations.
   */
  function process() {
    var me = this;

    // Mount extensions.
    me.mount(true);
    _opts = me.options;

    // ::init will/not be overridden by blazy/load, no problem since 2.6.
    if ($.isNull(me.init)) {
      me.init = me.run(_opts);
    }

    resize.call(me);
  }

  /**
   * Attaches blazy behavior to HTML elements.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyCompat = {
    attach: function (context) {

      var me = Drupal.blazy;
      me.context = $.context(context);

      // No bind without extra arguments, call me.
      $.once(process.call(me));

    },
    detach: function (context, settings, trigger) {
      if (trigger === 'unload') {
        var me = Drupal.blazy;
        me.unresize();
      }
    }
  };

}(dBlazy, Drupal, this));
