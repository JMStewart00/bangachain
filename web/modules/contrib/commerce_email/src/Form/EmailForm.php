<?php

namespace Drupal\commerce_email\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\entity\Form\EntityDuplicateFormTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EmailForm extends EntityForm {

  use EntityDuplicateFormTrait;

  /**
   * The email event plugin manager.
   *
   * @var \Drupal\Core\Plugin\DefaultPluginManager
   */
  protected $emailEventManager;

  /**
   * Constructs a new EmailForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Plugin\DefaultPluginManager $email_event_manager
   *   The email event plugin manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, DefaultPluginManager $email_event_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->emailEventManager = $email_event_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_email_event')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\commerce_email\Entity\EmailInterface $email */
    $email = $this->entity;
    $events = $this->emailEventManager->getDefinitions();
    $event_options = array_map(function ($event) {
      return $event['label'];
    }, $events);
    asort($event_options);
    $selected_event_id = $form_state->getValue('event', $email->getEventId());

    $wrapper_id = Html::getUniqueId('payment-gateway-form');
    $form['#prefix'] = '<div id="' . $wrapper_id . '">';
    $form['#suffix'] = '</div>';
    $form['#tree'] = TRUE;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $email->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $email->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_email\Entity\Email::load',
      ],
      '#disabled' => !$email->isNew(),
    ];
    $form['event'] = [
      '#type' => 'select',
      '#title' => $this->t('Event'),
      '#default_value' => $selected_event_id,
      '#options' => $event_options,
      '#required' => TRUE,
      '#disabled' => !$email->isNew(),
      '#ajax' => [
        'callback' => '::ajaxRefresh',
        'wrapper' => $wrapper_id,
      ],
      '#access' => count($event_options) > 1,
    ];
    if (!$selected_event_id) {
      return $form;
    }
    /** @var \Drupal\commerce_email\Plugin\Commerce\EmailEvent\EmailEventInterface $event */
    $event = $this->emailEventManager->createInstance($selected_event_id);
    $target_entity_type_id = $event->getEntityTypeId();

    // These addresses can't use the "email" element type because they
    // might contain tokens (which wouldn't pass validation).
    $form['from'] = [
      '#type' => 'textfield',
      '#title' => $this->t('From'),
      '#maxlength' => 255,
      '#default_value' => $email->getFrom(),
      '#required' => TRUE,
      '#element_validate' => ['token_element_validate'],
      '#token_types' => [$target_entity_type_id],
    ];
    $form['to'] = [
      '#type' => 'textfield',
      '#title' => $this->t('To'),
      '#maxlength' => 255,
      '#default_value' => $email->getTo(),
      '#required' => TRUE,
      '#element_validate' => ['token_element_validate'],
      '#token_types' => [$target_entity_type_id],
    ];
    $form['cc'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cc'),
      '#maxlength' => 255,
      '#default_value' => $email->getCc(),
      '#element_validate' => ['token_element_validate'],
      '#token_types' => [$target_entity_type_id],
    ];
    $form['bcc'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bcc'),
      '#maxlength' => 255,
      '#default_value' => $email->getBcc(),
      '#element_validate' => ['token_element_validate'],
      '#token_types' => [$target_entity_type_id],
    ];
    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#maxlength' => 255,
      '#default_value' => $email->getSubject(),
      '#required' => TRUE,
      '#element_validate' => ['token_element_validate'],
      '#token_types' => [$target_entity_type_id],
    ];
    $form['body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $email->getBody(),
      '#rows' => 10,
      '#required' => TRUE,
      '#element_validate' => ['token_element_validate'],
      '#token_types' => [$target_entity_type_id],
    ];
    $form['token_help'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => [$target_entity_type_id],
    ];

    $form['conditions'] = [
      '#type' => 'commerce_conditions',
      '#title' => $this->t('Conditions'),
      '#parent_entity_type' => 'commerce_email',
      '#entity_types' => [$target_entity_type_id],
      '#default_value' => $email->get('conditions'),
    ];
    $form['conditionOperator'] = [
      '#type' => 'radios',
      '#title' => $this->t('Condition operator'),
      '#title_display' => 'invisible',
      '#options' => [
        'AND' => $this->t('All conditions must pass'),
        'OR' => $this->t('Only one condition must pass'),
      ],
      '#default_value' => $email->getConditionOperator(),
    ];
    $form['status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Status'),
      '#options' => [
        0 => $this->t('Disabled'),
        1  => $this->t('Enabled'),
      ],
      '#default_value' => (int) $email->status(),
    ];

    return $form;
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    $this->postSave($this->entity, $this->operation);
    $this->messenger()->addMessage($this->t('Saved the %label email.', ['%label' => $this->entity->label()]));
    $form_state->setRedirect('entity.commerce_email.collection');
  }

}
