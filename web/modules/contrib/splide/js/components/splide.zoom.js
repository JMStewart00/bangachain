/**
 * @file
 * Provides Splide extensions for onwheel event.
 */

(function (_db, _ds, _win) {

  'use strict';

  var Zoom = function (Splide, Components) {
    var root = Splide.root;
    var o = Splide.options;
    var oz = o.zoom || {};
    var max = oz.max || 4;
    var half = max / 2;
    var zoomOn = oz.on || false;
    var zoomCss = oz.css || false;
    var zoomClick = oz.click || false;
    var zoomRoot = oz.root || false;
    var zoomClass = oz.rootClass || 'is-zoomed';
    var zoomTarget = oz.target || '.slide__media';
    var factor = 0.01;
    var scale = 1;
    var pos = {
      x: 0,
      y: 0
    };
    var _zoomRoot = null;
    var _target = null;
    var _img = null;
    var _offset = 0;
    var _sizes = {};
    var cw;
    var ch;
    var nw;
    var nh;
    var mw;
    var mh;
    var size;
    var dataCw = 'data-splide-cw';
    var dataCh = 'data-splide-ch';
    var dataNw = 'data-splide-nw';
    var dataNh = 'data-splide-nh';

    return {
      currSlide: null,
      unZoomed: false,
      mount: function () {
        var me = this;

        if (zoomOn) {
          Splide.on('active.spz', function (slide) {
            me.currSlide = slide;
            me.unZoomed = false;

            _img = slide.slide.querySelector('img');

            me.prepare();
            if (_img) {
              me.sizes(_img);
              if (_sizes && (_sizes.nw || _sizes.w)) {
                cw = _sizes.w;
                ch = _sizes.h;
                nw = _sizes.nw || cw;
                nh = _sizes.nh || ch;

                me.toData(_img);

                if (!_ds.attr(_img, dataNh)) {
                  _img.setAttribute(dataCw, cw);
                  _img.setAttribute(dataCh, ch);
                  _img.setAttribute(dataNw, nw);
                  _img.setAttribute(dataNh, nh);

                  size = _ds.resize(cw, ch, mw, mh);

                  _img.setAttribute('width', size.width);
                  _img.setAttribute('height', size.height);
                }
              }
            }
          });

          Splide.on('inactive.spz', function (slide) {
            me.zoomRoot().classList.remove(zoomClass);
            var oldImg = slide.slide.querySelector('img');
            me.scale(false, oldImg);
          });

          _win.setTimeout(function () {
            if (_img) {
              if (_sizes && (_sizes.nw || _sizes.w)) {
                me.scale(false, _img);
              }
            }
          }, 100);

          _db.bindEvent(root, 'wheel', me.zoom.bind(me), {
            passive: false
          });
          me.dragon();
        }
      },

      prepare: function () {
        if (_img) {
          _target = _db.closest(_img, zoomTarget);
          if (_target === null) {
            _target = _img.parentNode;
          }
        }
      },

      zoomRoot: function () {
        if (_zoomRoot === null && zoomRoot) {
          _zoomRoot = typeof zoomRoot === 'string' ? document.querySelector(zoomRoot) : zoomRoot;
        }
        return !_zoomRoot ? root : _zoomRoot;
      },

      /**
       * Zooms the current slide onwheel.
       *
       * @param {EventObject} e
       *   The wheel event object.
       */
      zoom: function (e) {
        var me = this;
        var delta = _ds.wheelDelta(e);

        _target = _target || e.target;
        _img = _img || _target.querySelector('img');

        e.preventDefault();

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

      /**
       * Scales the current slide.
       *
       * @param {Bool} zoomIn
       *   Whether zoom `in` or `out`.
       * @param {HTMLElement} el
       *   The image element.
       */
      scale: function (zoomIn, el) {
        var me = this;
        var num = zoomIn ? scale : 1;
        var zooming = zoomIn && num > half;

        me.zoomRoot().classList[zooming ? 'add' : 'remove'](zoomClass);

        if (el !== null) {
          if (!zoomCss) {
            el.style.transform = 'scale(' + num + ')';
          }

          me.resize(el, zooming);
        }

        if (_target && (!zooming || !me.isZoomed())) {
          _target.style.transform = 'translate(0px)';
        }
      },

      scrollon: function (e) {
        var me = this;

        if (!me.isValid()) {
          return;
        }

        me.sizes();
        var sy = Math.abs(_sizes.h - _sizes.ph) / 2;
        var delta = _ds.wheelDelta(e);
        var increment = (sy * 0.2);

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

        _target.style.transform = 'translate(' + (pos.x) + 'px,' + (pos.y) + 'px)';
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
        var items = [];
        _db.forEach(Components.Elements.slides, function (slide) {
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
            el.style.transform = 'translate(' + pos.x + 'px,' + pos.y + 'px' + ')';
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
                el.style.transform = 'translate(' + pos.x + 'px,' + pos.y + 'px)';
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
        var active = me.currSlide === null ? _db.closest(e.target, '.slide.is-active') : me.currSlide.slide;

        if (active !== null && _db.equal(e.target, 'img')) {
          me.unZoomed = false;
          scale = max;
          me.scale(!me.isZoomed(), e.target);
        }
      },

      sizes: function (img) {
        _img = img || _img;
        _sizes = _ds.checkSizes(_img, _target);
      },

      resize: function (el, zooming) {
        var me = this;

        _win.setTimeout(function () {
          me.sizes(el);
          me.toData(el);

          size = _ds.resize(cw, ch, mw, mh);

          if (zooming) {
            el.style.height = size.height + 'px';
          }
          else {
            el.style.height = ch + 'px';
          }
        });

      },

      toData: function (el) {
        cw = _ds.attr(el, dataCw, cw);
        ch = _ds.attr(el, dataCh, ch);
        mw = _ds.attr(el, dataNw, nw);
        mh = _ds.attr(el, dataNh, nh);
      },

      isValid: function () {
        return _img !== null && _target !== null;
      },

      isZoomed: function () {
        return zoomOn && this.zoomRoot().classList.contains(zoomClass);
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

})(dBlazy, dSplide, this);
