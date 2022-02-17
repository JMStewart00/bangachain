/**
 * @file
 * Provides Splide extensions for onwheel event.
 */

(function ($, _ds, _win, _doc) {

  'use strict';

  var Zoom = function (Splide, Components) {
    var root = Splide.root;
    var o = Splide.options;
    var oz = o.zoom || {};
    var max = oz.max || 1.5;
    var min = oz.min || 0.6;
    var zoomOn = oz.on || false;
    var zoomScale = oz.scale || false;
    var zoomClick = oz.click || false;
    var zoomRoot = oz.root || false;
    var zoomClass = oz.rootClass || 'is-zoomed';
    var zoomTarget = oz.target || '.slide__media';
    var dragClass = oz.dragClass || 'is-dragging';
    var _isZoomable = 'is-zoomable';
    var factor = 0.01;
    var scale = 1;
    var currScale = 1;
    var pos = {
      x: 0,
      y: 0
    };
    var _zoomRoot = null;
    var _target = null;
    var _img = null;
    var _offset = 0;
    var _sizes = {};
    var _targets = [];
    var cw;
    var ch;
    var nw;
    var nh;
    // @todo var mw;
    // @todo var mh;
    // @todo var size;
    var de = _doc.documentElement;
    var wh = _win.innerHeight || de.clientHeight;
    var ww = _win.innerWidth || de.clientWidth;
    var _dataSplide = 'data-splide';
    var dataCw = _dataSplide + '-cw';
    var dataCh = _dataSplide + '-ch';
    var dataNw = _dataSplide + '-nw';
    var dataNh = _dataSplide + '-nh';
    var _scrollRaf = null;

    return {
      currSlide: null,
      unZoomed: false,
      mount: function () {
        var me = this;

        if (!zoomOn) {
          return;
        }

        Splide.on('mounted.spz', function () {
          _win.setTimeout(function () {
            me.wheelZoom();
          }, 500);
        });

        Splide.on('active.spz', function (slide) {
          me.currSlide = slide;
          me.unZoomed = false;

          me.prepare();
        });

        Splide.on('inactive.spz', function (slide) {
          me.currSlide = null;
          me.toogleClass(false);
          var oldImg = slide.slide.querySelector('.' + _isZoomable);
          me.scale(false, oldImg);
        });

        me.dragon();
      },

      wheelZoom: function () {
        var me = this;

        _targets = root.querySelectorAll(zoomTarget);

        if (_targets.length) {
          $.forEach(_targets, function (el) {
            var img = el.querySelector('.' + _isZoomable);
            if (img !== null) {
              me.fit(img);
              me.scale(false, img);
              me.bindWheel(el, true, me.zoom.bind(me));
            }
          });
        }
      },

      fit: function (el) {
        var me = this;
        var oh = el.offsetHeight;
        var ow = el.offsetWidth;
        var rh = parseInt(_ds.attr(el, 'height', 0));
        var rw = parseInt(_ds.attr(el, 'width', 0));

        if (oh > wh) {
          if (wh / oh > ww / ow) {
            nw = ww;
            nh = oh * (ww / ow);
          }
          else {
            nw = ow * (wh / oh);
            nh = wh;
          }
        }

        var dims = me.sizes(el);
        if (!me.isEmpty(dims) && (dims.nw || dims.w)) {
          cw = dims.w;
          ch = dims.h;
          nw = dims.nw || nw;
          nh = dims.nh || nh;

          me.toData(el);

          if (!_ds.attr(el, dataNh)) {
            el.setAttribute(dataCw, cw);
            el.setAttribute(dataCh, ch);
            el.setAttribute(dataNw, nw);
            el.setAttribute(dataNh, nh);

            // @todo size = _ds.resize(cw, ch, mw, mh);
          }
        }

        if (rh < wh) {
          nh = rh;
          nw = rw;
          el.dataset.splideFit = 1;
        }

        el.style.width = nw + 'px';
        el.style.height = nh + 'px';
      },

      bindWheel: function (el, bind, callback) {
        $[bind ? 'bindEvent' : 'unbindEvent'](el, 'wheel', callback, {
          passive: true
        });
      },

      prepare: function () {
        var me = this;
        if (me.currSlide) {
          _target = me.currSlide.slide.querySelector(zoomTarget);
          if (_target !== null) {
            _img = _target.querySelector('.' + _isZoomable);
          }
        }
      },

      zoomRoot: function () {
        if (_zoomRoot === null && zoomRoot) {
          _zoomRoot = typeof zoomRoot === 'string' ? document.querySelector(zoomRoot) : zoomRoot;
        }
        return !_zoomRoot ? root : _zoomRoot;
      },

      zoom: function (e) {
        var me = this;
        var delta = _ds.wheelDelta(e);

        // Already passive e.preventDefault();
        me.prepare();

        scale += delta * -factor;

        // Restrict scale.
        scale = Math.min(Math.max(1, scale), max);

        if (me.isZoomed()) {
          me.unZoomed = false;
          me.scrollon(e);
        }
        else {
          if (me.unZoomed) {
            scale = 1;
            me.scale(false, _img);
          }
          else {
            me.scale(!me.isZoomed(), _img);
          }
        }
      },

      scale: function (zoomIn, el) {
        var me = this;
        currScale = scale;
        var zooming = zoomIn;

        me.toogleClass(zooming);

        if (el !== null) {
          if (zoomScale) {
            var slide = $.closest(el, '.slide');
            var cn = $.closest(el, '.slide__content');
            var w = parseInt(_ds.attr(el, 'width', 0));
            var h = parseInt(_ds.attr(el, 'height', 0));
            var cw = cn.offsetWidth;

            slide.classList.add(_isZoomable + '-slide');

            max = (h / wh); // / 1.5;

            var mn = min; // (h / wh) / 3;
            var mx = max > 2 ? 1.5 : max;
            if (w < cw) {
              mn = 0.8;
              mx = 1;
            }

            currScale = zoomIn ? mx : mn;
            el.style.transform = 'scale(' + currScale + ')';
          }
        }

        if (_target && (!zooming || !me.isZoomed() || el === null)) {
          _target.style.transform = 'translate(0px)';
        }
      },

      scrollon: function (e) {
        var me = this;

        if (_scrollRaf) {
          _win.cancelAnimationFrame(_scrollRaf);
        }

        me.sizes();
        if (me.isEmpty(_sizes) || !me.isValid()) {
          return;
        }

        _scrollRaf = _win.requestAnimationFrame(function () {
          me._scrollon(e);
        });
      },

      _scrollon: function (e) {
        e.preventDefault();
        e.stopPropagation();

        var el = _target.querySelector('.' + _isZoomable);
        var fit = el !== null && el.dataset.splideFit;
        var sy = Math.abs(_sizes.h - _sizes.ph) / 1.5;
        var delta = _ds.wheelDelta(e);
        var increment = sy * 0.5;

        pos.y += delta > 0 ? -increment : increment;
        pos.x = 0;
        _offset = Math.abs(pos.y) >= sy;

        // Scroll down.
        if (delta > 0 && _offset) {
          pos.y = -sy;
        }
        // Scroll up.
        else if (delta < 0 && _offset) {
          pos.y = sy;
        }

        if (_target && !fit) {
          _target.style.transform =
            'translate(' + pos.x + 'px,' + pos.y + 'px)';
        }

        _scrollRaf = null;
        return false;
      },

      dragon: function () {
        var me = this;
        var cn = me.zoomRoot();
        var phase;
        var dir;
        var reset = false;
        var opts = oz;
        var el = _target;
        var sy = 0;

        var callback = function (e, data) {
          el = data.el || el;
          pos.x = data.x || 0;
          pos.y = data.y || 0;

          me.sizes();
          sy = Math.ceil((_sizes.h - _sizes.ph) / 2);

          dir = data.dir;
          phase = data.phase;

          if (el) {
            if (phase === 'start') {
              start();
            }
            else if (phase === 'move') {
              move();
            }
            else if (phase === 'end') {
              end();
            }
          }
        };

        opts.onClick = zoomClick ? me.clicked.bind(me) : false;
        opts.callback = callback;
        opts.dragClass = dragClass;

        var items = [];
        $.forEach(Components.Elements.slides, function (slide) {
          var item = slide.querySelector(zoomTarget);
          if (item) {
            items.push(item);
          }
        });

        opts.elms = items;
        new SwipeDetect(cn, opts);

        function start(e) {
          update('off');
        }

        function move() {
          if (me.isZoomed()) {
            el.style.transform =
              'translate(' + pos.x + 'px,' + pos.y + 'px' + ')';
          }
          else {
            revert();
          }
        }

        function end() {
          _offset = Math.abs(pos.y) >= sy;
          var toRight = dir === 'right';
          if (toRight || dir === 'left') {
            reset = true;
            pos.x = toRight ? 0 : -1;
          }
          else if (_offset) {
            reset = true;
            // dir === 'up' && pos.y < 0
            pos.y = dir === 'down' && pos.y > 0 ? sy : -sy;
          }

          _win.setTimeout(function () {
            update('on');
            if (me.isZoomed()) {
              if (reset) {
                el.style.transform =
                  'translate(' + pos.x + 'px,' + pos.y + 'px)';
              }
            }
            else {
              revert();
            }
          }, 300);
        }

        function revert() {
          el.style.transform = 'translate(0px)';
        }

        function update(e) {
          me[e]();

          Splide.options = {
            drag: !me.isZoomed()
          };
        }
      },

      clicked: function (e) {
        var me = this;

        if (e.target.classList.contains(_isZoomable)) {
          me.unZoomed = false;
          scale = max;
          me.scale(!me.isZoomed(), e.target);
        }
        else {
          me.unZoomed = true;
        }
      },

      sizes: function (img) {
        _img = img || _img;
        var dims = _sizes = _ds.checkSizes(_img, _target);
        return dims;
      },

      isEmpty: function (obj) {
        return (
          obj &&
          Object.keys(obj).length === 0 &&
          Object.getPrototypeOf(obj) === Object.prototype
        );
      },

      toData: function (el) {
        cw = _ds.attr(el, dataCw, cw);
        ch = _ds.attr(el, dataCh, ch);
        // @todo mw = _ds.attr(el, dataNw, nw);
        // @todo mh = _ds.attr(el, dataNh, nh);
      },

      isValid: function () {
        this.prepare();
        return _target !== null;
      },

      isZoomed: function () {
        return zoomOn && this.zoomRoot().classList.contains(zoomClass);
      },

      toogleClass: function (zoomIn) {
        this.zoomRoot().classList[zoomIn ? 'add' : 'remove'](zoomClass);
      },

      on: function () {
        this.unZoomed = false;
      },

      off: function () {
        this.unZoomed = true;
      }
    };
  };

  _ds.extend({
    Zoom: Zoom
  });
})(dBlazy, dSplide, this, this.document);
