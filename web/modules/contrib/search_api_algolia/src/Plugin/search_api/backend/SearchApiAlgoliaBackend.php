<?php

namespace Drupal\search_api_algolia\Plugin\search_api\backend;

use AlgoliaSearch\AlgoliaException;
use AlgoliaSearch\Client;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search_api\Backend\BackendPluginBase;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Query\ConditionGroupInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api_autocomplete\SearchInterface;
use Drupal\search_api_autocomplete\Suggestion\SuggestionFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SearchApiAlgoliaBackend.
 *
 * @SearchApiBackend(
 *   id = "search_api_algolia",
 *   label = @Translation("Algolia"),
 *   description = @Translation("Index items using a Algolia Search.")
 * )
 */
class SearchApiAlgoliaBackend extends BackendPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  /**
   * Algolia Index.
   *
   * @var \AlgoliaSearch\Index
   */
  protected $algoliaIndex = NULL;

  /**
   * A connection to the Algolia server.
   *
   * @var \AlgoliaSearch\Client
   */
  protected $algoliaClient;

  /**
   * The logger to use for logging messages.
   *
   * @var \Psr\Log\LoggerInterface|null
   */
  protected $logger;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $backend = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager')
    );

    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
    $module_handler = $container->get('module_handler');
    $backend->setModuleHandler($module_handler);

    /** @var \Psr\Log\LoggerInterface $logger */
    $logger = $container->get('logger.channel.search_api_algolia');
    $backend->setLogger($logger);

    return $backend;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'application_id' => '',
      'api_key' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['help'] = [
      '#markup' => '<p>' . $this->t('The application ID and API key an be found and configured at <a href="@link" target="blank">@link</a>.', ['@link' => 'https://www.algolia.com/licensing']) . '</p>',
    ];
    $form['application_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application ID'),
      '#description' => $this->t('The application ID from your Algolia subscription.'),
      '#default_value' => $this->getApplicationId(),
      '#required' => TRUE,
      '#size' => 60,
      '#maxlength' => 128,
    ];
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#description' => $this->t('The API key from your Algolia subscription.'),
      '#default_value' => $this->getApiKey(),
      '#required' => TRUE,
      '#size' => 60,
      '#maxlength' => 128,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewSettings() {
    try {
      $this->connect();
    }
    catch (\Exception $e) {
      $this->getLogger()->warning('Could not connect to Algolia backend.');
    }
    $info = [];

    // Application ID.
    $info[] = [
      'label' => $this->t('Application ID'),
      'info' => $this->getApplicationId(),
    ];

    // API Key.
    $info[] = [
      'label' => $this->t('API Key'),
      'info' => $this->getApiKey(),
    ];

    // Available indexes.
    $indexes = $this->getAlgolia()->listIndexes();
    $indexes_list = [];
    if (isset($indexes['items'])) {
      foreach ($indexes['items'] as $index) {
        $indexes_list[] = $index['name'];
      }
    }
    $info[] = [
      'label' => $this->t('Available Algolia indexes'),
      'info' => implode(', ', $indexes_list),
    ];

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public function removeIndex($index) {
    // Only delete the index's data if the index isn't read-only.
    if (!is_object($index) || empty($index->get('read_only'))) {
      $this->deleteAllIndexItems($index);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function indexItems(IndexInterface $index, array $items) {
    $objects = [];

    /** @var \Drupal\search_api\Item\ItemInterface[] $items */
    foreach ($items as $id => $item) {
      $objects[$id] = $this->prepareItem($index, $item);
    }

    // Let other modules alter objects before sending them to Algolia.
    $this->alterAlgoliaObjects($objects, $index, $items);

    if (count($objects) > 0) {
      $itemsToIndex = [];

      if ($this->languageManager->isMultilingual()) {
        foreach ($objects as $item) {
          $itemsToIndex[$item['search_api_language']][] = $item;
        }
      }
      else {
        $itemsToIndex[''] = $objects;
      }

      foreach ($itemsToIndex as $language => $items) {
        try {
          $this->connect($index, '', $language);
          $this->getAlgoliaIndex()->saveObjects($items);
        }
        catch (AlgoliaException $e) {
          $this->getLogger()->warning(Html::escape($e->getMessage()));
        }
      }
    }

    return array_keys($objects);
  }

  /**
   * Indexes a single item on the specified index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index for which the item is being indexed.
   * @param \Drupal\search_api\Item\ItemInterface $item
   *   The item to index.
   */
  protected function indexItem(IndexInterface $index, ItemInterface $item) {
    $this->indexItems([$item->getId() => $item]);
  }

  /**
   * Prepares a single item for indexing.
   *
   * Used as a helper method in indexItem()/indexItems().
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   Index.
   * @param \Drupal\search_api\Item\ItemInterface $item
   *   The item to index.
   */
  protected function prepareItem(IndexInterface $index, ItemInterface $item) {
    $item_id = $item->getId();
    $item_to_index = ['objectID' => $item_id];

    /** @var \Drupal\search_api\Item\FieldInterface $field */
    $item_fields = $item->getFields();
    $item_fields += $this->getSpecialFields($index, $item);
    foreach ($item_fields as $field) {
      $type = $field->getType();
      $values = NULL;
      $field_values = $field->getValues();
      if (empty($field_values)) {
        continue;
      }
      foreach ($field_values as $field_value) {
        if (!$field_value) {
          continue;
        }
        switch ($type) {
          case 'text':
          case 'string':
          case 'uri':
            $field_value .= '';
            if (mb_strlen($field_value) > 10000) {
              $field_value = mb_substr(trim($field_value), 0, 10000);
            }
            $values[] = $field_value;
            break;

          case 'integer':
          case 'duration':
          case 'decimal':
            $values[] = 0 + $field_value;
            break;

          case 'boolean':
            $values[] = $field_value ? TRUE : FALSE;
            break;

          case 'date':
            if (is_numeric($field_value) || !$field_value) {
              $values[] = 0 + $field_value;
              break;
            }
            $values[] = strtotime($field_value);
            break;

          default:
            $values[] = $field_value;
        }
      }
      if (is_array($values) && count($values) <= 1) {
        $values = reset($values);
      }
      $item_to_index[$field->getFieldIdentifier()] = $values;
    }

    return $item_to_index;
  }

  /**
   * Applies custom modifications to indexed Algolia objects.
   *
   * This method allows subclasses to easily apply custom changes before the
   * objects are sent to Algolia.
   *
   * @param array $objects
   *   An array of objects ready to be indexed, generated from $items array.
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index for which items are being indexed.
   * @param array $items
   *   An array of items being indexed.
   *
   * @see hook_search_api_algolia_objects_alter()
   */
  protected function alterAlgoliaObjects(array &$objects, IndexInterface $index, array $items) {
    $this->getModuleHandler()->alter('search_api_algolia_objects', $objects, $index, $items);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItems(IndexInterface $index, array $ids) {
    // Deleting all items included in the $ids array.
    if ($this->languageManager->isMultilingual()) {
      foreach ($this->languageManager->getLanguages() as $language) {
        try {
          // Connect to the Algolia index for specific language.
          $this->connect($index, '', $language->getId());
        }
        catch (\Exception $e) {
          $this->getLogger()->error('Failed to connect to Algolia index while deleting indexed items, Error: @message', [
            '@message' => $e->getMessage(),
          ]);

          return;
        }

        $this->getAlgoliaIndex()->deleteObjects($ids);
      }
    }
    else {
      // Connect to the Algolia index.
      try {
        $this->connect($index);
      }
      catch (\Exception $e) {
        $this->getLogger()->error('Failed to connect to Algolia index while deleting indexed items, Error: @message', [
          '@message' => $e->getMessage(),
        ]);

        return;
      }

      $this->getAlgoliaIndex()->deleteObjects($ids);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAllIndexItems(IndexInterface $index = NULL, $datasource_id = NULL) {
    if ($index) {
      if ($this->languageManager->isMultilingual()) {
        foreach ($this->languageManager->getLanguages() as $language) {
          // Connect to the Algolia service.
          $this->connect($index, '', $language->getId());

          // Clearing the full index.
          $this->getAlgoliaIndex()->clearIndex();
        }
      }
      else {
        // Connect to the Algolia service.
        $this->connect($index);

        // Clearing the full index.
        $this->getAlgoliaIndex()->clearIndex();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function search(QueryInterface $query) {
    $results = $query->getResults();
    $options = $query->getOptions();
    $sorts = $query->getSorts() ?? [];
    $search_api_index = $query->getIndex();
    $suffix = '';

    // Allow other modules to remove sorts handled in index rankings.
    $this->getModuleHandler()->alter('search_api_algolia_sorts', $sorts, $search_api_index);

    // Get the first sort to build replica name.
    // Replicas must be created with format PRIMARYINDEXNAME_FIELD_DIRECTION.
    // For instance index_stock_desc.
    foreach ($sorts as $field => $direction) {
      $suffix = '_' . strtolower($field . '_' . $direction);
      break;
    }

    try {
      $this->connect($search_api_index, $suffix);
      $index = $this->getAlgoliaIndex();
    }
    catch (\Exception $e) {
      $this->getLogger()->error('Failed to connect to Algolia index while searching with suffix: @suffix, Error: @message', [
        '@message' => $e->getMessage(),
        '@suffix' => $suffix,
      ]);

      return $results;
    }

    $facets = isset($options['search_api_facets'])
      ? array_column($options['search_api_facets'], 'field')
      : [];

    $algolia_options = [
      'attributesToRetrieve' => [
        'search_api_id',
      ],
      'facets' => $facets,
      'analytics' => TRUE,
    ];

    if (!empty($options['limit'])) {
      $algolia_options['length'] = $options['limit'];
      $algolia_options['offset'] = $options['offset'];
    }

    $this->extractConditions($query->getConditionGroup(), $algolia_options, $facets);

    // Algolia expects indexed arrays, remove the keys.
    if (isset($algolia_options['facetFilters'])) {
      $algolia_options['facetFilters'] = array_values($algolia_options['facetFilters']);
    }
    if (isset($algolia_options['disjunctiveFacets'])) {
      $algolia_options['disjunctiveFacets'] = array_values($algolia_options['disjunctiveFacets']);
    }

    // Filters and disjunctiveFacets are not supported together by Algolia.
    if (!empty($algolia_options['filters']) && !empty($algolia_options['disjunctiveFacets'])) {
      unset($algolia_options['disjunctiveFacets']);
    }

    $keys = $query->getOriginalKeys();
    $search = empty($keys) ? '*' : $keys;

    $data = $index->search($search, $algolia_options);
    $results->setResultCount($data['nbHits']);
    foreach ($data['hits'] ?? [] as $row) {
      $item = $this->getFieldsHelper()->createItem($query->getIndex(), $row['search_api_id']);
      $results->addResultItem($item);
    }

    if (isset($data['facets'])) {
      $results->setExtraData(
        'search_api_facets',
        $this->extractFacetsData($facets, $data['facets'])
      );
    }

    return $results;
  }

  /**
   * Creates a connection to the Algolia Search server as configured.
   *
   * @param \Drupal\search_api\IndexInterface|null $index
   *   Index to connect to.
   * @param string $index_suffix
   *   Index suffix, specified when connecting to replica or query suggestion.
   * @param string $langcode
   *   Language code to connect to.
   *   Specified when doing operations on both languages together.
   *
   * @throws \AlgoliaSearch\AlgoliaException
   */
  protected function connect(?IndexInterface $index = NULL, $index_suffix = '', $langcode = '') {
    if (!$this->getAlgolia()) {
      $this->algoliaClient = new Client($this->getApplicationId(), $this->getApiKey());
    }

    if ($index && $index instanceof IndexInterface) {
      $indexId = ($index->getOption('algolia_index_name'))
        ? $index->getOption('algolia_index_name')
        : $index->get('id');

      if ($this->languageManager->isMultilingual()) {
        $langcode = $langcode ?: $this->languageManager->getCurrentLanguage()->getId();
        $indexId .= '_' . $langcode;
      }

      $indexId .= $index_suffix;
      $this->setAlgoliaIndex($this->algoliaClient->initIndex($indexId));
    }
  }

  /**
   * Retrieves the list of available Algolia indexes.
   *
   * @return array
   *   List of indexes on Algolia.
   */
  public function listIndexes() {
    $algoliaClient = new Client($this->getApplicationId(), $this->getApiKey());

    $indexes = $algoliaClient->listIndexes();
    $indexes_list = [];
    if (isset($indexes['items'])) {
      foreach ($indexes['items'] as $index) {
        $indexes_list[$index['name']] = $index['name'];
      }
    }

    return $indexes_list;
  }

  /**
   * Retrieves the logger to use.
   *
   * @return \Psr\Log\LoggerInterface
   *   The logger to use.
   */
  public function getLogger() {
    return $this->logger;
  }

  /**
   * Sets the logger to use.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger to use.
   *
   * @return $this
   */
  public function setLogger(LoggerInterface $logger) {
    $this->logger = $logger;
    return $this;
  }

  /**
   * Returns the module handler to use for this plugin.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   */
  public function getModuleHandler() {
    return $this->moduleHandler ?: \Drupal::moduleHandler();
  }

  /**
   * Sets the module handler to use for this plugin.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to use for this plugin.
   *
   * @return $this
   */
  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
    return $this;
  }

  /**
   * Returns the AlgoliaSearch client.
   *
   * @return \AlgoliaSearch\Client
   *   The algolia instance object.
   */
  public function getAlgolia() {
    return $this->algoliaClient;
  }

  /**
   * Get the Algolia index.
   *
   * @returns \AlgoliaSearch\Index
   *   Index.
   */
  protected function getAlgoliaIndex() {
    return $this->algoliaIndex;
  }

  /**
   * Set the Algolia index.
   */
  protected function setAlgoliaIndex($index) {
    $this->algoliaIndex = $index;
  }

  /**
   * Get the ApplicationID (provided by Algolia).
   */
  protected function getApplicationId() {
    return $this->configuration['application_id'];
  }

  /**
   * Get the API key (provided by Algolia).
   */
  protected function getApiKey() {
    return $this->configuration['api_key'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedFeatures() {
    return [
      'search_api_autocomplete',
      'search_api_facets',
      'search_api_facets_operator_or',
    ];
  }

  /**
   * Extract facets data from response.
   *
   * @param array $facets
   *   Facets to extract.
   * @param array $data
   *   Facets data from response.
   *
   * @return array
   *   Facets data in format required by Drupal.
   */
  private function extractFacetsData(array $facets, array $data) {
    $facets_data = [];

    foreach ($data as $field => $facet_data) {
      if (!in_array($field, $facets)) {
        continue;
      }

      foreach ($facet_data as $value => $count) {
        $facets_data[$field][] = [
          'count' => $count,
          'filter' => '"' . $value . '"',
        ];
      }
    }

    return $facets_data;
  }

  /**
   * Extract conditions.
   *
   * @param \Drupal\search_api\Query\ConditionGroupInterface $condition_group
   *   Condition group.
   * @param array $options
   *   Algolia options to updatesearch_api_algolia.module.
   * @param array $facets
   *   Facets.
   */
  private function extractConditions(ConditionGroupInterface $condition_group, array &$options, array $facets) {
    foreach ($condition_group->getConditions() as $condition) {
      if ($condition instanceof ConditionGroupInterface) {
        $this->extractConditions($condition, $options, $facets);
        continue;
      }


      $field = $condition->getField();

      /** @var \Drupal\search_api\Query\Condition $condition */
      // We support limited operators for now.
      if ($condition->getOperator() == '=' ) {
        $query = $field . ':' . $condition->getValue();

        if (in_array($field, $facets)) {
          $options['facetFilters'][$field][] = $query;
          $options['disjunctiveFacets'][$field] = $field;
        }
        else {
          $options['filters'] = isset($options['filters'])
            ? ' AND ' . $query
            : $query;
        }
      }
      elseif (in_array($condition->getOperator(), ['<', '>', '<=', '>='])) {
        $options['numericFilters'][] = $field . ' ' . $condition->getOperator() . ' ' . $condition->getValue();
      }
    }
  }

  /**
   * Implements autocomplete compatible to AutocompleteBackendInterface.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   A query representing the completed user input so far.
   * @param \Drupal\search_api_autocomplete\SearchInterface $search
   *   An object containing details about the search the user is on, and
   *   settings for the autocompletion. See the class documentation for details.
   *   Especially $search->options should be checked for settings, like whether
   *   to try and estimate result counts for returned suggestions.
   * @param string $incomplete_key
   *   The start of another fulltext keyword for the search, which should be
   *   completed. Might be empty, in which case all user input up to now was
   *   considered completed. Then, additional keywords for the search could be
   *   suggested.
   * @param string $user_input
   *   The complete user input for the fulltext search keywords so far.
   *
   * @return \Drupal\search_api_autocomplete\Suggestion\SuggestionInterface[]
   *   An array of suggestions.
   *
   * @see \Drupal\search_api_autocomplete\AutocompleteBackendInterface
   */
  public function getAutocompleteSuggestions(QueryInterface $query, SearchInterface $search, $incomplete_key, $user_input) {
    $suggestions = [];

    if (class_exists(SuggestionFactory::class)) {
      $factory = new SuggestionFactory($user_input);
    }

    $search_api_index = $query->getIndex();

    try {
      $this->connect($search_api_index, '_query');
      $index = $this->getAlgoliaIndex();
    }
    catch (\Exception $e) {
      $this->getLogger()->error('Failed to connect to Algolia index with suffix: @suffix, Error: @message', [
        '@message' => $e->getMessage(),
        '@suffix' => '_query',
      ]);

      return $suggestions;
    }

    $algolia_options = [
      'attributesToRetrieve' => [
        'query',
      ],
      'analytics' => TRUE,
    ];

    try {
      $data = $index->search($user_input, $algolia_options);
    }
    catch (\Exception $e) {
      $this->getLogger()->error('Failed to load autocomplete suggestions from Algolia. Query: @query, Error: @message', [
        '@message' => $e->getMessage(),
        '@query' => $user_input,
      ]);

      return $suggestions;
    }

    foreach ($data['hits'] ?? [] as $row) {
      $suggestions[] = $factory->createFromSuggestedKeys($row['query']);
    }

    return $suggestions;
  }

}
