/**
 * @file
 * Setup the mmenu.
 * https://mmenujs.com/
 */

document.addEventListener("DOMContentLoaded", () => {
  /* eslint no-unused-vars: "off" */
  /* global Mmenu */

  const mobileMenu = new Mmenu(
    ".c-mobile-menu",
    {
      extensions: [
        "fullscreen",
        "multiline",
        "border-full",
        "position-front",
        "position-bottom",
        "border-full"
      ],
      height: 2,
      keyboardNavigation: {
        enable: true,
        enhance: true
      },
      setSelected: {
        hover: true
      }
    },
    {
      // configuration
      offCanvas: {
        page: {
          selector: "#layout-container"
        }
      }
    }
  );

});
