<?php

namespace Drupal\bangachain_layout\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\layout_builder\LayoutBuilderEvents;
use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;

/**
 * Gets third party settings on BuildRender.
 */
class SectionComponentRender implements EventSubscriberInterface {

  /**
   * Constructs a new SectionComponentRender object.
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY] = ['onBuildRender'];

    return $events;
  }

  /**
   * Adds block classes to section component.
   *
   * @param \Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent $event
   *   The section component render event.
   */
  public function onBuildRender(SectionComponentBuildRenderArrayEvent $event) {
    $build = $event->getBuild();
    if (!empty($build)) {
      $build['#bangachain_layout'] = $event->getComponent()->getThirdPartySettings('bangachain_layout');
      $event->setBuild($build);
    }
  }

}
