<?php

namespace Drupal\Tests\feeds\Functional\Form;

use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;

/**
 * @coversDefaultClass \Drupal\feeds\Form\MappingForm
 * @group feeds
 */
class MappingFormTest extends FeedsBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['feeds_test_plugin'];

  /**
   * Tests that custom source names are unique.
   */
  public function testCustomSourceUnique() {
    // Create a feed type with two custom sources called 'guid' and 'title'.
    $feed_type = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' => 'title',
    ]);

    // Add a new target.
    $edit = [
      'add_target' => 'status',
    ];
    $this->drupalPostForm('/admin/structure/feeds/manage/' . $feed_type->id() . '/mapping', $edit, 'Save');

    // Now try to map to a new source called 'title'. This shouldn't be allowed
    // because that source name already exists on the feed type.
    $edit = [
      'mappings[2][map][value][select]' => '__new',
      'mappings[2][map][value][__new][value]' => 'title',
      'mappings[2][map][value][__new][machine_name]' => 'title',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Assert that the double source name is detected.
    $this->assertSession()->pageTextContains('The machine-readable name is already in use. It must be unique.');
  }

  /**
   * Tests that plugins can alter the mapping form.
   */
  public function testPluginWithMappingForm() {
    $feed_type = $this->createFeedType([
      'parser' => 'parser_with_mapping_form',
    ]);

    $edit = [
      'dummy' => 'dummyValue',
    ];

    $this->drupalPostForm('/admin/structure/feeds/manage/' . $feed_type->id() . '/mapping', $edit, 'Save');

    // Assert that the dummy value was saved for the parser.
    $feed_type = $this->reloadEntity($feed_type);
    $config = $feed_type->getParser()->getConfiguration();
    $this->assertEquals('dummyValue', $config['dummy']);
  }

  /**
   * Tests that plugins validate the mapping form.
   */
  public function testPluginWithMappingFormValidate() {
    $feed_type = $this->createFeedType([
      'parser' => 'parser_with_mapping_form',
    ]);

    // ParserWithMappingForm::mappingFormValidate() doesn't accept the value
    // 'invalid'.
    $edit = [
      'dummy' => 'invalid',
    ];

    $this->drupalPostForm('/admin/structure/feeds/manage/' . $feed_type->id() . '/mapping', $edit, 'Save');
    $this->assertText('Invalid value.');

    // Assert that the dummy value was *not* saved for the parser.
    $feed_type = $this->reloadEntity($feed_type);
    $config = $feed_type->getParser()->getConfiguration();
    $this->assertEquals('', $config['dummy']);
  }

}
