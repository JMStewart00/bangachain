<?php

namespace Drupal\commerce_wishlist\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides an wishlist type form.
 */
class WishlistTypeForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\commerce_wishlist\Entity\WishlistTypeInterface $wishlist_type */
    $wishlist_type = $this->entity;

    $form['#tree'] = TRUE;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $wishlist_type->label(),
      '#description' => $this->t('Label for the wishlist type.'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $wishlist_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_wishlist\Entity\WishlistType::load',
        'source' => ['label'],
      ],
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
    ];

    $form['allowAnonymous'] = [
      '#type' => 'checkbox',
      '#default_value' => $wishlist_type->isAllowAnonymous(),
      '#title' => $this->t('Allow anonymous wishlists'),
    ];

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_wishlist\Entity\WishlistTypeInterface $wishlist_type */
    $wishlist_type = $this->entity;
    $status = $wishlist_type->save();
    $this->messenger()->addStatus($this->t('Saved the %label wishlist type.', ['%label' => $wishlist_type->label()]));
    $form_state->setRedirect('entity.commerce_wishlist_type.collection');
  }

}
