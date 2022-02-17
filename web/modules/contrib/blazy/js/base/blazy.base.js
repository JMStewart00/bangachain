/**
 * @file
 * Provides base methods to bridge drupal-related codes with generic ones.
 *
 * @todo watch out for Drupal namespace removal, likely becomes under window.
 */

(function ($, Drupal) {

  'use strict';

  function _debounce(cb, arg, scope) {
    var _cb = function () {
      cb.call(scope, arg);
    };
    Drupal.debounce(_cb, 201, true);
  }

  $.debounce = _debounce;

})(dBlazy, Drupal);
