<?php

namespace Drupal\search_api_algolia\Plugin\search_api\backend;

use Algolia\AlgoliaSearch\SearchClient;
use Algolia\AlgoliaSearch\Exceptions\AlgoliaException;
use Algolia\AlgoliaSearch\SearchIndex;
use Drupal\Component\Utility\Html;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search_api\Backend\BackendPluginBase;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Query\ConditionGroupInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Utility\Utility;
use Drupal\search_api_algolia\SearchApiAlgoliaHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

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
   * @var \Algolia\AlgoliaSearch\SearchIndex
   */
  protected $algoliaIndex = NULL;

  /**
   * A connection to the Algolia server.
   *
   * @var \Algolia\AlgoliaSearch\SearchClient
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
   * The Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Search API Algolia Helper service.
   *
   * @var \Drupal\search_api_algolia\SearchApiAlgoliaHelper
   */
  protected $helper;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    LanguageManagerInterface $language_manager,
    ConfigFactoryInterface $config_factory,
    SearchApiAlgoliaHelper $helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageManager = $language_manager;
    $this->configFactory = $config_factory;
    $this->helper = $helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $backend = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager'),
      $container->get('config.factory'),
      $container->get('search_api_algolia.helper')
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
      'disable_truncate' => FALSE,
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

    $form['disable_truncate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable truncation'),
      '#description' => $this->t('If checked, fields of type text and strong will not be truncated at 10000 characters. It will be site owner or developer responsibility to limit the characters.'),
      '#default_value' => $this->configuration['disable_truncate'],
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
    $indexes = $this->getAlgolia()->listIndices();
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
   *
   * @throws \Drupal\search_api\SearchApiException
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

    /** @var \Drupal\search_api\Item\ItemInterface $item */
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
        // Allow adding objects to logs for investigation.
        if ($this->isDebugActive()) {
          foreach ($items as $item) {
            $this->getLogger()->notice('Data pushed to Algolia for Language @language : @data', [
              '@data' => json_encode($item),
              '@language' => $language,
            ]);
          }
        }

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
    $this->indexItems($index, [$item->getId() => $item]);
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
   *
   * @return array
   */
  protected function prepareItem(IndexInterface $index, ItemInterface $item) {
    $item_id = $item->getId();
    $item_to_index = ['objectID' => $item_id];

    $item_fields = $item->getFields();
    $item_fields += $this->getSpecialFields($index, $item);

    /** @var \Drupal\search_api\Item\FieldInterface $field */
    foreach ($item_fields as $field) {
      $type = $field->getType();
      $values = NULL;
      $field_values = $field->getValues();
      if (empty($field_values)) {
        continue;
      }
      foreach ($field_values as $field_value) {
        switch ($type) {
          case 'uri':
            $field_value .= '';
            if (mb_strlen($field_value) > 10000) {
              $field_value = mb_substr(trim($field_value), 0, 10000);
            }
            $values[] = $field_value;
            break;

          case 'text':
          case 'string':
            $field_value .= '';
            if (empty($this->configuration['disable_truncate']) && mb_strlen($field_value) > 10000) {
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
    // When using custom field for object id, we handle the deletion of
    // objects in separate code.
    if ($index->getOption('object_id_field')){
      return;
    }

    // Deleting all items included in the $ids array.
    foreach ($this->getLanguages($index) as $key) {
      // If algolia_index_batch_deletion enabled delete in batches
      // with drush command.
      if ($index->getOption('algolia_index_batch_deletion')) {
        $this->helper->scheduleForDeletion($index, $ids, $key);
        continue;
      }

      try {
        // Connect to the Algolia index for specific language.
        $this->connect($index, '', $key);
      } catch (\Exception $e) {
        $this->getLogger()->error('Failed to connect to Algolia index while deleting indexed items, Error: @message', [
          '@message' => $e->getMessage(),
        ]);

        continue;
      }

      $response = $this->getAlgoliaIndex()->deleteObjects($ids);

      if ($this->isDebugActive()) {
        $this->getLogger()->notice('Deletion requested for IDs: @ids on Algolia for Index: @index, Response: @response.', [
          '@response' => json_encode($response),
          '@index' => $this->getAlgoliaIndex()->getIndexName(),
          '@ids' => implode(',', $ids),
        ]);
      }

      // Wait for the deletion to be completed.
      if ($this->shouldWaitForDeleteToFinish()) {
        $response->wait();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAllIndexItems(IndexInterface $index = NULL, $datasource_id = NULL) {
    if (empty($index)) {
      return;
    }

    foreach ($this->getLanguages($index) as $key) {
      // Connect to the Algolia service.
      $this->connect($index, '', $key);

      // Clearing the full index.
      $response = $this->getAlgoliaIndex()->clearObjects();

      if ($this->isDebugActive()) {
        $this->getLogger()->notice('Deletion requested for full index on Algolia Index: @index, Response: @response.', [
          '@response' => json_encode($response),
          '@index' => $this->getAlgoliaIndex()->getIndexName(),
        ]);
      }

      // Wait for the deletion to be completed.
      if ($this->shouldWaitForDeleteToFinish()) {
        $response->wait();
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

    // Allow Algolia specific options to be set dynamically.
    if (isset($options['algolia_options']) && is_array($options['algolia_options'])) {
      $algolia_options += $options['algolia_options'];
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
      if (!empty($row['_snippetResult'])) {
        $item->setExcerpt(implode('&hellip;', array_column($row['_snippetResult'], 'value')));
      }
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
   */
  protected function connect(?IndexInterface $index = NULL, $index_suffix = '', $langcode = '') {
    if (!($this->getAlgolia() instanceof SearchClient)) {
      $this->algoliaClient = SearchClient::create($this->getApplicationId(), $this->getApiKey());
    }

    if ($index && $index instanceof IndexInterface) {
      $indexId = ($index->getOption('algolia_index_name'))
        ? $index->getOption('algolia_index_name')
        : $index->get('id');

      if ($this->isLanguageSuffixEnabled($index)) {
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
    $algoliaClient = SearchClient::create($this->getApplicationId(), $this->getApiKey());

    $indexes = $algoliaClient->listIndices();
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
    return $this->moduleHandler ?? Drupal::moduleHandler();
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
   * @return \Algolia\AlgoliaSearch\SearchClient
   *   The algolia instance object.
   */
  public function getAlgolia() {
    return $this->algoliaClient;
  }

  /**
   * Get the Algolia index.
   *
   * @returns \Algolia\AlgoliaSearch\SearchIndex
   *   Index.
   */
  protected function getAlgoliaIndex() {
    return $this->algoliaIndex;
  }

  /**
   * Set the Algolia index.
   */
  protected function setAlgoliaIndex(SearchIndex $index) {
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
  public function getAutocompleteSuggestions(QueryInterface $query, \Drupal\search_api_autocomplete\SearchInterface $search, $incomplete_key, $user_input) {
    // This function will be used only is search_api_autocomplete is enabled
    // and used. We have it here to add the support but it might never be used
    // in normal cases.
    $suggestions = [];

    try {
      $factory = new \Drupal\search_api_autocomplete\Suggestion\SuggestionFactory($user_input);
    }
    catch (\Exception $e) {
      return $suggestions;
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

  /**
   * Wrapper function to check if debug mode is active or not as per config.
   *
   * @return bool
   *   TRUE if debug mode is active.
   */
  protected function isDebugActive() {
    static $debug_active = NULL;

    if (is_null($debug_active)) {
      $debug_active = $this->configFactory
          ->get('search_api_algolia.settings')
          ->get('debug') ?? FALSE;
    }

    return $debug_active;
  }

  /**
   * Wrapper to check if we need to wait for delete operation to finish.
   *
   * @return bool
   *   TRUE if we should wait.
   */
  protected function shouldWaitForDeleteToFinish() {
    static $should_wait = NULL;

    if (is_null($should_wait)) {
      $should_wait = $this->configFactory
          ->get('search_api_algolia.settings')
          ->get('wait_for_delete') ?? FALSE;
    }

    return $should_wait;
  }

  /**
   * Wrapper function to check if multi-lingual language suffix is enabled.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   Index to check for.
   *
   * @return bool
   *   If language suffix is enabled.
   */
  protected function isLanguageSuffixEnabled(IndexInterface $index) {
    return $this->languageManager->isMultilingual() && $index->getOption('algolia_index_apply_suffix');
  }

  /**
   * Get all the languages supported by the Index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   Index.
   *
   * @return array
   *   Supported languages for the index.
   */
  protected function getLanguages(IndexInterface $index) {
    $languages = [];

    if (!($this->isLanguageSuffixEnabled($index))) {
      // If not multi-lingual or suffix not supported, we simply do it once
      // with empty language code.
      return [''];
    }

    foreach ($index->getDatasources() as $datasource) {
      $config = $datasource->getConfiguration();

      $always_valid = [
        LanguageInterface::LANGCODE_NOT_SPECIFIED,
        LanguageInterface::LANGCODE_NOT_APPLICABLE,
      ];

      foreach ($this->languageManager->getLanguages() as $language) {
        if (Utility::matches($language->getId(), $config['languages'])
          || in_array($language->getId(), $always_valid)) {
          $languages[$language->getId()] = $language->getId();
        }
      }
    }

    return $languages;
  }

}
