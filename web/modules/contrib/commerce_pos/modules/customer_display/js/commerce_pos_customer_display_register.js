(function ($, Drupal, drupalSettings) {
    'use strict';

    var conn = new WebSocket(drupalSettings.commercePOSCustomerDisplayURL);

    conn.onopen = function (e) {
        var init = {
            "register_id": drupalSettings.commercePOSCustomerDisplayRegisterId,
            "type": "register",
            "display_type": "cashier",
            "cashier": drupalSettings.commercePOSCustomerDisplayCashierName
        };

        conn.send(JSON.stringify(init));
        mutationHandler();
    };

    var observer = new MutationObserver(function (e) {
        mutationHandler();
    });
    var config = {
        childList: true,
        characterData: true,
        subtree: true
    };

    Drupal.behaviors.CommercePosCustomerDisplayRegister = {
        attach: function (context, settings) {
            $('#commerce-pos-order-form-wrapper').once("commerce_pos_register_display_order").each(function () {
                observer.observe(this, config);
            });
            $('#commerce-pos-pay-form-wrapper').once("commerce_pos_register_display_payments").each(function () {
                observer.observe(this, config);
            });
        }
    };


    // Function that handles gathering all the relevant data and passing it along to the websocket server.
    function mutationHandler() {
        var subtotals = [];
        var payments = [];
        var total_paid = {};
        var to_pay = {};
        var change = {};

        var message = {
            'register_id': drupalSettings.commercePOSCustomerDisplayRegisterId,
            'type': 'update'
        }

        $('.commerce-pos--totals--totals tr').each(function () {
            var total = {
                'label': $(this).find('td:nth-child(1)').text(),
                'value': $(this).find('td:nth-child(2)').text()
            };
            subtotals.push(total);
        });

        $('.commerce-pos--totals--payments tr').each(function () {
            var total = {
                'label': $(this).find('td:nth-child(1)').text(),
                'value': $(this).find('td:nth-child(2)').text()
            };
            payments.push(total);
        });

        $('.commerce-pos--totals--total-paid').each(function () {
            total_paid = {
                'label': $(this).find('td:nth-child(1)').text(),
                'value': $(this).find('td:nth-child(2)').text()
            };
        });

        $('.commerce-pos--totals--to-pay').each(function () {
            to_pay = {
                'label': $(this).find('td:nth-child(1)').text(),
                'value': $(this).find('td:nth-child(2)').text()
            };
        });

        $('.commerce-pos--totals--change').each(function () {
            change = {
                'label': $(this).find('td:nth-child(1)').text(),
                'value': $(this).find('td:nth-child(2)').text()
            };
        });

        var display_totals = {
            'subtotals': subtotals,
            'payments': payments,
            'total_paid': total_paid,
            'to_pay': to_pay,
            'change': change
        };

        message.display_totals = display_totals;

        var items = [];

        $('[data-drupal-selector="edit-order-items-target-id-order-items"] tr').each(function () {
            var return_item = false;

            if ($(this).find('td:nth-child(5) input:checked').length === 1) {
                return_item = true;
            }

            var item = {
                'product': $(this).find('td:nth-child(1)').html(),
                'unit_price': $(this).find('td:nth-child(2) .commerce-pos-customer-display-unit-price-hidden').val(),
                'total_price': $(this).find('td:nth-child(2) .commerce-pos-customer-display-item-total-price-hidden').val(),
                'quantity': $(this).find('td:nth-child(3) input:hidden').val(),
                'return': return_item
            };

            if (typeof item.quantity !== 'undefined') {
                items.push(item);
            }
        });

        // If we are on the right page, cache the items in localStorage, otherwise get from cache.
        if ($('[data-drupal-selector="edit-order-items-target-id-order-items"] tr').length > 0 &&
            $('[data-drupal-selector="edit-actions-submit"]').length > 0) {
            message.display_items = items;
            localStorage.setItem("commerce_pos_items", JSON.stringify(items));
        } else {
            message.display_items = JSON.parse(localStorage.getItem("commerce_pos_items"));
        }

        if (conn.readyState === conn.OPEN) {
            conn.send(JSON.stringify(message));
        }
        else if (conn.readyState === conn.CLOSED) {
            conn.connect();
        }
    }
}(jQuery, Drupal, drupalSettings));
