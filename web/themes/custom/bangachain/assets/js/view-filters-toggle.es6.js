/**
 * @file
 * Accordion icon change on click.
 */

(($, Drupal) => {
  Drupal.behaviors.viewFiltersToggle = {
    attach(context) {
      $(".c-view-filters__toggle > button", context)
        .once("viewFiltersToggle")
        .each((i, el) => {
          const buttonToggle = $(el);
          buttonToggle.on("click", () => {
            console.log('click BITCH!');
            const filters = $('.l-grid__sidebar')
              .find(".c-view-filters")
            filters.toggleClass("open");
          });
        });
    }
  };
})(jQuery, Drupal);
