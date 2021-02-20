<?php

namespace Drupal\Tests\htmlmail\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests basic installability of the HTML Mail module.
 *
 * @group htmlmail
 */
class HtmlMailTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['htmlmail', 'mailsystem'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Dummy test to satisfy DrupalCI.
   */
  public function testHello() {
  }

}
