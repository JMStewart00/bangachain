<?php

namespace Drupal\commerce_pos\Form;

use Drupal\commerce_pos\Entity\Register;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Form\FormStateInterface;

/**
 * Show a form to switch the current register for this session.
 */
class RegisterChangeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_pos_register_select';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $registers = \Drupal::service('commerce_pos.registers')->getRegisters();

    if (count($registers) < 2) {
      // Return no registers error, link to setup registers.
      $this->messenger()->addMessage($this->t('You have no other registers to switch to. @link', [
        '@link' => Link::createFromRoute($this->t('Add a new register.'), 'entity.commerce_pos_register.add_form')->toString(),
      ]), 'error');

      return $form;
    }

    $register_options = [];
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

    if (!$default_register) {
      // Our default register is empty so redirect to register select.
      $redirect_url = Url::fromRoute('commerce_pos.main');
      $this->messenger()->addError($this->t('You have to select a register before you can change it.'));
      return new RedirectResponse($redirect_url->toString());
    }

    $form['#wrapper_id'] = 'commerce-pos-register-select';
    $form['message'] = [
      '#markup' => $this->t('Your current register will remain open!'),
    ];
    $form['register'] = [
      '#type' => 'select',
      '#title' => $this->t('Switch Register'),
      '#options' => $register_options,
      '#default_value' => $default_register ? $default_register->id() : 0,
      '#required' => TRUE,
      '#ajax' => [
        // Call function to check if we have to set a float.
        'callback' => [static::class, 'ajaxRefresh'],
        'wrapper' => 'block-seven-content',
        'method' => 'html',
      ],
    ];

    $form['float'] = [
      '#type' => 'commerce_price',
      '#title' => $this->t('Opening Float'),
      '#required' => TRUE,
      '#default_value' => $default_register->getDefaultFloat()->toArray(),
    ];
    if ($default_register->isOpen() === '1') {
      $form['float']['#disabled'] = TRUE;
    }
    $form['actions']['submit'] = [
      // This is a hack because the formatting on price fields is screwy, when
      // that gets fixed this can be removed.
      '#prefix' => '<br />',
      '#type' => 'submit',
      '#value' => $this->t('Switch Register'),
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
    $register->open();
    $register->save();

    \Drupal::service('commerce_pos.current_register')->set($register);
    $this->messenger()->addMessage($this->t('Register changed to %register', ['%register' => $register->getName()]), 'status');
    $form_state->setRedirect('commerce_pos.main');
  }

  /**
   * Ajax callback for refreshing.
   */
  public function ajaxRefresh(array $form, FormStateInterface &$form_state) {
    $form_state->setRebuild();

    $register = Register::load($form_state->getValue('register'));
    $form['float']['#default_value'] = $register->getDefaultFloat()->toArray();

    return $form;
  }

}
