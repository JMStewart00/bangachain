<?php

namespace Drupal\Tests\Core\Condition;

use Drupal\Core\Form\FormState;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Condition\ConditionPluginBase
 * @group Condition
 */
class ConditionPluginBaseTest extends UnitTestCase {

  /**
   * @covers ::validateConfigurationForm
   */
  public function testValidateConfigurationForm() {
    $form_validator = $this->getMockBuilder('Drupal\Core\Condition\ConditionPluginBase')
      ->setConstructorArgs([[], '', ''])
      ->setMethods(['evaluate', 'summary'])
      ->getMock();

    $form = [];
    $form_state = new FormState();

    $form_state->setValue('negate', 0);
    $form_validator->validateConfigurationForm($form, $form_state);
    $this->assertIsBool($form_state->getValue('negate'));

    $form_state->setValue('negate', 1);
    $form_validator->validateConfigurationForm($form, $form_state);
    $this->assertIsBool($form_state->getValue('negate'));

    $form_state->setValue('negate', FALSE);
    $form_validator->validateConfigurationForm($form, $form_state);
    $this->assertIsBool($form_state->getValue('negate'));

    $form_state->setValue('negate', TRUE);
    $form_validator->validateConfigurationForm($form, $form_state);
    $this->assertIsBool($form_state->getValue('negate'));
  }

}
