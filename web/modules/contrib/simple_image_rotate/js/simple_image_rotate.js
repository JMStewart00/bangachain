/**
 * @file
 * A JavaScript file for the theme.
 *
 * In order for this JavaScript to be loaded on pages, see the instructions in
 * the README.txt next to this file.
 */

// JavaScript should be made compatible with libraries other than jQuery by
// wrapping it with an "anonymous closure". See:
// - https://drupal.org/node/1446420
// - http://www.adequatelygood.com/2010/3/JavaScript-Module-Pattern-In-Depth
(function ($, Drupal, window, document) {

    // To understand behaviors, see https://drupal.org/node/756722#behaviors
    Drupal.behaviors.rotateImage = {
        attach: function (context, settings) {

          // Leaving out absolute icon placement for now.
          // $('.rotate-icon').parent('.image-preview').addClass('rotate-enabled');
            // Treat click on rotate image icon
            $(".rotate-icon").once().click(function (e) {

                // Prevent default click on rotate icon
                e.preventDefault();
                // Current rotate value and thumbnail image
                var rotate = parseInt($(this).attr("data-rotate")),
                $image = $(this).closest(".image-widget").find("img");
                // Rotate Input
                $rotate_input = $(this).closest(".image-widget").find('.rotate');
                // Remove current rotate CSS class
                $image.removeClass("rotate-" + rotate);
                // Update rotate value
                rotate = rotate < 270 ? rotate + 90 : 0;
                // Add new rotate CSS class and update rotate value in elements
                $image.addClass("rotate-" + rotate);
                $rotate_input.attr("value", rotate);
                $(this).attr("data-rotate", rotate);
            });
        }
    };

})(jQuery, Drupal, this, this.document);
