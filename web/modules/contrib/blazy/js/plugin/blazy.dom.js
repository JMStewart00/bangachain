/**
 * @file
 * Provides CSS DOM methods which can replaced by Cash, or alike when available.
 *
 * This file is separated to be removed when Cash is available, and adoptable.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module, or its sub-modules.
 *   This file is an experiment, and subject to removal when Cash lands, or
 *   similar vanilla alternative is available at core. The rule is don't load
 *   anything unless required by the page. Another reason for components.
 *   It is extending dBlazy as a separate plugin to mimick jQuery CSS method.
 *
 * @todo https://caniuse.com/dom-manip-convenience
 * Includes: ChildNode.before, ChildNode.after, ChildNode.replaceWith,
 * ParentNode.prepend, and ParentNode.append.
 */

(function ($, _win, _doc) {

  'use strict';

  var _width = 'width';
  var _height = 'height';
  var _after = 'after';
  var _before = 'before';
  var _begin = 'begin';
  var _end = 'end';
  var _uTop = 'Top';
  var _uLeft = 'Left';
  var _uHeight = 'Height';
  var _uWidth = 'Width';
  var _scroll = 'scroll';

  function css(els, props, vals) {
    var me = this;
    var _undefined = $.isUnd(vals);
    var _obj = $.isObj(props);
    var _getter = !_obj && _undefined;

    // Getter.
    if (_getter && $.isStr(props)) {
      // @todo figure out multi-element getters. Ok for now, as hardly multiple.
      var el = els && els.length ? els[0] : els;
      // @todo re-check common integer.
      var arr = [_width, _height, 'top', 'right', 'bottom', 'left'];
      var result = $.computeStyle(el, props);
      return arr.indexOf(props) === -1 ? result : parseInt(result, 10);
    }

    var chainCallback = function (el) {
      if (!$.isElm(el)) {
        return _getter ? '' : me;
      }

      var setVal = function (prop, val) {
        // Setter.
        if ($.isFun(val)) {
          val = val();
        }

        if ($.contains(prop, '-') || $.isVar(prop)) {
          prop = $.camelCase(prop);
        }

        el.style[prop] = $.isStr(val) ? val : val + 'px';
      };

      // Passing a key-value pair object means setting multiple attributes once.
      if (_obj) {
        $.each(props, function (val, prop) {
          setVal(prop, val);
        });
      }
      // Since a css value null makes no sense, assumes nullify.
      else if ($.isNull(vals)) {
        $.each($.toArray(props), function (prop) {
          el.style.removeProperty(prop);
        });
      }
      else {
        // Else a setter.
        if ($.isStr(props)) {
          setVal(props, vals);
        }
      }
    };

    return $.chain(els, chainCallback);
  }

  $.css = css;

  // @tdo multiple css values once.
  $.fn.css = function (prop, val) {
    return css(this, prop, val);
  };

  function offset(el) {
    var rect = $.rect(el);

    return {
      top: (rect.top || 0) + _doc.body[_scroll + _uTop],
      left: (rect.left || 0) + _doc.body[_scroll + _uLeft]
    };
  }

  $.offset = offset;

  $.width = function (el, val) {
    return css(el, _width, val);
  };

  $.height = function (el, val) {
    return css(el, _height, val);
  };

  function outerDim(el, withMargin, prop) {
    var result = 0;

    if ($.isElm(el)) {
      result = el['offset' + prop];
      if (withMargin) {
        var style = $.computeStyle(el);
        var margin = function (pos) {
          return parseInt(style['margin' + pos], 10);
        };
        if (prop === _uHeight) {
          result += margin(_uTop) + margin('Bottom');
        }
        else {
          result += margin(_uLeft) + margin('Right');
        }
      }
    }
    return result;
  }

  $.outerWidth = function (el, withMargin) {
    return outerDim(el, withMargin, _uWidth);
  };

  $.outerHeight = function (el, withMargin) {
    return outerDim(el, withMargin, _uHeight);
  };

  /**
   * Insert Element or string into a position relative to a target element.
   *
   * To minimize confusions with native insertAdjacent[Element|HTML].
   *
   * <!-- beforebegin -->
   * <p>
   *   <!-- afterbegin -->
   *   foo
   *   <!-- beforeend -->
   * </p>
   * <!-- afterend -->
   *
   * @param {Element} target
   *   The target Element.
   * @param {Element|string} el
   *   The element or string to insert.
   * @param {string} position
   *   The position or placement.
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/API/Element/insertAdjacentElement
   * @see https://developer.mozilla.org/en-US/docs/Web/API/Element/insertAdjacentHTML
   */
  function insert(target, el, position) {
    // @todo recheck DocumentFragment if needed.
    if ($.isElm(target)) {
      var suffix = $.isElm(el) ? 'Element' : 'HTML';
      target['insertAdjacent' + suffix](position, el);
    }
  }

  $.after = function (target, el) {
    insert(target, el, _after + _end);
  };

  // Node.insertBefore(), similar to beforebegin, with different arguments.
  $.before = function (target, el) {
    insert(target, el, _before + _begin);
  };

  // Node.appendChild(), same effect as beforeend.
  $.append = function (target, el) {
    insert(target, el, _before + _end);
  };

  $.prepend = function (target, el) {
    insert(target, el, _after + _begin);
  };

  function clone(els) {
    var chainCallback = function (el) {
      return $.isElm(el) && el.cloneNode(true);
    };
    return $.chain(els, chainCallback);
  }

  $.clone = clone;
  $.fn.clone = function () {
    return clone(this);
  };

})(dBlazy, this, this.document);
