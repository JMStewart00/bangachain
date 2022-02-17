/**
 * @file
 * Provides Splide swipe detection.
 *
 * http://www.javascriptkit.com/javatutors/touchevents3.shtml
 * @todo re-check if the Splide library has this data exposed.
 * This file is not directly used by Splide module, but is at Splidebox. Moved
 * here for re-use and other advanced usages, like zoom, etc.
 */

(function ($, _ds, _win, _doc) {

  'use strict';

  var _touchstart = 'touchstart';
  var _touchmove = 'touchmove';
  var _touchend = 'touchend';
  var _mousemove = 'mousemove';
  var _mouseup = 'mouseup';
  var _mousedown = 'mousedown';
  var _click = 'click';
  var _down = 'down';
  var _up = 'up';
  var _left = 'left';
  var _right = 'right';
  var _none = 'none';

  /**
   * SwipeDetect constructor.
   *
   * @namespace
   *
   * @param {HTMLElement} el
   *   The container element to detect swiping.
   * @param {Object} options
   *   An options object.
   *
   * @return {String}
   *   Returns SwipeDetect instance.
   */
  _win.SwipeDetect = function (el, options) {
    var me = this;

    if (el && typeof el === 'string') {
      el = _doc.querySelector(el);
    }

    me.root = el;
    me.options = options;

    init.call(me);

    return me;
  };

  var _proto = SwipeDetect.prototype;
  _proto.constructor = SwipeDetect;

  // Public methods.
  _proto.toData = function (el, dir, phase, swipeType, viewport, x, y) {
    var distance = (dir === _left || dir === _right) ? x : y;
    return {
      el: el,
      dir: dir,
      distance: phase === 'start' ? 0 : distance,
      phase: phase,
      swipeType: swipeType,
      viewport: viewport,
      x: x,
      y: y
    };
  };

  function init() {
    var me = this;
    var target = me.options.target || '.slide__content';

    _win.setTimeout(function () {
      var elms = me.options.elms || me.root.querySelectorAll(target);

      if (elms && elms.length) {
        $.forEach(elms, detect.bind(me));
      }
    });
  }

  function detect(el) {
    var me = this;
    var root = me.root;
    var options = me.options;
    var dragClass = options.dragClass || 'is-dragging';
    var targetClass = options.targetClass || 'is-moved';
    var onClick = options.onClick || function (evt) {};
    var touch = options.callback || function (evt, data) {};
    var threshold = 150;
    var restraint = 100;
    var data;
    var dir = _none;
    var swipeType = _none;
    var startX;
    var startY;
    var x = 0;
    var y = 0;
    var elapsedTime;
    var startTime;
    var mouseIsDown = false;
    var viewport = {};
    var _downStarter = null;
    var _downDelay = 500;
    var _downActive = false;
    var _moveRaf = null;

    _downDelay = options.downDelay || _downDelay;

    function _start(e) {
      var isMobile = e.type === _touchstart;
      var touchObj = isMobile ? e.changedTouches[0] : e;

      viewport = _ds.getViewport();
      startX = touchObj.pageX;
      startY = touchObj.pageY;
      // Record time when finger first makes contact with surface.
      startTime = new Date().getTime();

      data = me.toData(el, dir, 'start', _none, viewport, startX, startY);

      toggleItemClass(el, true);
      touch(e, data);

      mouseIsDown = !isMobile;
      e.preventDefault();

      if (isMobile) {
        bind(el, _touchmove, move);
        bind(el, _touchend, release);
      }
      else {
        bind(_win, _mousemove, move);
        bind(_win, _mouseup, release);
      }
    }

    function start(e) {
      _start(e);

      _downStarter = _win.setTimeout(function () {
        _downStarter = null;
        _downActive = true;
      }, _downDelay);
    }

    function move(e) {
      var isMobile = e.type === _touchmove;
      if (!isMobile && !mouseIsDown) {
        return false;
      }

      // Cancel if an animation frame was already requested.
      if (_moveRaf) {
        _win.cancelAnimationFrame(_moveRaf);
      }

      var update = function () {
        toggleRootClass(true);
        touch(e, data);
      };

      _moveRaf = _win.requestAnimationFrame(function () {
        var touchObj = isMobile ? e.changedTouches[0] : e;

        // Get horizontal dist traveled by finger while in contact with surface.
        x = touchObj.pageX - startX;
        // Get vertical dist traveled by finger while in contact with surface.
        y = touchObj.pageY - startY;

        // If distance traveled horizontally is greater than vertically,
        // consider this a horizontal movement.
        if (abs(x) > abs(y)) {
          dir = x < 0 ? _left : _right;
        }
        // Else consider this a vertical movement.
        else {
          dir = y < 0 ? _up : _down;
        }

        data = me.toData(el, dir, 'move', swipeType, viewport, x, y);

        update();

        dir = data.dir;
        _moveRaf = null;
      });

      // Prevent scrolling when inside DIV.
      e.preventDefault();
    }

    function _release(e) {
      var isMobile = e.type === _touchend;
      if (!isMobile && !mouseIsDown) {
        return false;
      }

      elapsedTime = new Date().getTime() - startTime;
      if (elapsedTime <= _downDelay) {
        if (abs(x) >= threshold && abs(y) <= restraint) {
          swipeType = dir;
        }
        else if (abs(y) >= threshold && abs(x) <= restraint) {
          swipeType = dir;
        }
      }

      data = me.toData(el, dir, 'end', swipeType, viewport, x, y);

      touch(e, data);

      mouseIsDown = false;
      e.preventDefault();
      reset(isMobile);
    }

    function release(e) {
      _release(e);

      if (_downStarter) {
        _win.clearTimeout(_downStarter);
        toggleClick(el, true);
      }
      else if (_downActive) {
        _downActive = false;
      }
    }

    function reset(isMobile) {
      toggleRootClass(false);
      if (isMobile && el) {
        unbind(el, _touchmove, move);
        unbind(el, _touchend, release);
      }
      else {
        unbind(_win, _mousemove, move);
        unbind(_win, _mouseup, release);
      }

      if (_moveRaf) {
        _win.cancelAnimationFrame(_moveRaf);
      }
    }

    function _onClick(e) {
      if ($.equal(e.target, 'img')) {
        onClick(e);

        e.stopPropagation();

        toggleClick(el, false);
      }
    }

    function toggleRootClass(add) {
      if (root && dragClass) {
        _toggleClass(root, dragClass, add);
      }
    }

    function toggleItemClass(el, add) {
      if (el && targetClass) {
        _toggleClass(el, targetClass, add);
      }
    }

    function toggleClick(el, add) {
      if (el) {
        el[(add ? 'add' : 'remove') + 'EventListener'](_click, _onClick, true);
      }
    }

    bind(el, _touchstart, start);
    bind(el, _mousedown, start);
  }

  function abs(v) {
    return Math.abs(v);
  }

  function bind(el, e, fn, params) {
    if (el) {
      $.bindEvent(el, e, fn, params);
    }
  }

  function unbind(el, e, fn, params) {
    if (el) {
      $.unbindEvent(el, e, fn, params);
    }
  }

  function _toggleClass(el, className, add) {
    if (el && className) {
      el.classList[add ? 'add' : 'remove'](className);
    }
  }
})(dBlazy, dSplide, this, this.document);
