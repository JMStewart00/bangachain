<?php

namespace Drupal\commerce_loyalty_points\Form;

use Drupal\commerce_loyalty_points\Entity\LoyaltyPoints;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the commerce_loyalty_points entity forms.
 *
 * @ingroup commerce_loyalty_points
 */
class LoyaltyPointsForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_loyalty_points\Entity\LoyaltyPoints $entity */
    $entity = $this->getEntity();
    $entity->save();

    // Redirect to view page.
    $user = $entity->getUser();
    $form_state->setRedirect('entity.commerce_loyalty_points.collection', [], [
      'query' => [
        'uid' => $user->getUsername() . ' (' . $user->id() . ')',
      ],
    ]);
  }

}
