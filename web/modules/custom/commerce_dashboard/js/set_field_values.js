(function($) {
  Drupal.behaviors.commerceSetFieldValue = {
    attach: function (context, settings) {
      const plasticCosts = {
        'star': {
          cost: 8.00,
          price: 15.00
        },
        'gstar': {
          cost: 7.00,
          price: 16.00
        }
      }

      $('#edit-attribute-disc-plastic-0-target-id', context).change((e) => {
        const lastOpenParen = e.target.value.lastIndexOf(' ');
        const plastic = e.target.value.substring(0, lastOpenParen).toLowerCase();

        if (plastic && plasticCosts[plastic]) {
          $('#edit-price-0-number').val(plasticCosts[plastic].price);
          $('#edit-field-vendor-cost-0-value').val(plasticCosts[plastic].cost);
        } else {
          $('#edit-price-0-number').val('');
          $('#edit-field-vendor-cost-0-value').val('');
        }

      });

      $('#edit-attribute-disc-weight', context).change((e) => {
        const weight = $("#edit-attribute-disc-weight option:selected").text().substring(0,3);
        $('#edit-weight-0-number').val(weight);
      });
    }
  };
})(jQuery);
