<?php

namespace Drupal\commerce_pos_customer_display\Form;

use Drupal\commerce_pos\Entity\Register;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Show a form to select the current register for this session.
 */
class RegisterSelectForm extends FormBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_pos_customer_display_register_select';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $registers = \Drupal::service('commerce_pos.registers')->getRegisters();

    if (empty($registers)) {
      // Return no registers error, link to setup registers.
      $this->messenger()->addMessage($this->t('POS Orders can\'t be created until a register has been created. <a href=":url">Add a new register.</a>', [
        ':url' => URL::fromRoute('entity.commerce_pos_register.add_form')->toString(),
      ]), 'error');

      return $form;
    }

    $register_options = ['' => '-'];
    foreach ($registers as $id => $register) {
      $register_options[$id] = $register->getName();
    }

    if ($form_state->getValue('register') > 0) {
      $default_register = Register::load($form_state->getValue('register'));
    }
    else {
      $default_register = \Drupal::service('commerce_pos.current_register')
        ->get();
    }

    $form['register'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Register'),
      '#options' => $register_options,
      '#default_value' => $default_register ? $default_register->id() : 0,
      '#required' => TRUE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Select Register'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // No custom validation needed.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $register = Register::load($values['register']);

    \Drupal::service('commerce_pos.current_register')->set($register);
  }

}
