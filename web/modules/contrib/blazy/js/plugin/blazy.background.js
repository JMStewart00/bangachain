/**
 * @file
 * Provides background extension for dBlazy.
 */

(function ($) {

  'use strict';

  /**
   * Updates CSS background with multi-breakpoint images.
   *
   * @private
   *
   * @param {dBlazy|Array.<Element>|Element} els
   *   The container HTML element(s), or dBlazy instance.
   * @param {Object} winData
   *   Containing ww: windowWidth, and up: to use min-width or max-width.
   *
   * @return {Object}
   *   This dBlazy object.
   */
  function bg(els, winData) {
    var chainCallback = function (el) {
      if ($.isElm(el)) {
        var url = $.bgUrl(el, winData);

        if (url) {
          el.style.backgroundImage = 'url("' + url + '")';
        }
      }
    };

    return $.chain(els, chainCallback);
  }

  $.bgUrl = function (el, winData) {
    var data = $.parse($.attr(el, 'data-b-bg'));

    if (data) {
      var _bg = $.activeWidth(data, winData);
      if (_bg && _bg !== 'undefined') {
        var _ratio = _bg.ratio;

        // Allows to disable Aspect ratio if it has known/ fixed heights such as
        // gridstack multi-size boxes.
        if (_ratio && !$.hasClass(el, 'b-noratio')) {
          el.style.paddingBottom = _ratio + '%';
        }
        return _bg.src;
      }
    }
    return '';
  };

  $.bg = bg;
  $.fn.bg = function (winData) {
    return bg(this, winData);
  };

}(dBlazy));
