<?php

namespace Drupal\metatag\TypedData;

use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedData;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

/**
 * A computed property for each metatag.
 *
 * Required settings (below the definition's 'settings' key) are:
 *  - tag_name: The tag to be processed.
 *    Examples: title.
 */
class Metatags extends TypedData {

  use DependencySerializationTrait;

  /**
   * Cached processed value.
   */
  protected $value;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);

    if ($definition->getSetting('tag_name') === NULL) {
      throw new \InvalidArgumentException("The definition's 'tag_name' key has to specify the name of the metatag to be processed.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    if (isset($this->value)) {
      return $this->value;
    }

    // The metatag id.
    $property_name = $this->definition->getSetting('tag_name');

    // The item is our parent.
    $item = $this->getParent();

    // Unserialize the metatags.
    if (isset($item->value) && !empty($item->value)) {
      $metatags = (unserialize($item->value));
    }

    // If this tag has a value set the property value.
    if (isset($metatags[$property_name])) {
      $this->value = $metatags[$property_name];
    }

    return $this->value;
  }

}
