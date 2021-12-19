"use strict";

(function ($, Drupal) {
  var thumbnailSlider;
  var gallerySlider;
  $(document).ready(function () {
    // Create and mount the thumbnails slider.
    thumbnailSlider = new Splide('#thumbnail-slider', {
      rewind: true,
      fixedWidth: 100,
      isNavigation: true,
      gap: 10,
      focus: 'center',
      pagination: false,
      cover: false,
      perPage: 4,
      updateOnMove: true,
      drag: true,
      breakpoints: {
        '600': {
          fixedWidth: 66
        }
      }
    });
    thumbnailSlider.on('active', function (Slide) {
      var variationSelect = document.querySelector('select[name="purchased_entity[0][variation]"]');
      var variationId = Slide.slide.dataset.variationId;
      var opts = variationSelect.options;

      for (var opt, j = 0; opt = opts[j]; j++) {
        if (opt.value == variationId) {
          variationSelect.selectedIndex = j;
          variationSelect.dispatchEvent(new Event('change'));
          break;
        }
      }
    }); // Create the main slider.

    gallerySlider = new Splide('#gallery-slider', {
      type: 'fade',
      pagination: false,
      arrows: false,
      cover: false,
      drag: true
    }); // Set the thumbnails slider as a sync target and then call mount.

    gallerySlider.sync(thumbnailSlider);
    gallerySlider.mount();
    thumbnailSlider.mount();
  }); // Product Image Slider Ajax.

  Drupal.behaviors.productSlider = {
    attach: function attach(context, settings) {
      // Commerce add to cart form.
      $('form.commerce-order-item-add-to-cart-form', context).once('productSlider').each(function (i, item) {
        // Grab the current product variation ID.
        var current_product_variation_id = $(item).data('product-variation-id'); // Determine the gallery image that matches (if any)

        if ($('#thumbnail-slider').length && $('#thumbnail-slider .splide__slide[data-variation-id*="' + current_product_variation_id + '"]').length) {
          var $nav_item = $('#thumbnail-slider .splide__slide[data-variation-id*="' + current_product_variation_id + '"]');
          var item_position = $nav_item.index(); // Trigger Splide to go to current variation image.

          thumbnailSlider.go(item_position);
        }
      });
    }
  };
})(jQuery, Drupal);