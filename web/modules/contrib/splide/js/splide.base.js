/**
 * @file
 * Provides Splide utilities.
 */

(function (factory) {

  'use strict';

  // Browser globals (root is window).
  factory(window.dBlazy, window, window.document);

})(function (_db, _win, _doc) {

  'use strict';

  /**
   * Shared objects for drupalSplide.
   *
   * @namespace
   */
  _win.dSplide = {};

  var _ds = _win.dSplide;

  _ds.extensions = {};
  _ds.transitions = [];

  _ds.extend = function (fn) {
    this.extensions = _db.extend({}, this.extensions, fn);
  };

  _ds.addTransition = function (fn) {
    this.transitions.push(fn);
  };

  _ds.getTransition = function (type) {
    var me = this;
    var fn = null;
    if (me.transitions.length) {
      _db.forEach(me.transitions, function (obj) {
        if (obj.fn && (obj.type && obj.type === type)) {
          fn = obj.fn;
          return false;
        }
      });
    }
    return fn;
  };

  _ds.fsIconOn = '<svg xmlns="http://www.w3.org/2000/svg" height="100%" version="1.1" viewBox="0 0 34 34" width="100%"><path d="m 10,16 2,0 0,-4 4,0 0,-2 L 10,10 l 0,6 0,0 z"></path><path d="m 20,10 0,2 4,0 0,4 2,0 L 26,10 l -6,0 0,0 z"></path><path d="m 24,24 -4,0 0,2 L 26,26 l 0,-6 -2,0 0,4 0,0 z"></path><path d="M 12,20 10,20 10,26 l 6,0 0,-2 -4,0 0,-4 0,0 z"></path></svg>';

  _ds.getViewport = function (padding) {
    padding = padding || 8;
    var width = _win.innerWidth;
    var height = _win.innerHeight;

    return {
      bottom: height - padding,
      left: padding,
      right: width - padding,
      top: padding,
      height: height,
      width: width
    };
  };

  _ds.applyStyle = function (elm, styles) {
    if (elm) {
      _db.forEach(styles, function (value, prop) {
        if (value !== null) {
          elm.style[prop] = value;
        }
      });
    }
  };

  _ds.checkSizes = function (img, parent) {
    var _sizes = {};
    if (!img && !parent) {
      return _sizes;
    }

    var recheck = function (e) {
      var aw = img.getAttribute('width') || 0;
      var ah = img.getAttribute('height') || 0;
      _sizes = {
        w: img.offsetWidth,
        h: img.offsetHeight,
        nw: img.naturalWidth || parseInt(aw),
        nh: img.naturalHeight || parseInt(ah),
        aw: parseInt(aw),
        ah: parseInt(ah),
        pw: parent.offsetWidth,
        ph: parent.offsetHeight
      };

      if (e) {
        _db.unbindEvent(img, 'load', recheck);
      }
    };

    if (img.complete || img.getAttribute('data-src')) {
      recheck();
    }
    else {
      _db.bindEvent(img, 'load', recheck);
    }

    return _sizes;
  };

  _ds.resize = function (width, height, maxWidth, maxHeight) {
    var ratio = Math.min(maxWidth / width, maxHeight / height);
    return {
      width: Math.ceil(width * ratio),
      height: Math.ceil(height * ratio)
    };
  };

  // https://stackoverflow.com/questions/5527601/normalizing-mousewheel-speed-across-browsers
  _ds.wheelDelta = function (e) {
    // FIREFOX WIN / MAC | IE.
    var delta = e.deltaY;
    if (!delta) {
      if (e.wheelDelta) {
        // CHROME WIN/MAC | SAFARI 7 MAC | OPERA WIN/MAC | EDGE.
        delta = e.wheelDelta / 120;
      }
      else if (e.detail) {
        // W3C.
        delta = -e.detail / 2;
      }
    }

    return delta > 0 ? 1 : -1;
  };

  // @todo remove and replace with _db.clearLoading post Blazy 2.3.
  _ds.clearLoading = function (el) {
    var loaders = [el, _db.closest(el, '[class*="loading"]')];

    _db.forEach(loaders, function (loader) {
      if (loader !== null) {
        loader.className = loader.className.replace(/(\S+)loading/g, '');
      }
    });
  };

  // @todo Replace with _db.attr post Blazy 2.3+.
  _ds.attr = function (el, attr, def) {
    def = def || '';
    return el && el.hasAttribute(attr) ? el.getAttribute(attr) : def;
  };

  return _ds;

});
