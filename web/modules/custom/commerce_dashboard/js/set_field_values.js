(function ($) {
  Drupal.behaviors.commerceSetFieldValue = {
    attach: function (context, settings) {
      const plasticCosts = {
        "prime": {
          cost: 5,
          price: 10
        },
        "origio": {
          cost: 5,
          price: 10
        },
        "retro": {
          cost: 5,
          price: 10
        },
        "prime burst": {
          cost: 5,
          price: 10
        },
        "origio burst": {
          cost: 5,
          price: 10
        },
        "retro burst": {
          cost: 5,
          price: 10
        },
        "prime moonshine": {
          cost: 5.5,
          price: 11
        },
        "origio moonshine": {
          cost: 5.5,
          price: 11
        },
        "retro moonshine": {
          cost: 5.5,
          price: 11
        },
        "prime burst moonshine": {
          cost: 6,
          price: 12
        },
        "origio burst moonshine": {
          cost: 6,
          price: 12
        },
        "retro burst moonshine": {
          cost: 6,
          price: 12
        },
        "bt soft": {
          cost: 6.5,
          price: 12
        },
        "bt soft burst": {
          cost: 6.5,
          price: 12
        },
        "bt medium": {
          cost: 6.5,
          price: 12
        },
        "bt medium burst": {
          cost: 6.5,
          price: 12
        },
        "bt hard": {
          cost: 6.5,
          price: 12
        },
        "bt hard burst": {
          cost: 6.5,
          price: 12
        },
        "bt moonshine": {
          cost: 6.5,
          price: 15
        },
        "zero soft": {
          cost: 6.5,
          price: 12
        },
        "zero soft burst": {
          cost: 6.5,
          price: 12
        },
        "zero medium": {
          cost: 6.5,
          price: 12
        },
        "zero medium burst": {
          cost: 6.5,
          price: 12
        },
        "zero hard": {
          cost: 6.5,
          price: 12
        },
        "zero hard burst": {
          cost: 6.5,
          price: 12
        },
        "zero moonshine": {
          cost: 6.5,
          price: 15
        },
        "classic": {
          cost: 6.5,
          price: 12
        },
        "classic blend": {
          cost: 6.5,
          price: 12
        },
        "classic blend burst": {
          cost: 6.5,
          price: 12
        },
        "classic soft": {
          cost: 6.5,
          price: 12
        },
        "classic soft burst": {
          cost: 6.5,
          price: 12
        },
        "biofuzion": {
          cost: 7.5,
          price: 15
        },
        "recycled": {
          cost: 7.5,
          price: 15
        },
        "lucid": {
          cost: 9,
          price: 16
        },
        "lucid air": {
          cost: 9,
          price: 16
        },
        "fluid": {
          cost: 9,
          price: 16
        },
        "opto": {
          cost: 9,
          price: 16
        },
        "opto air": {
          cost: 9,
          price: 16
        },
        "frost": {
          cost: 9,
          price: 16
        },
        "vip": {
          cost: 9,
          price: 16
        },
        "vip air": {
          cost: 9,
          price: 16
        },
        "fuzion": {
          cost: 9.5,
          price: 18
        },
        "fuzion burst": {
          cost: 9.5,
          price: 18
        },
        "gold": {
          cost: 9.5,
          price: 18
        },
        "gold burst": {
          cost: 9.5,
          price: 18
        },
        "tournament": {
          cost: 9.5,
          price: 18
        },
        "tournament burst": {
          cost: 9.5,
          price: 18
        },
        "2k opto-g": {
          cost: 9.5,
          price: 18
        },
        "mydye": {
          cost: 11,
          price: 20
        },
        "lucid glimmer": {
          cost: 11,
          price: 20
        },
        "opto glimmer": {
          cost: 11,
          price: 20
        },
        "vip glimmer": {
          cost: 11,
          price: 20
        },
        "moonshine": {
          cost: 11,
          price: 20
        },
        "dyemax": {
          cost: 11,
          price: 20
        },
        "decodye": {
          cost: 11,
          price: 20
        },
        "lucid-x": {
          cost: 13,
          price: 25
        },
        "opto-x": {
          cost: 13,
          price: 25
        },
        "vip-x": {
          cost: 13,
          price: 25
        },
        "fuzion-x": {
          cost: 13,
          price: 25
        },
        "gold-x": {
          cost: 13,
          price: 25
        },
        "tournament-x": {
          cost: 13,
          price: 25
        },
        "prime starter": {
          cost: 13,
          price: 25
        },
        "retro starter": {
          cost: 13,
          price: 25
        },
        "prime starter with bag": {
          cost: 20,
          price: 40
        },
        "opto starter": {
          cost: 25,
          price: 50
        },
        "dynamic ultimate": {
          cost: 6,
          price: 12
        },
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
        const weight = $("#edit-attribute-disc-weight option:selected").text().substring(0, 3);
        $('#edit-weight-0-number').val(weight);
      });
    }
  };
})(jQuery);
