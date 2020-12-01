/**
 * @file
 * Accordion icon change on click.
 */

(($, Drupal) => {
  Drupal.behaviors.defaultImagePlacement = {
    attach(context) {

      $('body', context)
        .once("defaultImagePlacement")
        .on('DOMSubtreeModified', '.c-product__image', () => {
          console.log('changed');
        });
    }
  };
})(jQuery, Drupal);
