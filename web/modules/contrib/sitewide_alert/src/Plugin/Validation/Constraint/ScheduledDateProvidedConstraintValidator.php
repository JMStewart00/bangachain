<?php

namespace Drupal\sitewide_alert\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the UniqueInteger constraint.
 */
class ScheduledDateProvidedConstraintValidator extends ConstraintValidator {

  /**
   * Validator 2.5 and upwards compatible execution context.
   *
   * @var \Symfony\Component\Validator\Context\ExecutionContextInterface
   */
  protected $context;

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if ($entity->isScheduled() &&
      ($entity->getScheduledStartDateTime() === NULL ||
      $entity->getScheduledEndDateTime() === NULL)) {
      $this->context->buildViolation($constraint->messageDatesNotProvided)
        ->atPath('scheduled_date')
        ->addViolation();
    }
  }

}
