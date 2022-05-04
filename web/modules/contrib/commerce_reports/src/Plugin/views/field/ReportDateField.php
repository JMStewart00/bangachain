<?php

namespace Drupal\commerce_reports\Plugin\views\field;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\EntityField;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ViewsField("commerce_reports_report_date_field")
 */
class ReportDateField extends EntityField {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The configured date format.
   *
   * Initialized in ::init().
   *
   * @var string
   */
  protected $dateFormat;

  /**
   * The date format string.
   *
   * Initialized in ::init().
   *
   * @var string
   */
  protected $dateFormatString;

  /**
   * Constructs a \Drupal\field\Plugin\views\field\Field object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The field formatter plugin manager.
   * @param \Drupal\Core\Field\FormatterPluginManager $formatter_plugin_manager
   *   The field formatter plugin manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_plugin_manager
   *   The field plugin type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, FormatterPluginManager $formatter_plugin_manager, FieldTypePluginManagerInterface $field_type_plugin_manager, LanguageManagerInterface $language_manager, RendererInterface $renderer, EntityRepositoryInterface $entity_repository, EntityFieldManagerInterface $entity_field_manager, DateFormatterInterface $date_formatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $formatter_plugin_manager, $field_type_plugin_manager, $language_manager, $renderer, $entity_repository, $entity_field_manager);
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.field.formatter'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('language_manager'),
      $container->get('renderer'),
      $container->get('entity.repository'),
      $container->get('entity_field.manager'),
      $container->get('date.formatter')
    );
  }

  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    if (!empty($this->options['settings']['date_format'])) {
      $this->dateFormat = $this->options['settings']['date_format'];
      if ($this->dateFormat == 'custom') {
        $this->dateFormatString = $this->options['settings']['custom_date_format'];
      }
      else {
        /** @var \Drupal\Core\Datetime\DateFormatInterface $formatter */
        $formatter = $this->entityTypeManager->getStorage('date_format')->load($this->dateFormat);
        $this->dateFormatString = $formatter->getPattern();
      }
    }
  }

  /**
   * Called to add the field to a query.
   *
   * By default, all needed data is taken from entities loaded by the query
   * plugin. Columns are added only if they are used in groupings.
   */
  public function query($use_groupby = FALSE) {
    $fields = $this->additional_fields;
    // No need to add the entity type.
    $entity_type_key = array_search('entity_type', $fields);
    if ($entity_type_key !== FALSE) {
      unset($fields[$entity_type_key]);
    }

    if ($use_groupby) {
      // Add the fields that we're actually grouping on.
      $options = [];
      if ($this->options['group_column'] != 'entity_id') {
        $options = [$this->options['group_column'] => $this->options['group_column']];
      }
      $options += is_array($this->options['group_columns']) ? $this->options['group_columns'] : [];

      // Go through the list and determine the actual column name from field api.
      $fields = [];
      $table_mapping = $this->getTableMapping();
      $field_definition = $this->getFieldStorageDefinition();

      foreach ($options as $column) {
        $fields[$column] = $table_mapping->getFieldColumnName($field_definition, $column);
      }

      $this->group_fields = $fields;
    }

    // Add additional fields (and the table join itself) if needed.
    $this->add_field_table($use_groupby);
    $this->ensureMyTable();

    // Add the field.
    $params = $this->options['group_type'] !== 'group' ? ['function' => $this->options['group_type']] : [];
    $expression = $this->query->getDateFormat("FROM_UNIXTIME($this->tableAlias.$this->realField)", $this->dateFormatString);
    $this->field_alias = $this->query->addField(NULL, $expression, "{$this->tableAlias}_{$this->realField}", $params);
    $this->query->addGroupBy($this->field_alias);
    $this->aliases[$this->definition['field_name']] = $this->field_alias;

    // Let the entity field renderer alter the query if needed.
    $this->getEntityFieldRenderer()->query($this->query, $this->relationship);
  }

  /**
   * {@inheritdoc}
   */
  public function getItems(ResultRow $values) {
    if (!$this->displayHandler->useGroupBy()) {
      $build_list = $this->getEntityFieldRenderer()->render($values, $this);
    }
    else {
      // Render date values from SQL result.
      $alias = $this->aliases[$this->definition['field_name']];
      return [['rendered' => ['#plain_text' => $values->{$alias}]]];
    }

    // Code from parent function.
    if (!$build_list) {
      return [];
    }

    if ($this->options['field_api_classes']) {
      return [['rendered' => $this->renderer->render($build_list)]];
    }

    // Render using the formatted data itself.
    $items = [];
    // Each item is extracted and rendered separately, the top-level formatter
    // render array itself is never rendered, so we extract its bubbleable
    // metadata and add it to each child individually.
    $bubbleable = BubbleableMetadata::createFromRenderArray($build_list);
    foreach (Element::children($build_list) as $delta) {
      BubbleableMetadata::createFromRenderArray($build_list[$delta])
        ->merge($bubbleable)
        ->applyTo($build_list[$delta]);
      $items[$delta] = [
        'rendered' => $build_list[$delta],
        // Add the raw field items (for use in tokens).
        'raw' => $build_list['#items'][$delta],
      ];
    }
    return $items;
  }

}
