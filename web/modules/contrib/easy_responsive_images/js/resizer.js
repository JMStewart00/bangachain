/**
 * @file
 * Attach the ResizeObserver to responsive images to load the best image style.
 */
(function (Drupal) {

  'use strict';

  Drupal.behaviors.easyResponsiveImages = {
    attach: function (context) {

      // Fetch all images containing a "data-srcset" attribute.
      const images = context.querySelectorAll('img[data-srcset]');

      // Create a ResizeObserver to update the image "src" attribute when its
      // parent container resizes.
      const observer = new ResizeObserver(entries => {
        for (let entry of entries) {
          const images = entry.target.querySelectorAll('img[data-srcset]');
          images.forEach(image => {
            const availableWidth = Math.floor(image.parentNode.clientWidth);
            const attrWidth = image.getAttribute('width');
            const sources = image.getAttribute('data-srcset').split(',');

            // If the selected image is already bigger than the available width,
            // we do not update the image.
            if (attrWidth && attrWidth > availableWidth) {
              return;
            }

            // Find the best matching source based on actual image space.
            let source, responsiveImgPath, responsiveImgWidth;
            for (source of sources) {
              let array = source.split(' ');
              responsiveImgPath = array[0];
              responsiveImgWidth = array[1].slice(0, -1);
              if (availableWidth < responsiveImgWidth) {
                break;
              }
            }

            // Update the "src" with the new image and also set the "width"
            // attribute to easily check if we need a new image after resize.
            image.setAttribute('src', responsiveImgPath);
            image.setAttribute('width', responsiveImgWidth);
          });
        }
      });

      // Attach the ResizeObserver to the image containers.
      images.forEach(image => {
        observer.observe(image.parentNode);
      });

    }
  };

})(Drupal);
