<?php

namespace Drupal\commerce_wishlist\Form;

use Drupal\commerce_wishlist\Entity\WishlistItemInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxFormHelperTrait;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the wishlist details form.
 */
class WishlistItemDetailsForm extends EntityForm {

  use AjaxFormHelperTrait;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item */
    $wishlist_item = $this->entity;

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    // Workaround for core bug #2897377.
    $form['#id'] = Html::getId($form_state->getBuildInfo()['form_id']);

    $form['comment'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Comment'),
      '#rows' => 5,
      '#default_value' => $wishlist_item->getComment(),
    ];
    $form['quantity'] = [
      '#type' => 'commerce_number',
      '#title' => $this->t('Quantity'),
      '#required' => TRUE,
      '#default_value' => $wishlist_item->getQuantity(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update details'),
      '#submit' => ['::submitForm'],
    ];
    if ($this->isAjax()) {
      $actions['submit']['#ajax']['callback'] = '::ajaxSubmit';
    }

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    /** @var \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item */
    $wishlist_item = $this->entity;
    $wishlist_item->save();

    $wishlist = $wishlist_item->getWishlist();
    $form_state->setRedirectUrl($wishlist->toUrl('user-form'));
  }

  /**
   * {@inheritdoc}
   */
  protected function successfulAjaxSubmit(array $form, FormStateInterface $form_state) {
    // We need to clear parent wishlist cache, so that on refresh
    // we are able to see changes also, not only after ajax updates.
    $wishlist = $this->entity->getWishlist();
    Cache::invalidateTags($wishlist->getCacheTags());

    $response = new AjaxResponse();
    $response->addCommand(new PrependCommand('.commerce-wishlist-form', ['#type' => 'status_messages']));
    $response->addCommand(new ReplaceCommand('#wishlist-item-details-' . $this->entity->id(), [
      '#theme' => 'commerce_wishlist_item_details',
      '#wishlist_item_entity' => $this->entity,
    ]));
    $response->addCommand(new CloseDialogCommand());

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    assert($entity instanceof WishlistItemInterface);
    $values = $form_state->getValues();
    unset($values['action']);
    foreach ($values as $key => $value) {
      if ($entity->hasField($key)) {
        $entity->set($key, $value);
      }
    }
  }

}
