"use strict";

(function ($, Drupal, once) {
  function init(props) {
    // Grab the current product variation ID.
    if (props.productForm) {
      var current_product_variation_id = props.productForm.dataset.productVariationId; // Determine the gallery image that matches (if any)

      if ($('#thumbnail-slider').length && $('#thumbnail-slider .splide__slide[data-variation-id*="' + current_product_variation_id + '"]').length) {
        var $nav_item = $('#thumbnail-slider .splide__slide[data-variation-id*="' + current_product_variation_id + '"]');
        var item_position = $nav_item.index(); // Trigger Splide to go to current variation image.

        props.thumbnailSlider.go(item_position);
      }
    }
  }

  var thumbnailSlider;
  var gallerySlider;
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

    if (variationSelect !== null) {
      var variationId = Slide.slide.dataset.variationId;
      var opts = variationSelect.options;

      for (var opt, j = 0; opt = opts[j]; j++) {
        if (opt.value == variationId) {
          variationSelect.selectedIndex = j;
          variationSelect.dispatchEvent(new Event('change'));
          break;
        }
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
  thumbnailSlider.mount(); // Product Image Slider Ajax.

  Drupal.behaviors.productSlider = {
    attach: function attach(context) {
      var productForm = once('form', "form.commerce-order-item-add-to-cart-form", context).shift();
      init({
        productForm: productForm,
        gallerySlider: gallerySlider,
        thumbnailSlider: thumbnailSlider
      });
    }
  };
})(jQuery, Drupal, once);