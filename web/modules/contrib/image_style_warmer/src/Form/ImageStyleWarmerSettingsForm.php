<?php

namespace Drupal\image_style_warmer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for creating and editing Image Style Warmer settings.
 */
class ImageStyleWarmerSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'image_style_warmer_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['image_style_warmer.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('image_style_warmer.settings');
    $imageStyleOptions = image_style_options(FALSE);

    $initialImageStyles = $config->get('initial_image_styles');
    $form['initial_image_styles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Initial image styles'),
      '#description' => $this->t('Select image styles which will be created initial for an image.'),
      '#options' => $imageStyleOptions,
      '#default_value' => !empty($initialImageStyles) ? $initialImageStyles : [],
      '#size' => 10,
    ];
    $form['initial_image_styles']['#attributes']['class'][] = 'image-style-warmer';
    $form['initial_image_styles']['#attributes']['class'][] = 'image-styles';
    $form['initial_image_styles']['#attributes']['class'][] = 'initial-image-styles';

    $queueImageStyles = $config->get('queue_image_styles');
    $form['queue_image_styles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Queue image styles'),
      '#description' => $this->t('Select image styles which will be created via queue worker.'),
      '#options' => $imageStyleOptions,
      '#default_value' => !empty($queueImageStyles) ? $queueImageStyles : [],
      '#size' => 10,
    ];
    $form['queue_image_styles']['#attributes']['class'][] = 'image-style-warmer';
    $form['queue_image_styles']['#attributes']['class'][] = 'image-styles';
    $form['queue_image_styles']['#attributes']['class'][] = 'queue-image-styles';

    // Add CSS for better form layout.
    $form['#attached']['library'][] = 'image_style_warmer/image_style_warmer.admin';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $form_state->setValue('initial_image_styles', array_filter($form_state->getValue('initial_image_styles')));
    $form_state->setValue('queue_image_styles', array_filter($form_state->getValue('queue_image_styles')));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('image_style_warmer.settings');
    $form_state->cleanValues();
    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
