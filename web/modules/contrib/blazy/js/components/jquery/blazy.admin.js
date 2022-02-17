/**
 * @file
 * Provides admin utilities.
 */

(function ($, Drupal, _d) {

  'use strict';

  var _desc = 'description';
  var _descMounted = _desc + '--on';
  var _vanillaOn = 'form--vanilla-on';
  var _elTootip = '.' + _desc + ':not(.' + _descMounted + '), .form-item__' + _desc + ':not(.' + _descMounted + ')';
  var _checkbox = 'form-checkbox';
  var _checkboxMounted = _checkbox + '--on';
  var _elCheckbox = '.' + _checkbox + ':not(.' + _checkboxMounted + ')';
  var _form = 'form--slick';
  var _formMounted = _form + '--on';
  var _elForm = '.' + _form + ':not(.' + _formMounted + ')';
  var _elFormItem = '.form-item';
  var _elExpandable = '.js-expandable';
  var _elHint = '.b-hint';
  var _isFocused = 'is-focused';
  var _isHovered = 'is-hovered';
  var _isSelected = 'is-selected';
  var _addClass = 'addClass';
  var _removeClass = 'removeClass';
  var _checked = 'checked';
  var _change = 'change';
  var _click = 'click';

  /**
   * Blazy admin utility functions.
   *
   * @param {HTMLElement} form
   *   The Blazy form wrapper HTML element.
   */
  function blazyForm(form) {
    var t = $(form);

    $('.details-legend-prefix', t).removeClass('element-invisible');

    t[$('.' + _checkbox + '--vanilla', t).prop(_checked) ? _addClass : _removeClass](_vanillaOn);

    t.on(_click, '.' + _checkbox, function () {
      var $input = $(this);
      $input[$input.prop(_checked) ? _addClass : _removeClass]('on');

      if ($input.hasClass(_checkbox + '--vanilla')) {
        t[$input.prop(_checked) ? _addClass : _removeClass](_vanillaOn);
      }
    });

    $('select[name$="[style]"]', t).on(_change, function () {
      var $select = $(this);
      var value = $select.val();

      t.removeClass(function (index, css) {
        return (css.match(/(^|\s)form--style-\S+/g) || []).join(' ');
      });

      if (value === '') {
        t.addClass('form--style-off form--style-is-grid');
      }
      else {
        t.addClass('form--style-on form--style-' + value);
        if (value === 'column' || value === 'grid' || value === 'flex' || value === 'nativegrid') {
          t.addClass('form--style-is-grid');
        }
      }
    }).change();

    $('select[name$="[grid]"]', t).on(_change, function () {
      var $select = $(this);

      t[$select.val() === '' ? _removeClass : _addClass]('form--grid-on');
    }).change();

    $('select[name$="[responsive_image_style]"]', t).on(_change, function () {
      var $select = $(this);
      t[$select.val() === '' ? _removeClass : _addClass]('form--responsive-image-on');
    }).change();

    $('select[name$="[media_switch]"]', t).on(_change, function () {
      var $select = $(this);
      var value = $select.val();

      t.removeClass(function (index, css) {
        return (css.match(/(^|\s)form--media-switch-\S+/g) || []).join(' ');
      });

      t[value === '' ? _removeClass : _addClass]('form--media-switch-' + value);
      var nobox = (value === '' || value === 'content' || value === 'media' || value === 'rendered');
      t[nobox ? _removeClass : _addClass]('form--media-switch-lightbox');
    }).change();

    t.on('mouseenter touchstart', _elHint, function () {
      $(this).closest(_elFormItem).addClass(_isHovered);
    });

    t.on('mouseleave touchend', _elHint, function () {
      $(this).closest(_elFormItem).removeClass(_isHovered);
    });

    t.on(_click, _elHint, function () {
      $('.form-item.' + _isSelected, t).removeClass(_isSelected);
      $(this).parent().toggleClass(_isSelected);
    });

    t.on(_click, '.description, .form-item__description', function () {
      $(this).closest('.' + _isSelected).removeClass(_isSelected);
    });

    t.on('focus', _elExpandable, function () {
      $(this).parent().addClass(_isFocused);
    });

    t.on('blur', _elExpandable, function () {
      $(this).parent().removeClass(_isFocused);
    });

    t.addClass(_formMounted);
  }

  /**
   * Blazy admin tooltip function.
   *
   * @param {HTMLElement} elm
   *   The Blazy form item description HTML element.
   */
  function blazyTooltip(elm) {
    var $tip = $(elm);

    // Claro removed description for BEM form-item__description.
    if (!$tip.hasClass(_desc)) {
      $tip.addClass(_desc);
    }

    if (!$tip.siblings(_elHint).length) {
      $tip.closest(_elFormItem).append('<span class="b-hint">?</span>');
    }

    $tip.addClass(_descMounted);
  }

  /**
   * Blazy admin checkbox function.
   *
   * @param {HTMLElement} elm
   *   The Blazy form item checkbox HTML element.
   */
  function blazyCheckbox(elm) {
    var $elm = $(elm);
    if (!$elm.next('.field-suffix').length) {
      $elm.after('<span class="field-suffix"></span>');
    }
    $elm.addClass(_checkboxMounted);
  }

  /**
   * Attaches Blazy form behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyAdmin = {
    attach: function (context) {

      context = _d.context(context);

      _d.once(blazyTooltip, _elTootip, context);
      _d.once(blazyCheckbox, _elCheckbox, context);
      _d.once(blazyForm, _elForm, context);
    }
  };

})(jQuery, Drupal, dBlazy);
