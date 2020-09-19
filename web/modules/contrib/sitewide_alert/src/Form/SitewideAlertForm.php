<?php

namespace Drupal\sitewide_alert\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Sitewide Alert edit forms.
 *
 * @ingroup sitewide_alert
 */
class SitewideAlertForm extends ContentEntityForm {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * Constructs a new SitewideAlertForm.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user account.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, AccountProxyInterface $account) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);

    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\sitewide_alert\Entity\SitewideAlertInterface $entity */
    $entity = $this->entity;
    $form = parent::buildForm($form, $form_state);

    // Authoring information for administrators.
    if (isset($form['user_id'])) {
      $form['author'] = [
        '#type' => 'details',
        '#title' => $this->t('Authoring information'),
        '#group' => 'advanced',
        '#attributes' => [
          'class' => ['sitewide_alert-form-author'],
        ],
        '#weight' => -3,
        '#optional' => TRUE,
      ];

      $form['user_id']['#group'] = 'author';
    }

    // Make the scheduled alert dates conditional on the checkbox.
    $form['scheduled_date']['#states'] = [
      'visible' => [
        ':input[name="scheduled_alert[value]"]' => ['checked' => TRUE]
      ],
    ];

    // Allow the editor to disable previous dismissals.
    if (!$entity->isNew()) {
      $form['dismissible_ignore_previous'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Ignore Previous Dismissals'),
        '#description' => $this->t('Select this when making a major change and you want to make sure all visitors see this alert even if they have dismissed it prior.'),
        '#default_value' => FALSE,
        '#return_value' => TRUE,
        '#weight' => -9,
        '#states' => [
          'visible' => [
            ':input[name="dismissible[value]"]' => ['checked' => TRUE]
          ],
        ]
      ];
    }

    // Organize the limit by pages options.
    $form['limit_by_pages_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Page Visibility'),
      '#description' => $this->t('Limit the alert to only show on some of pages.'),
    ];

    $form['limit_by_pages_fieldset']['limit_alert_by_pages'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Limit by Page'),
      '#default_value' => !empty($entity->getPagesToShowOn()),
      '#return_value' => TRUE,
      '#weight' => -10,
    ];

    $form['limit_by_pages_fieldset']['limit_to_pages'] = $form['limit_to_pages'];
    unset($form['limit_to_pages']);
    $form['limit_by_pages_fieldset']['limit_to_pages']['#states'] = [
      'visible' => [
        ':input[name="limit_alert_by_pages"]' => ['checked' => TRUE]
      ],
    ];

    $form['limit_by_pages_fieldset']['limit_to_pages_negate'] = $form['limit_to_pages_negate'];
    unset($form['limit_to_pages_negate']);
    $form['limit_by_pages_fieldset']['limit_to_pages_negate']['#states'] = [
      'visible' => [
        ':input[name="limit_alert_by_pages"]' => ['checked' => TRUE]
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\sitewide_alert\Entity\SitewideAlertInterface $entity */
    $entity = $this->entity;

    // Set the dismissal timestamp.
    if (!$form_state->isValueEmpty('dismissible_ignore_previous') && $form_state->getValue('dismissible_ignore_previous')) {
      $entity->setDismissibleIgnoreBeforeTime($this->time->getRequestTime());
    }

    // Clear any previously set limit by pages if the option to limit them is not set.
    if (!$form_state->isValueEmpty('limit_alert_by_pages') && !$form_state->getValue('limit_alert_by_pages')) {
      $entity->set('limit_to_pages', '');
    }

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime($this->time->getRequestTime());
      $entity->setRevisionUserId($this->account->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Sitewide Alert.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Sitewide Alert.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.sitewide_alert.collection');
  }

}
