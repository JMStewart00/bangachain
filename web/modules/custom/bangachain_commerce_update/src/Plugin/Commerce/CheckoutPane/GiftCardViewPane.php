<?php

namespace Drupal\bangachain_commerce_update\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;

/**
 * Provides a view of current user's giftcards.
 *
 * @CommerceCheckoutPane(
 *   id = "giftcards_view_pane",
 *   label = @Translation("Display Gift Cards for User"),
 * )
 */
class GiftCardViewPane extends CheckoutPaneBase {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $user_id = \Drupal::currentUser()->id();
    $view = Views::getView('gift_card_balances');

    $pane_form['giftcard_view'] = $view->buildRenderable('block_2', [$user_id]);

    return $pane_form;
  }

}
