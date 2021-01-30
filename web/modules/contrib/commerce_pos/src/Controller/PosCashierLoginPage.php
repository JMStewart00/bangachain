<?php

namespace Drupal\commerce_pos\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Url;

/**
 * Provides route responses for the Cashier Login Page.
 */
class PosCashierLoginPage extends ControllerBase {

  /**
   * Returns a customer cashier login page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function login() {
    if ($this->currentUser()->isAuthenticated()) {
      user_logout();
      $this->messenger()->addMessage(t("You've been logged out"));
    }
    /** @var \Drupal\commerce_pos\RecentCashiers $recent_cashiers */
    $recent_cashiers = \Drupal::service('commerce_pos.recent_cashiers');

    /* @var $register \Drupal\commerce_pos\Entity\Register */
    $register = \Drupal::service('commerce_pos.current_register')->get();
    if (isset($register)) {
      $store = $register->getStore();
    }
    else {
      /** @var \Drupal\commerce_store\StoreStorageInterface $store_storage */
      $store_storage = \Drupal::entityTypeManager()->getStorage('commerce_store');
      $store = $store_storage->loadDefault();
    }

    $status_messages = ['#type' => 'status_messages'];
    $messages = \Drupal::service('renderer')->renderRoot($status_messages);

    // Delete messages now that we've grabbed them, so they don't keep showing.
    $messenger = \Drupal::messenger();
    $messenger->deleteAll();

    $login_background = '/' . drupal_get_path('module', 'commerce_pos') . '/images/bg_cashier_login.jpg';
    if ($this->config('commerce_pos.settings')->get('look_and_feel_login_bg')) {
      $login_background = file_create_url(File::load($this->config('commerce_pos.settings')
        ->get('look_and_feel_login_bg'))->getFileUri());
    }

    $cashier_users_url = Url::fromRoute('commerce_pos.login_users');
    $cashier_users_url = $cashier_users_url->toString();
    $page = [
      '#type' => 'page',
      '#theme' => 'commerce_pos_cashier_login_page',
      '#form' => \Drupal::formBuilder()->getForm('Drupal\commerce_pos\Form\CashierForm'),
      '#messages' => $messages,
      '#store_name' => $store->getName(),
      '#login_background' => $login_background,
      '#attached' => [
        'library' => [
          'commerce_pos/cashier_login',
        ],
        'drupalSettings' => [
          'cashierUrl' => $cashier_users_url,
        ],
      ],
    ];

    return $page;
  }

  /**
   * Returns recent cashiers list.
   *
   * @return json
   *   A simple json object with recent cashiers data.
   */
  public function getRecentCashiers() {
    $cashiers = [];
    /** @var \Drupal\commerce_pos\RecentCashiers $recent_cashiers */
    $recent_cashiers = \Drupal::service('commerce_pos.recent_cashiers');
    if (!empty($recent_cashiers->get())) {
      $cashiers = [
        '#theme' => 'commerce_pos_cashier_login_recent_cashiers',
        '#cashiers' => $recent_cashiers->get(),
      ];
      $cashiers = \Drupal::service('renderer')->render($cashiers);
    }
    $response['data'] = $cashiers;
    return new JsonResponse($response);
  }

}
