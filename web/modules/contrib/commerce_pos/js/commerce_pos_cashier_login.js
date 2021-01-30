(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.CommercePosCashierLogin = {
    attach: function (context, settings) {

      $('.commerce-pos-cashier-login').on('click','.commerce-pos-login__pane__toggle',function(e){
        $('.commerce-pos-login__pane--login').addClass('is-active')
        $(this).parents('.commerce-pos-login__pane').addClass('is-active').siblings().removeClass('is-active');
        e.preventDefault();
      });

      $('.commerce-pos-cashier-login').on('click','.commerce-pos-login__users-list__user',function(e){
        $('.commerce-pos-login__pane--login').addClass('is-active')
        $('.commerce-pos-login__pane--users').removeClass('is-active');
        $("input[name='name']").val($(this).html());
        e.preventDefault();
      });

      // Sending Ajax request to get the recent cashiers. If taking recent
      // cashiers from the backend ncorrect results are being displayed
      // because of Drupal 8 anonymous page cache.
      // Adding a cache context for the commerce_pos_cashiers cookie
      // will not help because the internal page cache does
      // not use cache contexts for anonymous users, hence creating the user list
      // from client side.
      var cashierUrl = drupalSettings.cashierUrl;
      $.ajax({
        url: cashierUrl,
        cache: false,
        success: function(html){
          $(".commerce-pos-login__content").append(html.data);
        }
      });
    }
  };
}(jQuery, Drupal, drupalSettings));
