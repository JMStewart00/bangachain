<?php

namespace Drupal\iframe\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class IframeUrlheightWidget.
 *
 * @FieldWidget(
 *  id = "iframe_urlheight",
 *  label = @Translation("URL with height"),
 *  field_types = {"iframe"}
 * )
 */
class IframeUrlheightWidget extends IframeWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $this->allowedAttributes['width'] = 0;
    $elements = parent::formElement($items, $delta, $element, $form, $form_state);
    // Dont show, only save default value.
    $elements['width']['#type'] = 'value';
    unset($element['width']['#required']);

    return $elements;
  }

}
