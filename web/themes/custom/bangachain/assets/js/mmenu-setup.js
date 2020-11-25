"use strict";

/**
 * @file
 * Setup the mmenu.
 * https://mmenujs.com/
 */
document.addEventListener("DOMContentLoaded", function () {
  /* eslint no-unused-vars: "off" */

  /* global Mmenu */
  var mobileMenu = new Mmenu(".c-mobile-menu", {
    extensions: ["fullscreen", "multiline", "border-full", "position-front", "position-bottom", "border-full"],
    height: 2,
    keyboardNavigation: {
      enable: true,
      enhance: true
    },
    setSelected: {
      hover: true
    }
  }, {
    // configuration
    offCanvas: {
      page: {
        selector: "#layout-container"
      }
    }
  });
});