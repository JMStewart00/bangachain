<?php

/**
 * @file
 * Contains install and update functions for Commerce Reports.
 */

/**
 * Add permission check to "Purchased Items Report".
 */
function commerce_reports_post_update_add_permission_to_purchased_items_report(&$sandbox) {
  $config_factory = \Drupal::configFactory();
  $view = $config_factory->getEditable('views.view.purchased_items_report');

  $access = [
    'type' => 'perm',
    'options' => [
      'perm' => 'access commerce reports',
    ],
  ];
  $view->set('display.default.display_options.access', $access);

  $dependencies = $view->get('dependencies.module');
  $dependencies[] = 'user';
  $view->set('dependencies.module', $dependencies);

  $data = $view->get('display.default.cache_metadata.contexts');
  $data[] = 'user.permissions';
  $view->set('display.default.cache_metadata.contexts', $data);

  $data = $view->get('display.page_1.cache_metadata.contexts');
  $data[] = 'user.permissions';
  $view->set('display.page_1.cache_metadata.contexts', $data);

  $view->save();
}
