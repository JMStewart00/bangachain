/**
 * @file
 * Provides Splide extensions for Fullscren API.
 *
 * https://wiki.mozilla.org/Gecko:FullScreenAPI
 * https://developer.mozilla.org/en-US/docs/Web/API/Fullscreen_API
 * https://developer.mozilla.org/en-US/docs/Web/API/Fullscreen_API/Guide
 * Credit: https://github.com/sindresorhus/screenfull.js
 * @todo Fix move to blazy for re-use with videos, and other elements.
 */

(function ($, _ds, _doc) {

  'use strict';

  var _proto = Element.prototype;
  if (!_proto.requestFullscreen) {
    _proto.requestFullscreen = _proto.mozRequestFullscreen || _proto.webkitRequestFullscreen || _proto.msRequestFullscreen;
  }

  if (!_doc.exitFullscreen) {
    _doc.exitFullscreen = _doc.mozExitFullscreen || _doc.webkitExitFullscreen || _doc.msExitFullscreen;
  }

  if (!_doc.fullscreenElement) {
    Object.defineProperty(_doc, 'fullscreenElement', {
      get: function () {
        return _doc.mozFullScreenElement || _doc.msFullscreenElement || _doc.webkitFullscreenElement;
      }
    });

    Object.defineProperty(_doc, 'fullscreenEnabled', {
      get: function () {
        return _doc.mozFullScreenEnabled || _doc.msFullscreenEnabled || _doc.webkitFullscreenEnabled;
      }
    });
  }

  var FullScreen = function (Splide, Components) {
    var _element = Splide.root || _doc.documentElement;
    var _trigger = false;
    var _className = 'is-fs';
    var events = {
      change: 'fullscreenchange',
      error: 'fullscreenerror'
    };

    return {
      options: {
        element: _element,
        className: _className,
        trigger: '[data-fs-trigger]'
      },

      init: function (opts) {
        var me = this;

        me.options = $.extend({}, me.options, opts || {});
        _element = me.options.element;
        _trigger = me.options.trigger;
        _className = me.options.className;

        var elms = _doc.querySelectorAll(_trigger);
        if (elms === null) {
          elms = _element.querySelectorAll(_trigger);
        }

        if (elms !== null && elms.length) {
          $.forEach(elms, function (el) {
            $.bindEvent(el, 'click', me.toggle.bind(me), {
              passive: false
            });
          });
        }
      },

      _toggle: function (enter) {
        _element.classList[enter ? 'add' : 'remove'](_className);

        return enter ? _element.requestFullscreen() : _doc.exitFullscreen();
      },

      _request: function () {
        return this._toggle(true);
      },

      _exit: function () {
        return this._toggle(false);
      },

      request: function () {
        var me = this;

        if (_element.classList.contains(_className)) {
          return false;
        }

        return new Promise(function (resolve, reject) {
          var onEntered = function () {
            me.off('change', onEntered);
            resolve();
          };

          me.on('change', onEntered);

          var hit = me._request();
          if (hit instanceof Promise) {
            hit.then(onEntered, reject);
          }
        });
      },

      exit: function () {
        var me = this;

        if (!_element.classList.contains(_className)) {
          return false;
        }

        return new Promise(function (resolve, reject) {
          if (!me._close()) {
            resolve();
            return;
          }

          var onExit = function () {
            me.off('change', onExit);
            resolve();
          };

          me.on('change', onExit);

          var hit = me._exit();
          if (hit instanceof Promise) {
            hit.then(onExit, reject);
          }
        });
      },

      toggle: function (e) {
        var me = this;
        if (e && typeof e !== 'undefined') {
          e.preventDefault();
        }

        return me[me._close() ? 'exit' : 'request']();
      },

      on: function (e, callback) {
        return typeof callback === 'undefined' ? false : $.bindEvent(_doc, events[e], callback.bind(this));
      },

      off: function (e, callback) {
        return typeof callback === 'undefined' ? false : $.unbindEvent(_doc, events[e], callback.bind(this));
      },

      _close: function () {
        return _doc.fullscreenElement;
      }

    };
  };

  _ds.extend({
    FullScreen: FullScreen
  });

})(dBlazy, dSplide, this.document);
