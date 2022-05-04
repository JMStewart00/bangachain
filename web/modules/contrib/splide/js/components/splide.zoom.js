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
    var min = oz.min || 0.8;
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
    var _scrollRaf = null;

    function dim(el, which) {
      return which === 'width' ? el.offsetWidth : el.offsetHeight;
    }

    function updateDim(el, fit) {
      var ow = el.dataset.sNw;
      var oh = el.dataset.sNh;

      var cw = el.dataset.sCw;
      var ch = el.dataset.sCh;

      var eh = $.attr(el, 'height', oh);
      var ew = $.attr(el, 'width', ow);

      el.style.width = (fit ? ew : cw) + 'px';
      el.style.height = (fit ? eh : ch) + 'px';

      var picture = $.closest(el, 'picture');
      if ($.isElm(picture)) {
        picture.style.width = (fit ? ew : cw) + 'px';
        picture.style.height = (fit ? eh : ch) + 'px';
      }
    }

    return {
      currSlide: null,
      unZoomed: false,
      mount: function () {
        var me = this;

        if (!zoomOn) {
          return;
        }

        Splide.on('mounted.spz', function () {
          setTimeout(function () {
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
          var oldImg = $.find(slide.slide, '.' + _isZoomable);

          if ($.isElm(oldImg)) {
            me.scale(false, oldImg);
          }
        });

        me.dragon();
      },

      wheelZoom: function () {
        var me = this;

        _targets = $.findAll(root, zoomTarget);

        if (_targets.length) {
          $.each(_targets, function (el) {
            var img = $.find(el, '.' + _isZoomable);
            if ($.isElm(img)) {
              me.scale(false, img);
              me.bindWheel(el, true, me.zoom.bind(me));
            }
          });
        }
      },

      bindWheel: function (el, bind, callback) {
        $[bind ? 'on' : 'off'](el, 'wheel', callback, {
          passive: true
        });
      },

      fit: function (el) {
        var img = el.target || el;
        var p = $.closest(img, '.slide');
        var ph = dim(p, 'height');
        var pw = ph;
        var cw;
        var ch;
        var ratio = 0;
        var ow = img.naturalWidth;
        var oh = img.naturalHeight;
        var eh = _ds.attr(img, 'width', oh);

        img.style.width = 'auto';
        img.style.height = 'auto';

        if (ow > pw && ow > oh) {
          ratio = ow / oh;
          cw = pw;
          ch = (pw / ratio);
        }
        else if (oh > ph && oh > ow) {
          ratio = oh / ow;

          cw = (ph / ratio);
          ch = ph;
        }
        else {
          cw = pw;
          ch = ph;
        }

        var fit = eh < ph;
        img.dataset.sFit = fit ? 1 : 0;

        img.dataset.sNw = ow;
        img.dataset.sNh = oh;

        img.dataset.sCw = cw;
        img.dataset.sCh = ch;

        img.dataset.sRatio = ratio;

        updateDim(img, fit);
      },

      prepare: function () {
        var me = this;
        var elms = $.findAll(_doc, '.' + _isZoomable);

        if (elms.length) {
          $.each(elms, function (el) {
            if ($.isDecoded(el)) {
              me.fit(el);
            }
            else {
              $.one(el, 'load', me.fit.bind(me));
            }
          });
        }

        if (me.currSlide) {
          _target = $.find(me.currSlide.slide, zoomTarget);
          if ($.isElm(_target)) {
            _img = $.find(_target, '.' + _isZoomable);
          }
        }
      },

      zoomRoot: function () {
        if (!$.isElm(_zoomRoot) && zoomRoot) {
          _zoomRoot = typeof zoomRoot === 'string' ? $.find(_doc, zoomRoot) : zoomRoot;
        }
        return $.isElm(_zoomRoot) ? _zoomRoot : root;
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
        var fit = el && parseInt(el.dataset.sFit, 0) === 1;

        me.toogleClass(zooming);

        if (el) {
          if (zoomScale && !fit) {
            var slide = $.closest(el, '.slide');

            $.addClass(slide, _isZoomable + '-slide');

            max = Math.min(
              parseInt(el.dataset.sNw, 0) / parseInt(el.dataset.sCw, 0),
              parseInt(el.dataset.sNh, 0) / parseInt(el.dataset.sCh, 0)
            );

            max = max > 4 ? 3 : max;

            currScale = zoomIn ? max : min;

            el.style.transform = 'scale(' + currScale + ')';
          }
        }

        if ($.isElm(_target)) {
          if (!zooming || !me.isZoomed() || !$.isElm(el)) {
            _target.style.transform = 'translate(0px)';
          }
        }
      },

      scrollon: function (e) {
        var me = this;

        if (_scrollRaf) {
          _win.cancelAnimationFrame(_scrollRaf);
        }

        me.sizes();
        if ($.isEmpty(_sizes) || !me.isValid()) {
          return;
        }

        _scrollRaf = _win.requestAnimationFrame(function () {
          me._scrollon(e);
        });
      },

      _scrollon: function (e) {
        e.preventDefault();
        e.stopPropagation();

        var el = $.isElm(_target) && $.find(_target, '.' + _isZoomable);
        var fit = $.isElm(el) && parseInt(el.dataset.sFit, 0) === 1;
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

        if ($.isElm(_target) && !fit) {
          // updateDim(el, false);
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
        $.each(Components.Elements.slides, function (slide) {
          var item = $.find(slide, zoomTarget);
          if ($.isElm(item)) {
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

          setTimeout(function () {
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

        if ($.hasClass(e.target, _isZoomable)) {
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

      isValid: function () {
        this.prepare();
        return $.isElm(_target);
      },

      isZoomed: function () {
        return zoomOn && $.hasClass(this.zoomRoot(), zoomClass);
      },

      toogleClass: function (zoomIn) {
        $[zoomIn ? 'addClass' : 'removeClass'](this.zoomRoot(), zoomClass);
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
