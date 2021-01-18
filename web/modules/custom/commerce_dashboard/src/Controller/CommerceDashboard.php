<?php

namespace Drupal\commerce_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * The CommerceDashboard controller.
 */
class CommerceDashboard extends ControllerBase {

  /**
   * Returns a render-able array for a test page.
   */
  public function dashboard() {
    return [
      '#markup' => '<div id="commerce-dashboard"></div>',
      '#attached' => [
        'library' => [
          'commerce_dashboard/vue.js',
          'commerce_dashboard/vuetify.js',
          'commerce_dashboard/moment.js',
          'commerce_dashboard/axios.js',
          'commerce_dashboard/commerce_dashboard.js',
          'commerce_dashboard/materialdesignicons.css',
          'commerce_dashboard/vuetify.css',
          'commerce_dashboard/robotofont.css',
        ],
      ],
    ];
  }

}
