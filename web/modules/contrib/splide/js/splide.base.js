/**
 * @file
 * Provides Splide utilities.
 */

(function (factory) {

  'use strict';

  // Browser globals (root is window).
  factory(window.dBlazy, window, window.document);

})(function ($, _win, _doc) {

  'use strict';

  /**
   * Shared objects for drupalSplide.
   *
   * @namespace
   */
  _win.dSplide = {};

  var _ds = _win.dSplide;
  var _id = 'splide';

  _ds.extensions = {};
  _ds.listeners = {};
  _ds.transitions = [];

  // Init non-module library built-in, yet separated, extensions.
  _ds.initExtensions = function () {
    var me = this;
    if (_win.splide && _win.splide.Extensions) {
      if (_win.splide.Extensions.AutoScroll) {
        me.extend({AutoScroll: _win.splide.Extensions.AutoScroll});
      }
      if (_win.splide.Extensions.Intersection) {
        me.extend({Intersection: _win.splide.Extensions.Intersection});
      }
    }
  };

  // Init module/ custom listener extensions, must be called before init event.
  _ds.initListeners = function (instance) {
    var me = this;
    var o = instance.options;
    var append = function (prev, sel) {
      var el = $.find(instance.root, sel);
      if ($.isElm(el)) {
        prev.insertAdjacentElement('afterend', el);
      }
    };

    instance.on('arrows:mounted', function (prev, next) {
      if (prev === null) {
        return;
      }

      _win.setTimeout(function () {
        // Puts dots inbetween arrows for easy theming like this: < ooooo >.
        if (o.pagination === '.' + _id + '__arrows') {
          append(prev, '.' + _id + '__pagination');
        }

        // Puts arrow down inbetween arrows for easy theming like this: < v >.
        if (o.down) {
          append(prev, '.' + _id + '__arrow--down');
        }
      }, 100);
    });

    instance.on('lazyload:loaded', $.unloading);

    var listeners = me.listeners;
    if (listeners) {
      $.each(listeners, function (listener) {
        if (listener && typeof listener === 'function') {
          var fn = listener(instance, instance.Components, o);
          if ('mount' in fn) {
            fn.mount();
          }
        }
      });
    }
  };

  // Register module/ custom extensions not bound to before init event.
  _ds.extend = function (fn) {
    this.extensions = $.extend({}, this.extensions, fn);
  };

  // Register module/ custom listener plugins, must be called before init event.
  _ds.listen = function (fn) {
    this.listeners = $.extend({}, this.listeners, fn);
  };

  // Register module/ custom transitions aside from defaults: loop, slide, fade.
  _ds.addTransition = function (fn) {
    this.transitions.push(fn);
  };

  _ds.getTransition = function (type) {
    var me = this;
    var fn = null;
    if (me.transitions.length) {
      $.each(me.transitions, function (obj) {
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
      $.each(styles, function (value, prop) {
        if (!$.isNull(value)) {
          elm.style[prop] = value;
        }
      });
    }
  };

  _ds.checkSizes = function (img, parent) {
    var _sizes = {};
    if ($.isNull(img) || $.isNull(parent)) {
      return _sizes;
    }

    var recheck = function (e) {
      var aw = $.attr(img, 'width') || 0;
      var ah = $.attr(img, 'height') || 0;
      _sizes = {
        w: img.offsetWidth,
        h: img.offsetHeight,
        nw: img.naturalWidth || parseInt(aw, 2),
        nh: img.naturalHeight || parseInt(ah, 2),
        aw: parseInt(aw, 2),
        ah: parseInt(ah, 2),
        pw: parent.offsetWidth,
        ph: parent.offsetHeight
      };

      if (e) {
        $.off(img, 'load.' + _id, recheck);
      }
    };

    if ($.isDecoded(img) || $.attr(img, 'data-src')) {
      recheck();
    }
    else {
      $.on(img, 'load.' + _id, recheck);
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

  // @todo remove for $.unloading post Blazy 2.3+.
  _ds.unloading = function (el) {
    $.unloading(el);
  };

  // @todo remove for $.attr post Blazy 2.3+.
  _ds.attr = function (el, attr, def) {
    return $.attr(el, attr, def);
  };

  // @todo remove for dBlazy.context post Blazy:2.6+.
  _ds.context = function (context) {
    return $.context(context);
  };

  return _ds;

});
