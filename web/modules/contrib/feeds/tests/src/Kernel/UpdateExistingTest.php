<?php

namespace Drupal\Tests\feeds\Kernel;

use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;

/**
 * Tests the feature of updating items.
 *
 * @group feeds
 */
class UpdateExistingTest extends FeedsKernelTestBase {

  /**
   * Tests updating terms by name.
   *
   * Tests that terms from the right vocabulary get updated.
   */
  public function testUpdateTermsInSameVocabulary() {
    // Install taxonomy module with a vocabulary called "tags".
    $this->installTaxonomyModuleWithVocabulary();

    // Create a second vocabulary.
    $this->entityTypeManager->getStorage('taxonomy_vocabulary')->create([
      'vid' => 'vocab2',
      'name' => 'Vocabulary 2',
    ])->save();

    /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');

    // Create a term in the first vocabulary.
    $tags_term1 = $term_storage->create([
      'name' => 'Lorem ipsum',
      'vid' => 'tags',
    ]);
    $tags_term1->save();

    // Create a term in the second vocabulary.
    $vocab2_term1 = $term_storage->create([
      'name' => 'Ut wisi enim ad minim veniam',
      'description' => 'Wisi Wisi',
      'vid' => 'vocab2',
    ]);
    $vocab2_term1->save();

    // Create a feed type.
    $feed_type = $this->createFeedTypeForCsv([
      'title' => 'title',
      'body' => 'body',
    ], [
      'processor' => 'entity:taxonomy_term',
      'processor_configuration' => [
        'update_existing' => ProcessorInterface::UPDATE_EXISTING,
        'values' => [
          'vid' => 'vocab2',
        ],
      ],
      'mappings' => [
        [
          'target' => 'name',
          'map' => ['value' => 'title'],
          'unique' => ['value' => TRUE],
        ],
        [
          'target' => 'description',
          'map' => ['value' => 'body'],
          'settings' => ['format' => 'plain_text'],
        ],
      ],
    ]);

    // Import.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();

    // Assert that the second vocabulary has two terms now.
    $term_count = $term_storage->getQuery()
      ->condition('vid', 'vocab2')
      ->count()
      ->execute();
    $this->assertEquals(2, $term_count, 'Two terms exist in vocabulary vocab2.');

    // Assert that the term from the first vocabulary was not updated with a
    // description.
    $tags_term1 = $this->reloadEntity($tags_term1);
    $this->assertEquals('Lorem ipsum', $tags_term1->getName());
    $this->assertEquals('', $tags_term1->getDescription());

    // Assert that the terms have the expected descriptions.
    $term = $term_storage->load(3);
    $this->assertEquals('Lorem ipsum', $term->getName());
    $this->assertEquals('Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat.', $term->getDescription());
    $vocab2_term1 = $this->reloadEntity($vocab2_term1);
    $this->assertEquals('Ut wisi enim ad minim veniam', $vocab2_term1->getName());
    $this->assertEquals('Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat.', $vocab2_term1->getDescription());
  }

}
