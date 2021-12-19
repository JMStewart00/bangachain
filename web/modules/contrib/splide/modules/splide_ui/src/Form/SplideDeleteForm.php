<?php

namespace Drupal\splide_ui\Form;

use Drupal\Core\Url;

/**
 * Builds the form to delete a Splide optionset.
 */
class SplideDeleteForm extends SplideDeleteFormBase {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.splide.collection');
  }

}
