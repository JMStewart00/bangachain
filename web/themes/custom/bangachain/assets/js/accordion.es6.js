/**
 * @file
 * Accordion icon change on click.
 */

(($, Drupal) => {
  Drupal.behaviors.accordionIconChange = {
    attach(context) {
      $(".c-accordion-item > a", context)
        .once("accordionIconChange")
        .each((i, el) => {
          const accordionItem = $(el);
          accordionItem.on("click", () => {
            const icon = accordionItem
              .find(".c-accordion-item__icon")
              .children();
            icon.toggleClass("fa-minus fa-plus");
          });
        });
    }
  };
})(jQuery, Drupal);
