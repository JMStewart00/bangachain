<?php

namespace Drupal\Tests\commerce\Functional;

/**
 * Tests redirection.
 *
 * @group commerce
 */
class RedirectTest extends CommerceBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['commerce_test'];

  /**
   * Test redirection inside forms (via NeedsRedirectException).
   */
  public function testRedirectForm() {
    $this->drupalGet('/commerce_test/redirect_form');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertEquals('https://www.example.org/', $this->getSession()->getCurrentUrl());
  }

}
