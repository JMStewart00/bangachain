(function ($, Drupal, drupalSettings) {
    'use strict';

    var conn = new WebSocket(drupalSettings.commercePOSCustomerDisplayURL);

    conn.onopen = function (e) {
        var init = {
            "register_id": drupalSettings.commercePOSCustomerDisplayRegisterId,
            "type": "register",
            "display_type": "customer"
        };

        conn.send(JSON.stringify(init));
    };

    conn.onmessage = function (e) {
        var message = JSON.parse(e.data);

        if (message.register_id == drupalSettings.commercePOSCustomerDisplayRegisterId) {

            if (message.type == 'cashier') {
                $('.commerce-pos-customer-display__cashier__value').html(message.cashier);
            }
            if (message.type == 'display') {
                var product_output = '';

                // Setup basic line item layout and elements.
                message.display_items.forEach(function (item) {
                    product_output += '<div class="commerce-pos-customer-display__item">';
                    product_output += '<div class="commerce-pos-customer-display__item__image">';
                    product_output += '<svg class="icon-image-placeholder" width="1792" height="1792" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1757 1408l35 313q3 28-16 50-19 21-48 21h-1664q-29 0-48-21-19-22-16-50l35-313h1722zm-93-839l86 775h-1708l86-775q3-24 21-40.5t43-16.5h256v128q0 53 37.5 90.5t90.5 37.5 90.5-37.5 37.5-90.5v-128h384v128q0 53 37.5 90.5t90.5 37.5 90.5-37.5 37.5-90.5v-128h256q25 0 43 16.5t21 40.5zm-384-185v256q0 26-19 45t-45 19-45-19-19-45v-256q0-106-75-181t-181-75-181 75-75 181v256q0 26-19 45t-45 19-45-19-19-45v-256q0-159 112.5-271.5t271.5-112.5 271.5 112.5 112.5 271.5z"/></svg>';
                    product_output += '</div>';
                    product_output += '<div class="commerce-pos-customer-display__item__info-wrapper">';
                    product_output += '<div class="commerce-pos-customer-display__item__quantity">';
                    product_output += '<div class="commerce-pos-customer-display__item__quantity__label">' + Drupal.t("QTY:") + '</div>';
                    product_output += '<div class="commerce-pos-customer-display__item__quantity__number">' + item.quantity + ' X ' + item.unit_price + '</div>';
                    product_output += '</div>';
                    product_output += '<div class="commerce-pos-customer-display__item__total-price">' + item.total_price + '</div>';
                    product_output += item.product;
                    product_output += '</div>';
                    product_output += '</div>';
                });

                $('.commerce-pos-customer-display__items').html(product_output);

                // Move elements within layout.
                $('.commerce-pos-customer-display__item').each(function () {
                    var productImageWrapper = $(this).find('.commerce-pos-customer-display__item__image');
                    var productInfoWrapper = $(this).find('.commerce-pos-customer-display__item__info-wrapper');
                    var productQtyWrapper = $(this).find('.commerce-pos-customer-display__item__quantity');
                    var productImage = $(this).find('.field--type-image');
                    var productImagePlaceholder = $(this).find('.icon-image-placeholder');
                    var productTitle = $(this).find('.field--name-title');
                    var productSku = $(this).find('.field--name-sku');
                    var productUnitPrice = $(this).find('.field--name-price');

                    if (productImage.length) {
                        productImagePlaceholder.remove();
                        productImage.appendTo(productImageWrapper);
                    }
                    if (productSku.length) {
                        productSku.prependTo(productInfoWrapper);
                    }
                    if (productTitle.length) {
                        productTitle.prependTo(productInfoWrapper);
                    }
                    if (productUnitPrice.length) {
                        productUnitPrice.appendTo(productQtyWrapper);
                    }
                });

                // Fade in line items.
                $('.commerce-pos-customer-display__item').fadeIn('fast');

                var total_output = '';

                message.display_totals.subtotals.forEach(function (total) {
                    total_output += '<div class="commerce-pos-customer-display__total commerce-pos-customer-display__total--' + drupal_clean_css_identifier(total.label) + '">';
                    total_output += '<div class="commerce-pos-customer-display__total__label">';
                    total_output += '<span>' + total.label + '</span>';
                    total_output += '</div>';

                    total_output += '<div class="commerce-pos-customer-display__total__value">';
                    total_output += '<span>' + total.value + '</span>';
                    total_output += '</div>';
                    total_output += '</div>';
                });

                message.display_totals.payments.forEach(function (total) {
                    total_output += '<div class="commerce-pos-customer-display__total commerce-pos-customer-display__total--' + drupal_clean_css_identifier(total.label) + '">';
                    total_output += '<div class="commerce-pos-customer-display__payment__label">';
                    total_output += '<span>' + total.label + '</span>';
                    total_output += '</div>';

                    total_output += '<div class="commerce-pos-customer-display__payment__value">';
                    total_output += '<span>' + total.value + '</span>';
                    total_output += '</div>';
                    total_output += '</div>';
                });

                $('.commerce-pos-customer-display__totals').html(total_output);
                $('.commerce-pos-customer-display__total--to-pay .commerce-pos-customer-display__total__value').html(message.display_totals.to_pay.value);
                $('.commerce-pos-customer-display__total--total-paid .commerce-pos-customer-display__total__value').html(message.display_totals.total_paid.value);
                $('.commerce-pos-customer-display__total--change .commerce-pos-customer-display__total__value').html(message.display_totals.change.value);

            }
        }
    };

    var drupal_clean_css_identifier = function(id) {
        id = id.toLowerCase();
        id = id.replace(" ", "-").replace("_", "-").replace("[", "-").replace("]", "");

        // As defined in http://www.w3.org/TR/html4/types.html#type-name, HTML IDs can
        // only contain letters, digits ([0-9]), hyphens ("-"), underscores ("_"),
        // colons (":"), and periods ("."). We strip out any character not in that
        // list. Note that the CSS spec doesn't allow colons or periods in identifiers
        // (http://www.w3.org/TR/CSS21/syndata.html#characters), so we strip those two
        // characters as well.
        id = id.replace(/[^A-Za-z0-9\-_]+/gi, '', id);

        // Removing multiple consecutive hyphens.
        id = id.replace(/\-+/gi, '-', id);

        return id;
    };

}(jQuery, Drupal, drupalSettings));
