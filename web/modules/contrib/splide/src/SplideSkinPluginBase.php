<?php

namespace Drupal\splide;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides base class for all splide skins.
 */
abstract class SplideSkinPluginBase extends PluginBase implements SplideSkinPluginInterface {

  use StringTranslationTrait;

  /**
   * The splide main/thumbnail skin definitions.
   *
   * @var array
   */
  protected $skins;

  /**
   * The splide arrow skin definitions.
   *
   * @var array
   */
  protected $arrows;

  /**
   * The splide dot skin definitions.
   *
   * @var array
   */
  protected $dots;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->skins = $this->setSkins();
    $this->arrows = $this->setArrows();
    $this->dots = $this->setDots();
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->configuration['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function skins() {
    return $this->skins;
  }

  /**
   * {@inheritdoc}
   */
  public function arrows() {
    return $this->arrows;
  }

  /**
   * {@inheritdoc}
   */
  public function dots() {
    return $this->dots;
  }

  /**
   * Sets the required plugin main/thumbnail skins.
   */
  abstract protected function setSkins();

  /**
   * Sets the optional/ empty plugin arrow skins.
   */
  protected function setArrows() {
    return [];
  }

  /**
   * Sets the optional/ empty plugin dot skins.
   */
  protected function setDots() {
    return [];
  }

}
