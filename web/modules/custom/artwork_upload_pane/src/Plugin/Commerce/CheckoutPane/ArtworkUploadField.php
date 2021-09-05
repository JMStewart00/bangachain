<?php

namespace Drupal\artwork_upload_pane\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Provides a pane to upload images for projects.
 *
 * @CommerceCheckoutPane(
 *   id = "artwork_upload_pane_file_upload",
 *   label = @Translation("Artwork Upload Pane"),
 * )
 */
class ArtworkUploadField extends CheckoutPaneBase {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {

    // Get all items in the order and loop through them adding an upload field for each item

    foreach ($this->order->getItems() as $item) {

      $form['file_upload_'. $item->id()] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Upload file'),
        '#upload_location' => 'private://client_uploads', // Can also use 'public://'
        '#required' => TRUE,
        '#upload_validators' => [
          'file_validate_extensions' => ['zip, jpg, jpeg, png'], // Limit to any file extension(s) here
        ],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    // Get the pane values
    $pane = $form_state->getValue('artwork_upload_pane_file_upload'); // The id we annotated earlier

    // Loop through each item on the order
    foreach ($this->order->getItems() as $item) {

      // Get the uploaded file
      $fid = $pane['file_upload_'.$item->id()][0];
      // Make the file managed
      $file = File::load($fid);
      $file->setPermanent();
      $file->save();

      // Add the file id to the file reference field on the order item
      $item->set('field_file_upload', $file->id());
      $item->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isVisible() {
    // Check whether the order has an item that requires the disclaimer message.
    foreach ($this->order->getItems() as $order_item) {
      $purchased_entity = $order_item->getPurchasedEntity();
      if ($purchased_entity->hasField('field_needs_artwork') && $purchased_entity->get('field_needs_artwork')->value == 1) {
        return TRUE;
      }
    }

    return FALSE;
  }
}
