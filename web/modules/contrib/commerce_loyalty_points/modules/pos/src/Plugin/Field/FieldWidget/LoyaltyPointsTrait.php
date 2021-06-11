<?php

namespace Drupal\commerce_pos_loyalty_points_support\Plugin\Field\FieldWidget;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Field widget trait for loyalty points.
 */
trait LoyaltyPointsTrait {

  /**
   * Display loyalty points of a customer.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function displayLoyaltyPoints(array &$form, FormStateInterface $form_state) {
    $message = t('No loyalty points found.');
    if (isset($form_state->getValue('uid')[0]['target_id'])) {
      $uid = $form_state->getValue('uid')[0]['target_id'];
      if ($uid) {
        $message = $this->getLoyaltyPoints($uid);
      }
    }
    else {
      $message = t('User not found');
    }

    // Return ajax response.
    $ajax_response = new AjaxResponse();
    return $ajax_response->addCommand(new HtmlCommand('#customer-loyalty-points', $message));
  }

  /**
   * Fetch loyalty points of a customer.
   *
   * @param int $uid
   *   User ID.
   *
   * @return mixed
   *   Loyalty points of a user.
   */
  protected function getLoyaltyPoints($uid) {
    $user = User::load($uid);

    /** @var \Drupal\commerce_loyalty_points\LoyaltyPointsStorageInterface $loyalty_points_storage */
    $loyalty_points_storage = \Drupal::entityTypeManager()->getStorage('commerce_loyalty_points');
    $points = $loyalty_points_storage->loadAndAggregateUserLoyaltyPoints($uid);

    $key = 'pos_aggregate';
    \Drupal::moduleHandler()->alter('loyalty_points_view', $points, $key);

    $message['#markup'] = '<hr>';
    $message['#markup'] .= '<h4>' . t('Loyalty points for @user: @points', [
      '@user' => $user->getUsername(),
      '@points' => $points,
    ]) . '</h4>';
    $message['#markup'] .= t('Subscription status: <strong>@sub_status</strong>', [
      '@sub_status' => $user->hasRole('loyalty_points_subscriber') ? t('Active') : t('Inactive'),
    ]);

    return \Drupal::service('renderer')->render($message);
  }

}
