<?php

namespace Drupal\sitewide_alert\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;
use Symfony\Component\Validator\Constraint;

/**
 * Plugin implementation of the 'ScheduledDateProvided'.
 *
 * @Constraint(
 *   id = "ScheduledDateProvided",
 *   label = @Translation("Scheduled date provided constraint", context = "Validation"),
 *   type = "entity:sitewide_alert"
 * )
 */
class ScheduledDateProvidedConstraint extends CompositeConstraintBase {

  /**
   * Message shown when the entity is marked as scheduled, but no scheduled date is provided.
   *
   * @var string
   */
  public $messageDatesNotProvided = 'This alert is marked as scheduled, but scheduled dates are not provided.';

  /**
   * An array of entity fields which should be passed to the validator.
   *
   * @return string[]
   *   An array of field names.
   */
  public function coversFields() {
    return ['scheduled_alert', 'scheduled_date'];
  }

}
