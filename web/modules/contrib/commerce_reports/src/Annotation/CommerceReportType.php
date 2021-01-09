<?php

namespace Drupal\commerce_reports\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the commerce report type plugin annotation object.
 *
 * Plugin namespace: Plugin\Commerce\ReportType.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class CommerceReportType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The commerce report type label.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The commerce report type create label.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $create_label;

}
