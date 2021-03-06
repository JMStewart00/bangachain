(function ($) {
  Drupal.behaviors.commerceSetFieldValue = {
    attach: function (context, settings) {
      // const plasticCosts = {
      //   "200": { cost: 4.75, price: 9 },
      //   "2k opto-g": { cost: 9.5, price: 18 },
      //   "2k opto-g": { cost: 9.5, price: 18 },
      //   "300 soft": { cost: 7, price: 14 },
      //   "300 spectrum": { cost: 9.5, price: 18 },
      //   "300": { cost: 7, price: 13 },
      //   "350": { cost: 7, price: 13 },
      //   "350g": { cost: 7.5, price: 14 },
      //   "400 glimmer": { cost: 9, price: 18 },
      //   "400 glow": { cost: 10, price: 20 },
      //   "400 spectrum": { cost: 10.5, price: 21 },
      //   "400": { cost: 8, price: 15 },
      //   "400g": { cost: 9.25, price: 18 },
      //   "500 spectrum": { cost: 11.75, price: 20 },
      //   "500": { cost: 9.25, price: 18 },
      //   "750 spectrum": { cost: 12, price: 24 },
      //   "750": { cost: 9.5, price: 18 },
      //   "750g": { cost: 10.5, price: 21 },
      //   "active basic": { cost: 4, price: 8 },
      //   "active premium": { cost: 7.75, price: 15 },
      //   "basegrip color glow": { cost: 6.29, price: 12 },
      //   "basegrip": { cost: 5, price: 9 },
      //   "biofuzion": { cost: 7.5, price: 15 },
      //   "blizzard champion": { cost: 9, price: 16 },
      //   "bt hard burst": { cost: 6.5, price: 12 },
      //   "bt hard": { cost: 6.5, price: 12 },
      //   "bt medium burst": { cost: 6.5, price: 12 },
      //   "bt medium": { cost: 6.5, price: 12 },
      //   "bt moonshine": { cost: 6.5, price: 15 },
      //   "bt soft burst": { cost: 6.5, price: 12 },
      //   "bt soft": { cost: 6.5, price: 12 },
      //   "c-line color glow": { cost: 10, price: 20 },
      //   "c-line": { cost: 8.5, price: 16 },
      //   "champion": { cost: 9, price: 16 },
      //   "classic blend burst": { cost: 6.5, price: 12 },
      //   "classic blend": { cost: 6.5, price: 12 },
      //   "classic soft burst": { cost: 6.5, price: 12 },
      //   "classic soft": { cost: 6.5, price: 12 },
      //   "classic": { cost: 6.5, price: 12 },
      //   "cosmic electron": { cost: 7.5, price: 14 },
      //   "cosmic neutron": { cost: 10, price: 20 },
      //   "d-line": { cost: 4.4, price: 10 },
      //   "decodye": { cost: 11, price: 20 },
      //   "duraflex color glow": { cost: 9, price: 18 },
      //   "duraflex": { cost: 7.5, price: 15 },
      //   "dx": { cost: 5, price: 10 },
      //   "dyemax": { cost: 11, price: 20 },
      //   "dynamic ultimate": { cost: 6, price: 12 },
      //   "echo": { cost: 8.5, price: 16 },
      //   "eclipse 2.0": { cost: 11, price: 20 },
      //   "electron": { cost: 7, price: 12 },
      //   "evo": { cost: 10, price: 20 },
      //   "exo": { cost: 6, price: 12 },
      //   "fission":{ cost: 10, price: 19 },
      //   "fluid": { cost: 9, price: 16 },
      //   "forge neo": { cost: 10.25, price: 21 },
      //   "frost": { cost: 9, price: 16 },
      //   "fuzion burst": { cost: 9.5, price: 18 },
      //   "fuzion-x": { cost: 13, price: 25 },
      //   "fuzion": { cost: 9.5, price: 18 },
      //   "g-line": { cost: 9, price: 17 },
      //   "glow c-line": { cost: 10.5, price: 18 },
      //   "glow dx": { cost: 6.75, price: 12 },
      //   "gold burst": { cost: 9.5, price: 18 },
      //   "gold-x": { cost: 13, price: 25 },
      //   "gold": { cost: 9.5, price: 18 },
      //   "i-dye blizzard": { cost: 11, price: 19 },
      //   "i-dye champion": { cost: 11, price: 19 },
      //   "i-dye pro": { cost: 9.5, price: 17 },
      //   "i-dye star": { cost: 11.5, price: 20 },
      //   "i-dye starlite": { cost: 11.5, price: 20 },
      //   "jk pro": { cost: 7, price: 14 },
      //   "kc pro": { cost: 7, price: 14 },
      //   "lucid air": { cost: 9, price: 16 },
      //   "lucid glimmer": { cost: 11, price: 20 },
      //   "lucid-x": { cost: 13, price: 25 },
      //   "lucid": { cost: 9, price: 16 },
      //   "luster c-line": { cost: 9, price: 17 },
      //   "lux": { cost: 10.25, price: 20 },
      //   "mf c-line": { cost: 9, price: 17 },
      //   "moonshine": { cost: 11, price: 20 },
      //   "mydye": { cost: 11, price: 20 },
      //   "neo": { cost: 10.25, price: 19 },
      //   "neutron": { cost: 9.25, price: 18 },
      //   "opto air": { cost: 9, price: 16 },
      //   "opto glimmer": { cost: 11, price: 20 },
      //   "opto starter": { cost: 25, price: 50 },
      //   "opto-x": { cost: 13, price: 25 },
      //   "opto": { cost: 9, price: 16 },
      //   "origio burst moonshine": { cost: 6, price: 12 },
      //   "origio burst": { cost: 5, price: 10 },
      //   "origio moonshine": { cost: 5.5, price: 11 },
      //   "origio": { cost: 5, price: 10 },
      //   "p-line": { cost: 6.5, price: 15 },
      //   "plasma": { cost: 10, price: 19 },
      //   "prime burst moonshine": { cost: 6, price: 12 },
      //   "prime burst": { cost: 5, price: 10 },
      //   "prime moonshine": { cost: 5.5, price: 11 },
      //   "prime starter with bag": { cost: 20, price: 40 },
      //   "prime starter": { cost: 13, price: 25 },
      //   "prime": { cost: 5, price: 10 },
      //   "pro": { cost: 7.5, price: 15 },
      //   "proton": { cost: 8.75, price: 17 },
      //   "r-pro": { cost: 7, price: 14 },
      //   "recycled": { cost: 7.5, price: 15 },
      //   "recycled": { cost: 7.5, price: 15 },
      //   "retro burst moonshine": { cost: 6, price: 12 },
      //   "retro burst": { cost: 5, price: 10 },
      //   "retro moonshine": { cost: 5.5, price: 11 },
      //   "retro starter": { cost: 13, price: 25 },
      //   "retro": { cost: 5, price: 10 },
      //   "s-line": { cost: 9, price: 17 },
      //   "star": { cost: 9.5, price: 17 },
      //   "starlite": { cost: 9.5, price: 17 },
      //   "swirl s-line": { cost: 12.5, price: 20 },
      //   "tournament burst": { cost: 9.5, price: 18 },
      //   "tournament-x": { cost: 13, price: 25 },
      //   "tournament": { cost: 9.5, price: 18 },
      //   "unique blend jawbreaker": { cost: 10, price: 20 },
      //   "vip air": { cost: 9, price: 16 },
      //   "vip glimmer": { cost: 11, price: 20 },
      //   "vip-x": { cost: 13, price: 25 },
      //   "vip": { cost: 9, price: 16 },
      //   "x-line": { cost: 6.5, price: 15 },
      //   "yeti pro": { cost: 7, price: 14 },
      //   "zero hard burst": { cost: 6.5, price: 12 },
      //   "zero hard": { cost: 6.5, price: 12 },
      //   "zero medium burst": { cost: 6.5, price: 12 },
      //   "zero medium": { cost: 6.5, price: 12 },
      //   "zero moonshine": { cost: 6.5, price: 15 },
      //   "zero soft burst": { cost: 6.5, price: 12 },
      //   "zero soft": { cost: 6.5, price: 12 },
      // }

      $('#edit-attribute-disc-plastic-0-target-id', context).change((e) => {
        const lastOpenParen = e.target.value.lastIndexOf(' ');
        const plastic = e.target.value.substring(0, lastOpenParen).toLowerCase();

        $.ajax({
          url: `/jsonapi/commerce_product_attribute_value/disc_plastic?filter[name]=${plastic}`,
          type: "GET",
          dataType: "json",
          success: (data) => {
            let plastic = false;
            if (data.data.length > 0) {
              plastic = data.data[0].attributes;
            }

            if (plastic) {
              if (!plastic.field_price) {
                alert('You have not set the vendor cost and prices for this plastic yet!');
              }

              $('#edit-price-0-number').val(plastic.field_price || '');
              $('#edit-field-vendor-cost-0-value').val(plastic.field_vendor_cost || '');
            } else {
              $('#edit-price-0-number').val('');
              $('#edit-field-vendor-cost-0-value').val('');
            }
          },
          error: (err) => {
            console.warn('Something went wrong.', err);
          }
        })

      });

      $('#edit-attribute-disc-weight', context).change((e) => {
        const weight = $("#edit-attribute-disc-weight option:selected").text().substring(0, 3);
        $('#edit-weight-0-number').val(weight);
      });
    }
  };
})(jQuery);
