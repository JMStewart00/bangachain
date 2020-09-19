<?php

/**
 * @file
 * Contains sitewide_alert.page.inc.
 *
 * Page callback for Sitewide Alert entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Sitewide Alert templates.
 *
 * Default template: sitewide_alert.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_sitewide_alert(array &$variables) {
  // Fetch SitewideAlert Entity Object.
  $sitewide_alert = $variables['elements']['#sitewide_alert'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
