<?php

namespace Drupal\commerce_smart_importer\Plugin;

use Drupal\Core\File\FileSystemInterface;
/**
 * @file
 * Main Commerce Smart Importer Service.
 */

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\commerce_price\Entity\Currency;
use Drupal\Core\Utility\Token;
use Drupal\image\Entity\ImageStyle;
use Drupal\taxonomy\Entity\Term;
use Masterminds\HTML5\Exception;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_price\Price;
use Drupal\commerce_store\Entity\Store;
use Drupal\commerce_product\Entity\ProductAttributeValue;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Controller\ControllerBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\commerce_smart_importer\ImportingParameters;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactory;
use Drupal\physical\Weight;
use Drupal\physical\WeightUnit;
use Drupal\physical\Area;
use Drupal\physical\AreaUnit;
use Drupal\physical\Length;
use Drupal\physical\LengthUnit;
use Drupal\physical\Volume;
use Drupal\physical\VolumeUnit;
use InvalidArgumentException;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Symfony\Component\Yaml\Parser;
use Drupal\redirect\Entity\Redirect;

/**
 * This is main Commerce Smart Importer Service.
 */
class CommerceSmartImporerService extends ControllerBase {

  use DependencySerializationTrait;

  /**
   * Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactoryService;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * CommerceSmartImporerService constructor.
   */
  public function __construct(Connection $connection,
                              Token $token,
                              EntityFieldManager $entityFieldManager,
                              ConfigFactory $configFactory,
                              EntityTypeBundleInfo $entityTypeBundleInfo) {
    $this->database = $connection;
    $this->token = $token;
    $this->entityFieldManager = $entityFieldManager;
    $this->configFactory = $configFactory;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
  }

  /**
   * Create.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('token'),
      $container->get('entity_field.manager'),
      $container->get('config.factory'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * Formats one field value based on field settings.
   */
  public function formatField($field, $product, $folders, $create_mode) {
    $exception = FALSE;
    $product = trim($product);
    $create = '';
    switch (trim($field['field_types'])) {
      case 'text':
      case 'string':
        try {
          $create = $this->createString($product, $field['field_settings']);
          if ($field['machine_names'] == 'sku') {
            $create = $this->createSku($create, $field['field_settings']);
          }
        }
        catch (Exception $e) {
          $create = $e->getMessage();
          $exception = TRUE;
        }
        break;

      case 'text_long':
      case 'string_long':
      case 'text_with_summary':
        try {
          $create = $this->createTextSummary($product, $field['field_settings']);
        }
        catch (Exception $e) {
          $create = $e->getMessage();
          $exception = TRUE;
        }
        break;

      case 'integer':
        try {
          $create = $this->createInteger($product, $field['field_settings']);
        }
        catch (Exception $e) {
          $create = $e->getMessage();
          $exception = TRUE;
        }
        break;

      case 'float':
      case 'decimal':
        try {
          $create = $this->createDecimal($product, $field['field_settings']);
        }
        catch (Exception $e) {
          $create = $e->getMessage();
          $exception = TRUE;
        }
        break;

      case 'image':
      case 'file':
        try {
          $create = $this->createFile($product, $field['field_settings'], $folders, $create_mode, $field['field_types']);
        }
        catch (Exception $e) {
          $create = $e->getMessage();
          $exception = TRUE;
        }
        break;

      case 'commerce_price':
        $product = explode(' ', $product);
        if (!array_key_exists(1, $product)) {
          if (is_numeric($product[0])) {
            throw new Exception('No currency');
          }
          else {
            throw new Exception('No price');
          }
        }
        if (is_numeric(trim($product[0]))) {
          if (trim($product[0]) == 0) {
            $create = '';
          }
          else {
            $create = new Price(trim($product[0]), trim($product[1]));
          }
        }
        else {
          $create = 'Price for one variation of ' . $field['label'] . ' is not number!';
          $exception = TRUE;
        }
        break;

      case 'list_float':
      case 'list_string':
      case 'list_integer':
        try {
          $create = $this->createList($product, $field['field_settings']);
        }
        catch (Exception $e) {
          $create = $e->getMessage();
          $exception = TRUE;
        }
        break;

      case 'timestamp':
        try {
          $create = $this->createTimestamp($product, $field['field_settings']);
        }
        catch (Exception $e) {
          $create = $e->getMessage();
          $exception = TRUE;
        }
        break;

      case 'email':
        try {
          $create = $this->createEmail($product, $field['field_settings']);
        }
        catch (Exception $e) {
          $create = $e->getMessage();
          $exception = TRUE;
        }
        break;

      case 'link':
        try {
          $create = $this->createUrl($product, $field['field_settings']);
        }
        catch (Exception $e) {
          $create = $e->getMessage();
          $exception = TRUE;
        }
        break;

      case 'path':
        try {
          $create = $this->createPath($product, $field['field_settings']);
        }
        catch (Exception $e) {
          $create = $e->getMessage();
          $exception = TRUE;
        }
        break;

      case 'boolean':
        try {
          $create = $this->createBool($product, $field['field_settings']);
        }
        catch (Exception $e) {
          $create = $e->getMessage();
          $exception = TRUE;
        }
        break;

      case 'physical_dimensions':
        try {
          $create = $this->createPhysicalDimension($product, $field['field_settings']);
        }
        catch (Exception $e) {
          $create = $e->getMessage();
          $exception = TRUE;
        }
        break;

      case 'physical_measurement':
        switch ($field['field_settings']['measurement_type']) {
          case 'weight':
            try {
              $create = $this->createPhysicalMesurementWeight($product, $field['field_settings']);
            }
            catch (Exception $e) {
              $create = $e->getMessage();
              $exception = TRUE;
            }
            break;

          case 'area':
            try {
              $create = $this->createPhysicalMesurementArea($product, $field['field_settings']);
            }
            catch (Exception $e) {
              $create = $e->getMessage();
              $exception = TRUE;
            }
            break;

          case 'length':
            try {
              $create = $this->createPhysicalMesurementLength($product, $field['field_settings']);
            }
            catch (Exception $e) {
              $create = $e->getMessage();
              $exception = TRUE;
            }
            break;

          case 'volume':
            try {
              $create = $this->createPhysicalMesurementVolume($product, $field['field_settings']);
            }
            catch (Exception $e) {
              $create = $e->getMessage();
              $exception = TRUE;
            }
            break;
        }
        break;

      case 'entity_reference':
        if ($field['machine_names'] != 'variations') {
          switch ($field['field_settings']['target_type']) {
            case 'taxonomy_term':
              try {
                $create = $this->createTaxonomyTerm($product,
                  reset($field['field_settings']['handler_settings']['target_bundles']), $create_mode);
              }
              catch (Exception $e) {
                $create = $e->getMessage();
                $exception = TRUE;
              }
              break;

            case 'commerce_product_attribute_value':
              try {
                $create = $this->createAttribute($product,
                  reset($field['field_settings']['handler_settings']['target_bundles']), $create_mode);
              }
              catch (Exception $e) {
                $create = $e->getMessage();
                $exception = TRUE;
              }
              break;
            case 'commerce_store':
              try {
                $create = $this->getStore($product);
              }
              catch (Exception $e) {
                $create = $e->getMessage();
                $exception = TRUE;
              }
              break;
          }
        }
        break;

      default:
        throw new Exception("This field type is not supported!");
    }
    if ($exception) {
      throw new Exception($create);
    }
    else {
      return $create;
    }
  }

  /**
   * Creates physical dimension value based on field settings.
   */
  public function createPhysicalDimension($value, $field_settings) {
    $field_value = [];
    $value = explode(' ', $value);
    if(count($value) != 2) {
      throw new Exception('Dimension is in wrong format. Follow "LxWxH [unit]". For example 4x5x6 m.');
    }
    else {
      $split_value = explode('x', $value[0]);
      if(count($split_value) != 3) {
        throw new Exception('Length, width and height values are required in the format "LxWxH [unit]."');
      }
      else {
        $field_value['length'] = $split_value[0];
        $field_value['width'] = $split_value[1];
        $field_value['height'] = $split_value[2];
        $field_value['unit'] = $value[1];
        return $field_value;
      }
    }
  }

  /**
   * Creates string value based on field settings.
   */
  public function createString($data, $field_settings) {
    if (strlen($data) > $field_settings['max_length']) {
      throw new Exception('Maximum number of characters of ' . $field_settings['max_length']);
    }
    else {
      return trim($data);
    }
  }

  /**
   * Creates text summary value based on field settings.
   */
  public function createTextSummary($data, $field_settings) {
    return trim($data);
  }

  /**
   * Creates image value based on field settings.
   */
  public function createFile($data, $field_settings, $folders, $create_mode, $file_type = 'file') {
    if (empty($data)) {
      return NULL;
    }
    $config = $this->getConfig();
    if ($create_mode) {
      if (filter_var(trim($data), FILTER_VALIDATE_URL)) {
        $data = basename($data);
      }
    }
    $saveFolder = $this->token->replace($field_settings['file_directory']);
    if (strpos($saveFolder, 'temporary://') === FALSE) {
      $saveFolder = 'public://' . $saveFolder;
    }
    foreach ($folders as $key => $folder) {
      if (!is_array($folder) && strpos($folder, 'temporary://') === FALSE && strpos($folder, 'public://') === FALSE) {
        $folders[$key] = 'public://' . $folder;
      }
    }

    if (!is_dir($saveFolder)) {
      mkdir($saveFolder, 0777, TRUE);
    }

    if (filter_var(trim($data), FILTER_VALIDATE_URL)) {
      if ($file_type !== 'image' || @getimagesize(trim($data))) {
        $imageData = file_get_contents($data);
        $imagesPath = $saveFolder;
        $fileSave = file_save_data($imageData, $imagesPath . '/' . basename($data), FileSystemInterface::EXISTS_REPLACE);
        if ($config['flush_image_cache'] === '1' && $file_type === 'image') {
          $this->flushImageStyleUri($fileSave->getFileUri());
        }
        if (is_bool($fileSave)) {
          throw new Exception('Remote Image ' . $data . ' was not found!');
        }
        else {
          return ['target_id' => $fileSave->id()];
        }
      }
      else {
        throw new Exception('Image ' . $data . ' was not found!');
      }
    }
    else {
      if (is_iterable($folders)) {
        foreach ($folders as $key => $images) {
          if ($key === 'fids') {
            foreach ($images as $image) {
              $file = $this->entityTypeManager()->getStorage('file')->load($image);
              $uri = $file->getFileUri();
              if (strtolower($data) == strtolower(basename($uri))) {
                $imageData = file_get_contents($uri);
                $imagesPath = $saveFolder;
                $fileSave = file_save_data($imageData, $imagesPath . '/' . basename($uri), FileSystemInterface::EXISTS_REPLACE);
                if ($config['flush_image_cache'] === '1' && $file_type === 'image') {
                  $this->flushImageStyleUri($fileSave->getFileUri());
                }
                return ['target_id' => $fileSave->id()];
              }
            }
            foreach ($images as $image) {
              $file = $this->entityTypeManager()->getStorage('file')->load($image);
              $uri = $file->getFileUri();
              if ($data == $this->replaceDuplicateInNames(basename($uri))) {
                $imageData = file_get_contents($uri);
                $imagesPath = $saveFolder;
                $fileSave = file_save_data($imageData, $imagesPath . '/' . $this->replaceDuplicateInNames(basename($uri)), FileSystemInterface::EXISTS_REPLACE);
                if ($config['flush_image_cache'] === '1' && $file_type === 'image') {
                  $this->flushImageStyleUri($fileSave->getFileUri());
                }
                return ['target_id' => $fileSave->id()];
              }
            }
          }
          else {
            $dir = scandir($images);
            foreach ($dir as $file) {
              if (is_file($images . '/' . $file)) {
                if (strtolower($data) == strtolower($file)) {
                  $imageData = file_get_contents($images . '/' . $file);
                  $imagesPath = $saveFolder;
                  $fileSave = file_save_data($imageData, $imagesPath . '/' . $data, FileSystemInterface::EXISTS_REPLACE);
                  if ($config['flush_image_cache'] === '1' && $file_type === 'image') {
                    $this->flushImageStyleUri($fileSave->getFileUri());
                  }
                  return ['target_id' => $fileSave->id()];
                }
              }
            }
          }
        }
      }
    }

    throw new Exception('Image ' . $data . ' was not found!');
  }

  /**
   * {@inheritdoc}
   */
  public function flushImageStyleUri($uri) {
    $image_styles = ImageStyle::loadMultiple();
    foreach ($image_styles as $image_style) {
      /** @var $image_style \Drupal\image\Entity\ImageStyle */
      $image_style->flush($uri);
    }
  }

  /**
   * Creates Taxonomy term value based on field settings.
   */
  public function createTaxonomyTerm($name, $reference, $create = TRUE) {
    $vocabularies = Vocabulary::loadMultiple();
    if (isset($vocabularies[$reference])) {
      $vocab = $vocabularies[$reference];
    }
    else {
      foreach ($vocabularies as $key => $vocabulary) {
        if ($vocabulary->get('name') == $reference) {
          $reference = $key;
          $vocab = $vocabulary;
          break;
        }
      }
    }

    if (!isset($vocab)) {
      throw new Exception('Vocabulary does not exist');
    }
    $query = $this->entityTypeManager()->getStorage('taxonomy_term')->getQuery();
    $query->condition('vid', $reference);
    $query->condition('name', $name);
    $taxonomyId = $query->execute();

    if (empty($taxonomyId) && $create) {
      $term = Term::create([
        'vid' => $reference,
        'name' => $name,
      ]);
      $term->save();
      $taxonomyId = $term->id();
    }
    if (!$create) {
      return ['target_id' => uniqid()];
    }
    return is_array($taxonomyId) ? ['target_id' => current($taxonomyId)] : ['target_id' => $taxonomyId];
  }

  public function getStore($value) {
    if (is_numeric($value)) {
      $query = $this->database->select('commerce_store_field_data', 'cs')
        ->fields('cs', ['store_id'])
        ->condition('cs.store_id', $value)
        ->range(0, 1)
        ->execute();
      $store_ids = $query->fetchAll();
      if (count($store_ids) > 0) {
        return ['target_id' => reset($store_ids)->store_id];
      }
    }
    $query = $this->database->select('commerce_store_field_data', 'cs')
      ->fields('cs', ['store_id', 'name'])
      ->condition('cs.name', $value)
      ->range(0, 1)
      ->execute();
    $store_ids = $query->fetchAll();
    if (count($store_ids) > 0) {
      return ['target_id' => reset($store_ids)->store_id];
    }
    return '';
  }

  /**
   * Creates vocabulary.
   */
  public function createVocabulary($name, $vid = FALSE) {
    if ($vid === FALSE) {
      $vid = strtolower($name);
      $vid = str_replace(' ', '_', $vid);
    }
    $vocabularies = Vocabulary::loadMultiple();
    if (!isset($vocabularies[$vid])) {
      $vocabulary = Vocabulary::create([
        'vid' => $vid,
        'description' => '',
        'name' => $name,
      ]);
      $vocabulary->save();
    }

    return $vid;
  }

  /**
   * Creates attribute for product.
   */
  public function createAttribute($name, $reference, $create = TRUE) {
    $attributeId = $this->entityTypeManager()->getStorage('commerce_product_attribute_value')->getQuery()
      ->condition('attribute', $reference)
      ->condition('name', $name)
      ->execute();

    if (!empty($attributeId)) {
      $attributeId = array_keys($attributeId);
      $attributeId = array_shift($attributeId);
      $attributeId = ProductAttributeValue::load($attributeId);
      return $attributeId;
    }
    else {
      $attributeId = ProductAttributeValue::create([
        'attribute' => $reference,
        'name' => $name,
      ]);
      if (!$create) {
        $attributeId->save();
      }
      return $attributeId;
    }
  }

  /**
   * Creates integer value based on field settings.
   */
  public function createInteger($number, $field_settings) {
    $pass = TRUE;
    if (!is_numeric($number)) {
      $error = 'Must be number';
      throw new Exception($error);
    }
    else {
      if (!empty($field_settings['min'])) {
        if ($number < $field_settings['min']) {
          $error = 'Must be greater than ' . $field_settings['min'];
          $pass = FALSE;
        }
      }
      if (!empty($field_settings['max'])) {
        if ($number > $field_settings['max']) {
          $error = 'Must be smaller than ' . $field_settings['max'];
          $pass = FALSE;
        }
      }
    }
    if ($field_settings['unsigned'] == TRUE) {
      $number = abs($number);
    }
    if ($pass) {
      return round($number);
    }
    else {
      throw new Exception($error);
    }
  }

  /**
   * Creates decimal value based on field settings.
   */
  public function createDecimal($number, $field_settings) {
    $pass = TRUE;
    if (!is_numeric($number)) {
      $error = 'Must be number';
      throw new Exception($error);
    }
    else {
      if (!empty($field_settings['min'])) {
        if ($number < $field_settings['min']) {
          $error = 'Must be greater than ' . $field_settings['min'];
          $pass = FALSE;
        }
      }
      if (!empty($field_settings['max'])) {
        if ($number > $field_settings['max']) {
          $error = 'Must be smaller than ' . $field_settings['max'];
          $pass = FALSE;
        }
      }
    }
    if ($pass) {
      return $number;
    }
    else {
      throw new Exception($error);
    }
  }

  /**
   * Creates timestamp value based on field settings.
   */
  public function createTimestamp($stamp, $field_settings) {
    if (is_numeric($stamp) && (int) $stamp == $stamp) {
      return $stamp;
    }
    else {
      throw new Exception("Timestamp is not valid");
    }
  }

  /**
   * Checks sku validity.
   */
  public function createSku($value, $field_definition) {
    if ($this->getVariationIdBySku($value) !== FALSE) {
      throw new Exception("Sku already exists");
    }
    return $value;
  }

  /**
   * Creates email value based on field settings.
   */
  public function createEmail($email, $field_settings) {
    if (filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
      return trim($email);
    }
    else {
      throw new Exception("Timestamp is not valid");
    }
  }

  /**
   * Creates url value based on field settings.
   */
  public function createUrl($url, $field_settings, $name = 'link') {

    if (filter_var(trim($url), FILTER_VALIDATE_URL)) {
      if ($field_settings['title'] == 1) {
        return [
          "uri" => trim($url),
          "title" => $name,
          "options" => ["target" => "_blank"],
        ];
      }
      else {
        return ["uri" => trim($url), "options" => ["target" => "_blank"]];
      }
    }
    else {
      throw new Exception("Url is not valid");
    }
  }

  /**
   * Creates alias based on field settings.
   */
  public function createPath($url, $field_settings) {
    $url = trim($url);
    if ($url != filter_var($url, FILTER_SANITIZE_URL)) {
      throw new Exception("There are some illegal characters in url");
    }
    if (substr((string) $url, 0, 1) !== '/') {
      throw new Exception("URL alias must start with /");
    }
    return ['alias' => $url];
  }

  /**
   * Creates bool value based on field settings.
   */
  public function createBool($bool, $field_settings) {
    if ($bool == 1 || $bool == 0) {
      return $bool;
    }
    elseif ($bool == 'on_label' || $field_settings['on_label'] == $bool) {
      return 1;
    }
    elseif ($bool == 'off_label' || $field_settings['off_label'] == $bool) {
      return 0;
    }
    else {
      throw new Exception("Not valid boolean");
    }
  }

  /**
   * Creates Weight Physical Mesurement value based on field settings.
   */
  public function createPhysicalMesurementWeight($value, $field_settings) {
    $value = explode(' ', $value);
    if (count($value) != 2) {
      throw new Exception('Physical mesurement must have only value and unit(format: value unit)');
    }
    try {
      WeightUnit::assertExists($value[1]);
      return new Weight($value[0], $value[1]);
    }
    catch (InvalidArgumentException $e) {
      throw new Exception($e->getMessage());
    }
  }

  /**
   * Creates Area Physical Mesurement value based on field settings.
   */
  public function createPhysicalMesurementArea($value, $field_settings) {
    $value = explode(' ', $value);
    if (count($value) != 2) {
      throw new Exception('Physical mesurement must have only value and unit(format: value unit)');
    }
    try {
      AreaUnit::assertExists($value[1]);
      return new Area($value[0], $value[1]);
    }
    catch (InvalidArgumentException $e) {
      throw new Exception($e->getMessage());
    }
  }

  /**
   * Creates Length Physical Mesurement value based on field settings.
   */
  public function createPhysicalMesurementLength($value, $field_settings) {
    $value = explode(' ', $value);
    if (count($value) != 2) {
      throw new Exception('Physical mesurement must have only value and unit(format: value unit)');
    }
    try {
      LengthUnit::assertExists($value[1]);
      return new Length($value[0], $value[1]);
    }
    catch (InvalidArgumentException $e) {
      throw new Exception($e->getMessage());
    }
  }

  /**
   * Creates Length Physical Mesurement value based on field settings.
   */
  public function createPhysicalMesurementVolume($value, $field_settings) {
    $value = explode(' ', $value);
    if (count($value) != 2) {
      throw new Exception('Physical mesurement must have only value and unit(format: value unit)');
    }
    try {
      VolumeUnit::assertExists($value[1]);
      return new Volume($value[0], $value[1]);
    }
    catch (InvalidArgumentException $e) {
      throw new Exception($e->getMessage());
    }
  }

  /**
   * Creates list value based on field settings.
   */
  public function createList($data, $field_settings) {
    foreach ($field_settings['allowed_values'] as $key => $allowed_value) {
      if ($key == trim($data) || $allowed_value == trim($data)) {
        return $key;
      }
    }
    throw new Exception('Value is not allowed');
  }

  /**
   * Replaces _ from names when there are more than one file with same name.
   */
  private function replaceDuplicateInNames($basename) {
    $dotIndex = (int) strrpos($basename, '.');
    $underscoreIndex = (int) strrpos($basename, '_');
    $temp_name = substr($basename, 0, $underscoreIndex);
    $temp_name .= substr($basename, $dotIndex, strlen($basename) - $dotIndex);

    return $temp_name;
  }

  /**
   * Checks if currency is valid and returns formated.
   */
  public function checkCurrencyValidity($code) {
    /* @var \Drupal\commerce_price\Entity\Currency $currecies */
    $currecies = Currency::loadMultiple();
    foreach ($currecies as $currency) {
      if ($code == $currency->getCurrencyCode() || strtolower($code) == strtolower($currency->getName()) ||
        $code == $currency->getNumericCode() || $code == $currency->getSymbol()) {
        return $currency->getCurrencyCode();
      }
    }
    return FALSE;
  }

  /**
   * Generates new sku.
   */
  public function generateSku() {
    $config = $this->getConfig();

    $method = $config['sku_method'];

    $prefix = $config['sku_prefix'];

    if ($method == 0) {
      // Auto increment.
      if ($this->config('commerce_smart_importer.settings')->get('increment_saver') == NULL) {
        $this->configFactoryService
          ->getEditable('commerce_smart_importer.settings')
          ->set('increment_saver', 0)
          ->save();
      }
      $increment = $this->config('commerce_smart_importer.settings')->get('increment_saver');

      do {
        $increment++;
        $query = $this->entityTypeManager->getStorage('commerce_product_variation')->getQuery();
        $query->condition('sku', $prefix . $increment);
      } while (!empty($query->execute()));
      return $prefix . $increment;
    }
    elseif ($method == 1) {

      $randomDigitsNumber = $config['sku_random_digits'];

      do {
        $sku = '';
        for ($i = 0; $i < $randomDigitsNumber; $i++) {
          $sku .= mt_rand(0, 9);
        }
        $query = $this->entityTypeManager()->getStorage('commerce_product_variation')->getQuery();
        $query->condition('sku', $prefix . $sku);
      } while (!empty($query->execute()));
      return $prefix . $sku;
    }
  }

  /**
   * Gets all Terms from one vocabulary.
   */
  public function getReferencedTaxonomyTerms($entity = 'all', $type = 'default') {

    $commerce_product_variation_fields = $this->entityFieldManager
      ->getFieldDefinitions('commerce_product_variation', $type);

    $commerce_product_fields = $this->entityFieldManager
      ->getFieldDefinitions('commerce_product', $type);

    $entity_fields = [];
    if ($entity == 'all') {
      $entity_fields = array_merge($commerce_product_fields, $commerce_product_variation_fields);
    }
    elseif ($entity == 'variation') {
      $entity_fields = $commerce_product_variation_fields;
    }
    elseif ($entity == 'product') {
      $entity_fields = $commerce_product_fields;
    }
    $taxonomies = [];
    foreach ($entity_fields as $key => $entity_field) {
      if ($entity_field->getType() == 'entity_reference') {
        if ($entity_field->getSettings()['target_type'] == 'taxonomy_term') {
          $field = [];
          if ($entity_field->getLabel() instanceof TranslatableMarkup) {
            $field['name'] = $entity_field->getLabel()->getUntranslatedString();
          }
          else {
            $field['name'] = is_object($entity_field->getLabel()) ? current($entity_field->getLabel()) : $entity_field->getLabel();
          }
          $field['machine_name'] = $key;
          $field['target_bundles'] = $entity_field->getSettings()['handler_settings']['target_bundles'];
          $taxonomies[] = $field;
        }
      }
    }
    return $taxonomies;
  }

  /**
   * Formats field definition for given product and variation type.
   */
  public function getFieldDefinition($with_identifiers = FALSE) {

    $excluded_fields = $this->getExcludedFieldNames($with_identifiers);
    $config = $this->getConfig();
    $product_field_definitions = $this->entityFieldManager->getFieldDefinitions('commerce_product', $config['commerce_product_bundle']);
    $fields['product'] = [];
    foreach ($product_field_definitions as $field_name => $product_field_definition) {
      if (!in_array($field_name, $excluded_fields)) {
        $fields['product'][] = $this->formatFieldDefinition($product_field_definition, $field_name);
      }
    }

    $variation_field_definitions = $this->entityFieldManager->getFieldDefinitions('commerce_product_variation', $config['commerce_product_variation_bundle']);
    $fields['variation'] = [];
    foreach ($variation_field_definitions as $field_name => $variation_field_definition) {
      if (!in_array($field_name, $excluded_fields) && $field_name != 'title' && $field_name != 'product_id') {
        if ($field_name == 'price') {
          $price = $this->formatFieldDefinition($variation_field_definition, $field_name);
        }
        else {
          $fields['variation'][] = $this->formatFieldDefinition($variation_field_definition, $field_name);
        }
      }
    }
    if (isset($price)) {
      $fields['variation'][] = $price;
    }
    else {
      throw new Exception("Missing price object in variation");
    }

    $help_fields['label'] = 'Currency';
    $help_fields['machine_names'] = 'currency';
    $help_fields['field_types'] = 'currency';
    $help_fields['required'] = TRUE;
    $help_fields['cardinality'] = 1;
    $fields['variation'][] = $help_fields;

    if ($this->moduleHandler()->moduleExists('redirect')) {
      $help_fields['label'] = 'Redirection';
      $help_fields['machine_names'] = 'redirection';
      $help_fields['field_types'] = 'redirection';
      $help_fields['required'] = FALSE;
      $help_fields['cardinality'] = -1;
      $help_fields['field_settings']['read-only'] = FALSE;
      $fields['product'][] = $help_fields;
    }
    $this->renameDuplicateFieldDefinitions($fields);
    return $fields;
  }

  /**
   * Helper function for getFieldDefinition, formats one field.
   *
   * @see getFieldDefinition()
   */
  private function formatFieldDefinition($field_definition, $machine_name) {
    $field = [];
    if ($field_definition->getLabel() instanceof TranslatableMarkup) {
      $label = $field_definition->getLabel()->getUntranslatedString();
    }
    else {
      $label = is_object($field_definition->getLabel()) ? current($field_definition->getLabel()) : $field_definition->getLabel();
    }

    switch ($machine_name) {
      case 'product_id':
        $field['label'] = 'ID(product)';
        break;

      case 'variation_id':
        $field['label'] = 'ID(variation)';
        break;

      default:
        $field['label'] = $label;
    }
    $field['machine_names'] = $machine_name;
    $field['field_types'] = $field_definition->getType();
    $field['field_settings'] = $field_definition->getSettings();

    if ($field_definition instanceof FieldConfig) {
      $fieldStorage = $field_definition->getFieldStorageDefinition();
      $field['required'] = $field_definition->get('required');
      $field['cardinality'] = $fieldStorage->get('cardinality');
      $field['field_settings']['default_value'] = $field_definition->get('default_value');
      $field['field_settings']['default_value'] = is_array($field['field_settings']['default_value']) ? current($field['field_settings']['default_value']) : $field['field_settings']['default_value'];
    }
    elseif ($field_definition instanceof BaseFieldDefinition) {
      $field['cardinality'] = $field_definition->getCardinality();
      $field['required'] = $field_definition->isRequired();
      if ($machine_name == 'sku') {
        $field['field_settings']['default_value'] = 'generateSKU';
      }
      elseif (!isset($field['field_settings']['default_value'])) {
        if ($field_definition->getDefaultValueCallback() == NULL) {
          $field['field_settings']['default_value'] = FALSE;
        }
      }
    }
    $identifiers = $this->getIdentifierFields();
    if (in_array($machine_name, $identifiers['product']) || in_array($machine_name, $identifiers['variation'])) {
      $field['field_settings']['read-only'] = TRUE;
    }
    else {
      $field['field_settings']['read-only'] = FALSE;
    }
    return $field;
  }

  /**
   * Helper function for getFieldDefinition, returns excluded fields.
   *
   * @see getFieldDefinition()
   */
  private function getExcludedFieldNames($with_identifiers = FALSE) {
    $config = $this->getConfig();
    $excluded_fields = [
      'product_id',
      'uuid',
      'langcode',
      'type',
      'status',
      'uid',
      'created',
      'changed',
      'default_langcode',
      'metatag',
      'variations',
      'variation_id',
    ];

    if (!$config['expose_store']) {
      $excluded_fields[] = 'stores';
    }

    if ($with_identifiers) {
      $identifiers = $this->getIdentifierFields();
      foreach ($excluded_fields as $key => $excluded_field) {
        if (in_array($excluded_field, $identifiers['product']) || in_array($excluded_field, $identifiers['variation'])) {
          unset($excluded_fields[$key]);
        }
      }
    }
    return $excluded_fields;
  }

  /**
   * Renames field definitions if there are more fields with same label.
   */
  private function renameDuplicateFieldDefinitions(&$fields) {
    $used_labels = [];
    $duplicate_labels = [];

    foreach ($fields['product'] as $key => $field) {
      if (in_array($field['label'], $used_labels)) {
        $duplicate_labels[] = $field['label'];
      }
      else {
        $used_labels[] = $field['label'];
      }
    }
    foreach ($fields['variation'] as $key => $field) {
      if (in_array($field['label'], $used_labels)) {
        $duplicate_labels[] = $field['label'];
      }
      else {
        $used_labels[] = $field['label'];
      }
    }
    foreach ($fields['product'] as $key => $field) {
      if (in_array($field['label'], $duplicate_labels)) {
        $fields['product'][$key]['label'] .= '(' . $fields['product'][$key]['machine_names'] . ')';
      }
    }
    foreach ($fields['variation'] as $key => $field) {
      if (in_array($field['label'], $duplicate_labels)) {
        $fields['variation'][$key]['label'] .= '(' . $fields['variation'][$key]['machine_names'] . ')';
      }
    }
  }

  /**
   * Changes file location in field definition.
   */
  public function changeFilePathInFieldDefinition(&$field_definitions, $path) {
    foreach ($field_definitions['product'] as $key => $field_definition) {
      if (!array_key_exists('field_settings', $field_definition)) {
        continue;
      }
      if (array_key_exists('file_directory', $field_definition['field_settings'])) {
        $field_definitions['product'][$key]['field_settings']['file_directory'] = $path;
      }
    }
    foreach ($field_definitions['variation'] as $key => $field_definition) {
      if (!array_key_exists('field_settings', $field_definition)) {
        continue;
      }
      if (array_key_exists('file_directory', $field_definition['field_settings'])) {
        $field_definitions['variation'][$key]['field_settings']['file_directory'] = $path;
      }
    }
  }

  /**
   * Counts Products and Variations in CSV file.
   */
  public function countProductsAndVariations($file) {
    $linecount = 0;
    $productCount = 0;
    $handle = fopen($file, "r");

    while (($line = fgetcsv($handle)) !== FALSE) {
      $linecount++;
      if (!empty($line[0])) {
        $productCount++;
      }
    }
    fclose($handle);

    return [
      'product_count' => $productCount - 2,
      'variation_count' => $linecount - 2,
    ];
  }

  /**
   * Creates product.
   */
  public function createNewProduct($field_definitions, $product, ImportingParameters $parameters, $external_folders, $override_values) {
    $config = $this->getConfig();
    $each_field_log = [];
    // Products.
    $is_product_creatable = TRUE;
    $redirections = [];
    foreach ($field_definitions['product'] as $key => $field_definition) {
      if ($field_definition['field_types'] == 'currency' || !array_key_exists('index', $field_definition)) {
        continue;
      }
      if ($field_definition['field_types'] === 'redirection') {
        $redirections = explode('|', $product['product'][$field_definition['index']]);
        continue;
      }
      $values = explode('|', $product['product'][$field_definition['index']]);
      if (isset($override_values['product'])) {
        $this->overrideValue($values, $override_values['product'], $field_definition);
      }
      // Formats field and checks for validity.
      $each_field_log[$key] = $this->formatMultipleFieldValues($values, $field_definition, $parameters, $external_folders);
      $this->duplicateValuesPass($each_field_log[$key]);
      $this->cardinalityPass($each_field_log[$key], $field_definition);
      $this->useDefaultValuePass($each_field_log[$key], $field_definition);
      $this->requiredPass($each_field_log[$key], $field_definition);
    }
    if ($is_product_creatable) {
      $is_product_creatable = $parameters->matchParameters($each_field_log);
    }
    $this->changeFieldHasLog($each_field_log);
    // Varitions.
    $variation_each_field_log = [];
    foreach ($product['variations'] as $key => $variation) {
      $variation_each_field_log[$key] = [];
      $variation_each_field_log[$key]['creatable'] = TRUE;
      if (!$this->variationCurrencyValidityPass($variation, $field_definitions)) {
        $variation_each_field_log[$key]['currency'] = FALSE;
      }
      else {
        $variation_each_field_log[$key]['currency'] = TRUE;
      }
      foreach ($field_definitions['variation'] as $field_key => $field_definition) {
        if ($field_definition['field_types'] == 'currency' || !array_key_exists('index', $field_definition)) {
          continue;
        }
        if (is_array($variation[$field_definition['index']])) {
          continue;
        }
        $values = explode('|', $variation[$field_definition['index']]);
        if (isset($override_values['variations'][$key])) {
          $this->overrideValue($values, $override_values['variations'][$key], $field_definition);
        }
        $variation_each_field_log[$key][$field_key] = $this->formatMultipleFieldValues($values, $field_definition, $parameters, $external_folders);
        $this->duplicateValuesPass($variation_each_field_log[$key][$field_key]);
        $this->cardinalityPass($variation_each_field_log[$key][$field_key], $field_definition);
        $this->useDefaultValuePass($variation_each_field_log[$key][$field_key], $field_definition);
        $this->requiredPass($variation_each_field_log[$key][$field_key], $field_definition);
        if ($field_definition['machine_names'] === 'sku') {
          $variation_each_field_log[$key][$field_key]['sku'] = TRUE;
        }
      }
      if ($variation_each_field_log[$key]['creatable']) {
        $variation_each_field_log[$key]['creatable'] = $parameters->matchParameters($variation_each_field_log[$key]);
      }
      $this->changeFieldHasLog($variation_each_field_log[$key]);
    }
    if (!$each_field_log['has_log']) {
      foreach ($variation_each_field_log as $one_field_log) {
        if ($one_field_log['has_log']) {
          $each_field_log['has_log'] = TRUE;
          break;
        }
      }
    }
    $error_log = [
      'product' => $each_field_log,
      'variations' => $variation_each_field_log,
    ];
    $product_data = $this->formatValuesArray($each_field_log, $field_definitions['product']);
    $variation_data = [];
    $valid_variations = TRUE;
    foreach ($variation_each_field_log as $variation_field_log) {
      if ($variation_field_log['creatable']) {
        $variation_data[] = $this->formatValuesArray($variation_field_log, $field_definitions['variation']);
      }
      if ($parameters->notValidVariations === FALSE && !$variation_field_log['creatable']) {
        $valid_variations = FALSE;
      }
    }
    $created = FALSE;
    $valid_import = FALSE;
    if ($valid_variations) {
      if ($is_product_creatable && count($variation_data) > 0) {
        $product_data['uid'] = $this->currentUser()->id();
        $stores = [];
        if (!array_key_exists('stores', $product_data) || !is_array($product_data['stores']) || count($product_data['stores']) === 0) {
          if ($config['store'] != 'all') {
            $stores[] = ['target_id' => $config['store']];
          }
          else {
            $store_ids = $this->getAllStoreIds();
            foreach ($store_ids as $store_id) {
              $stores[] = ['target_id' => $store_id];
            }
          }
          $product_data['stores'] = $stores;
        }

        $product_data['type'] = $config['commerce_product_bundle'];
        $productCreation = Product::create($product_data);
        foreach ($variation_data as $variation_datum) {
          $variation_datum['type'] = $config['commerce_product_variation_bundle'];
          $variationCreationTemp = ProductVariation::create($variation_datum);
          if ($variationCreationTemp->validate()) {
            $variationCreationTemp->save();
            $valid_import = TRUE;
            $productCreation->addVariation($variationCreationTemp);
          }
        }
        if ($valid_import) {
          $productCreation->save();
          if ($this->moduleHandler()->moduleExists('redirect')) {
            $this->createProductRedirection($productCreation, $redirections);
          }
          if ($parameters->createProduct) {
            $created = TRUE;
          }
        }
      }
    }

    return ['error_log' => $error_log, 'created' => $created];
  }

  /**
   * Helper function for createNewProduct.
   *
   * Formats multiple values.
   */
  public function formatMultipleFieldValues($values, $field_definition, ImportingParameters $parameters, $external_folders) {
    $log['values'] = [];
    $log['not_valid'] = [];
    $log['has_log'] = FALSE;
    foreach ($values as $key => $value) {
      if ($value != '') {
        try {
          $log['values'][] = $this->formatField($field_definition, $value, $external_folders, $parameters->createProduct);
        }
        catch (Exception $e) {
          $log['not_valid'][] = [
            'log' => $e->getMessage(),
            'order' => $key + 1,
          ];
          $log['has_log'] = TRUE;
        }
      }
      elseif ($field_definition['machine_names'] == 'weight') {
        try {
          $log['values'][] = $this->formatField($field_definition, '0 kg', $external_folders, $parameters->createProduct);
        }
        catch (Exception $e) {
          $log['not_valid'][] = [
            'log' => $e->getMessage(),
            'order' => (string) ($key + 1),
          ];
          $log['has_log'] = TRUE;
        }
      }
    }
    return $log;
  }

  /**
   * Helper function for createNewProduct.
   *
   * Checks if field log contains duplicate values, returns corrected.
   */
  public function duplicateValuesPass(&$field_log) {
    $new_values = [];
    $field_log['duplicates'] = TRUE;
    foreach ($field_log['values'] as $value) {
      if (!in_array($value, $new_values)) {
        $new_values[] = $value;
      }
      else {
        $field_log['duplicates'] = FALSE;
        $field_log['has_log'] = TRUE;
      }
    }
    $field_log['values'] = $new_values;
  }

  /**
   * Helper function for createNewProduct.
   *
   * Checks if field log satisfies cardinality, returns corrected.
   */
  public function cardinalityPass(&$field_log, $field_definition) {
    if ($field_definition['cardinality'] == -1) {
      $field_log['cardinality'] = TRUE;
    }
    elseif ($field_definition['cardinality'] < count($field_log['values'])) {
      $field_log['values'] = array_slice($field_log['values'], 0, $field_definition['cardinality']);
      $field_log['cardinality'] = FALSE;
      $field_log['has_log'] = TRUE;
    }
    else {
      $field_log['cardinality'] = TRUE;
    }
  }

  /**
   * Helper function for createNewProduct.
   *
   * If needed this will use default value.
   */
  public function useDefaultValuePass(&$field_log, $field_definition) {
    $default_index = $field_definition['field_types'] == 'image' ? 'default_image' : 'default_value';

    if (count($field_log['values']) == 0 && $field_definition['field_settings'][$default_index] !== FALSE) {
      if ($field_definition['field_settings'][$default_index] == 'generateSKU') {
        $field_log['values'] = [$this->generateSku()];
        $field_log['default_value'] = FALSE;
        $field_log['has_log'] = TRUE;
      }
      else {
        $field_log['values'] = $field_definition['field_settings'][$default_index];
        $field_log['default_value'] = FALSE;
        $field_log['has_log'] = TRUE;
      }
    }
    else {
      $field_log['default_value'] = TRUE;
    }
  }

  /**
   * Helper function for createNewProduct.
   */
  public function requiredPass(&$field_log, $field_definition) {
    if (count($field_log['values']) == 0 && $field_definition['required'] == TRUE) {
      $field_log['required'] = FALSE;
      $field_log['has_log'] = TRUE;
    }
    else {
      $field_log['required'] = TRUE;
    }
  }

  /**
   * Checks if currency is valid and reformats price.
   */
  private function variationCurrencyValidityPass(&$variation, $field_definitions) {
    $currency = '';
    foreach ($field_definitions['variation'] as $field_definition) {
      if ($field_definition['field_types'] == 'currency') {
        $currency = $variation[$field_definition['index']];
        break;
      }
    }
    $currency = $this->checkCurrencyValidity($currency);
    if ($currency === FALSE) {
      return FALSE;
    }
    foreach ($field_definitions['variation'] as $field_definition) {
      if ($field_definition['field_types'] == 'commerce_price') {
        if (isset($field_definition['index'])) {
          if (strpos($variation[$field_definition['index']], $currency) === FALSE && $variation[$field_definition['index']] != '') {
            $variation[$field_definition['index']] = $variation[$field_definition['index']] . ' ' . $currency;
          }
        }
      }
    }
    return TRUE;
  }

  /**
   * Overrides value if is available.
   */
  private function overrideValue(&$values, $override_values, $field_definition) {
    if (isset($override_values[$field_definition['machine_names']])) {
      $values = explode('|', $override_values[$field_definition['machine_names']]);
    }
  }

  /**
   * Helper function for createNewProduct.
   *
   * Formats values from field log.
   */
  private function formatValuesArray($field_log, $field_definitions) {
    $values = [];
    foreach ($field_definitions as $key => $field_definition) {
      if (array_key_exists($key, $field_log)) {
        $values[$field_definition['machine_names']] = count($field_log[$key]['values']) == 1 ? current($field_log[$key]['values']) : $field_log[$key]['values'];
      }
    }
    return $values;
  }

  /**
   * Changes if log should be logged.
   */
  private function changeFieldHasLog(&$fields_log) {
    $fields_log['has_log'] = FALSE;
    foreach ($fields_log as $field_log) {
      if ($field_log['has_log']) {
        $fields_log['has_log'] = TRUE;
        return;
      }
    }
  }

  /**
   * Add redirections that will lead to product.
   */
  public function createProductRedirection(Product $product_object, array $redirections) {
    $url = 'internal:' . $product_object->toUrl()->toString();
    foreach ($redirections as $redirection) {
      if (empty($redirection)) {
        continue;
      }
      if (substr((string) $redirection, 0, 1) === '/') {
        $redirection = substr($redirection, 1);
      }
      Redirect::create([
        'redirect_source' => $redirection,
        'redirect_redirect' => $url,
        'language' => 'und',
        'status_code' => '301',
      ])->save();
    }
  }

  /**
   * Exports groups of entity based on field_deifnitions.
   */
  public function exportMultipleFields($entity_type, $entity_id, $field_definitions) {
    if (empty($field_definitions)) {
      return [];
    }

    $entity = $this->entityTypeManager()->getStorage($entity_type)->load($entity_id);
    $values = [];
    foreach ($field_definitions as $field_definition) {
      $values[] = $this->exportField($entity, $field_definition);
    }
    return $values;
  }

  /**
   * Force different types of fields to string.
   */
  public function exportField($entity, $field_definition) {
    $value = '';
    switch ($field_definition['field_types']) {
      case 'text':
      case 'text_long':
      case 'string_long':
      case 'float':
      case 'decimal':
      case 'timestamp':
      case 'email':
      case 'link':
      case 'boolean':
      case 'string':
      case 'text_with_summary':
      case 'weight':
      case 'list_string':
      case 'list_float':
      case 'list_integer':
      case 'integer':
        $value = $this->exportDefaultField($entity, $field_definition);
        break;

      case 'commerce_price':
        $value = $this->exportPrice($entity, $field_definition);
        break;

      case 'currency':
        $value = $this->exportCurrency($entity);
        break;

      case 'entity_reference':
        $value = $this->exportEntity($entity, $field_definition);
        break;

      case 'image':
      case 'file':
        $value = $this->exportImage($entity, $field_definition);
        break;

      case 'physical_measurement':
        $value = $this->exportPhysicalMeasurement($entity, $field_definition);
        break;

      case 'redirection':
        $value = '';
        break;

      default:
        $value = $this->exportDefaultField($entity, $field_definition);
        break;

    }
    return $value;
  }

  /**
   * Helper function for exportField.
   *
   * Most of fields type export.
   */
  public function exportDefaultField($entity, $field_definition) {
    $values = [];
    if (!$entity->hasField($field_definition['machine_names'])) {
      return '';
    }
    foreach ($entity->get($field_definition['machine_names'])->getValue() as $item) {
      $values[] = current($item);
    }
    $values = implode('|', $values);
    return $values;
  }

  /**
   * Helper function for exportField.
   *
   * Exports price.
   */
  public function exportPrice($entity, $field_definition) {
    $values = [];
    foreach ($entity->get($field_definition['machine_names'])->getValue() as $item) {
      $field = $item;
      $values[] = number_format($field['number'], 2, '.', '');
    }
    $values = implode('|', $values);
    return $values;
  }

  /**
   * Helper function for exportField.
   *
   * Exports Currency.
   */
  public function exportCurrency($entity) {
    $value = $entity->get('price');
    $field = current($value->getValue());
    return $field['currency_code'];
  }

  /**
   * Helper function for exportField.
   *
   * Exports entity name(title).
   */
  public function exportEntity($entity, $field_definition) {

    $values = [];
    foreach ($entity->get($field_definition['machine_names'])->getValue() as $item) {
      $entity = $this->entityTypeManager()->getStorage($field_definition['field_settings']['target_type'])->load($item['target_id']);
      if ($entity->hasField('name')) {
        $values[] = $entity->getName();
      }
      elseif ($entity->hasField('title')) {
        $values[] = $entity->getTitle();
      }
    }
    $values = implode('|', $values);
    return $values;
  }

  /**
   * Helper function for exportField.
   *
   * Exports filename.
   */
  public function exportImage($entity, $field_definition) {
    $values = [];
    foreach ($entity->get($field_definition['machine_names'])->getValue() as $item) {
      $values[] = $this->entityTypeManager()->getStorage('file')->load($item['target_id'])->getFilename();
    }
    $values = implode('|', $values);
    return $values;
  }

  /**
   * Exports physical mesurement.
   */
  public function exportPhysicalMeasurement($entity, $field_definition) {
    if (!empty($entity->get($field_definition['machine_names'])->getValue())) {
      $value = $entity->get($field_definition['machine_names'])->getValue();
      return current($value)['number'] . ' ' . current($value)['unit'];
    }
    else {
      return '';
    }
  }

  /**
   * Caution: Erase all products on site.
   *
   * @deprecated
   */
  public function eraseAllProductsOnSite($pass) {
    if ($pass == 'mrmotmrmot23#42234d') {
      $results = $this->database->select('commerce_product')
        ->fields('commerce_product', ['product_id'])
        ->execute()->fetchAll();

      $count = count($results);
      $batches = ceil($count / 70);
      $batch = [
        'title' => $this->t('ERASING ALL DATA'),
        'init_message' => $this->t('Beginning...'),
        'progress_message' => $this->t('ERASED @current out of @total products'),
        'error_message' => $this->t('Something went wrong'),
        'progressive' => FALSE,
        'finished' => [$this, 'finished'],
        'operations' => [],
      ];

      for ($i = 0; $i < $batches; $i++) {
        $batch['operations'][] = [[$this, 'productEraser'], [70]];
      }
      batch_set($batch);
    }
  }

  /**
   * Caution: Erases first 70 products on site.
   *
   * @deprecated
   */
  public function productEraser($number) {

    $results = $this->database->select('commerce_product')
      ->fields('commerce_product', ['product_id'])
      ->range(0, $number)
      ->execute()->fetchAll();
    $ids = [];
    foreach ($results as $result) {
      $ids[] = $result->product_id;
    }
    $products = Product::loadMultiple($ids);
    foreach ($products as $result) {
      $result->delete();
    }
  }

  /**
   * Returnst identifiers fields machine names.
   */
  public function getIdentifierFields() {
    return [
      'product' => ['product_id'],
      'variation' => ['sku', 'variation_id'],
    ];
  }

  /**
   * Updates entity with given values.
   */
  public function updateProduct($entity, $fields, $value, $external_folders, ImportingParameters $parameters) {
    $change = FALSE;
    $error_log = [];
    $redirections = [];
    foreach ($fields as $key => $field) {
      if ($field['machine_names'] == 'currency' || $field['field_settings']['read-only']) {
        continue;
      }
      if (empty($value[$field['index']])) {
        continue;
      }
      if ($field['field_types'] == 'redirection') {
        $redirections = explode('|', $value[$field['index']]);
        $change = TRUE;
        continue;
      }
      $values = explode('|', $value[$field['index']]);
      $field_log = $this->formatMultipleFieldValues($values, $field, new ImportingParameters(), $external_folders);
      if ($parameters->appendImages && $field['field_types'] == 'image') {
        $field_log['values'] = array_merge($entity->get($field['machine_names'])->getValue(), $field_log['values']);
      }
      $this->duplicateValuesPass($field_log);
      $this->cardinalityPass($field_log, $field);
      $this->useDefaultValuePass($field_log, $field);
      $this->requiredPass($field_log, $field);
      $field_value = count($field_log['values']) == 1 ? current($field_log['values']) : $field_log['values'];
      $accepted = $parameters->matchOneFieldLog($field_log);
      if (!$accepted) {
        $error_log[$key] = $field_log;
      }
      else {
        $entity->set($field['machine_names'], $field_value);
        $change = TRUE;
      }
    }
    if ($change && $parameters->createProduct) {
      $entity->save();
      if ($entity->getEntityTypeId() == 'commerce_product') {
        if ($this->moduleHandler()->moduleExists('redirect')) {
          $this->createProductRedirection($entity, $redirections);
        }
      }
    }
    if (!empty($error_log)) {
      $this->changeFieldHasLog($error_log);
      return $error_log;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Returns variation id by sku.
   */
  public function getVariationIdBySku($id) {
    $query = $this->database->query("SELECT variation_id FROM commerce_product_variation_field_data WHERE sku='" . $id . "'");
    $sku = $query->fetchAll();

    if (!empty($sku)) {
      return $sku[0]->variation_id;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Returns config based on default and systems.
   *
   * Purpose of this is for smart importer to work without config.
   */
  public function getConfig() {
    $config = $this->config('commerce_smart_importer.settings')->getRawData();
    $parser = new Parser();
    if (is_file(drupal_get_path('module', 'commerce_smart_importer') . '/config/install/commerce_smart_importer.settings.yml')) {
      $default_config = $parser->parseFile(drupal_get_path('module', 'commerce_smart_importer') . '/config/install/commerce_smart_importer.settings.yml');
    }
    else {
      return $config;
    }

    foreach ($default_config as $key => $value) {
      if (!isset($config[$key])) {
        $config[$key] = $value;
      }
    }
    return $config;
  }

  /**
   * Returns list of bundles for entity.
   */
  public function getEntityBundles($entity_name) {
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_name);
    $entityBundles = [];
    foreach ($bundles as $key => $bundle) {
      $entityBundles[$key] = $bundle['label'];
    }
    return $entityBundles;
  }

  public function getAllStoreIds() {
    $store_results = $this->database->query("SELECT store_id FROM commerce_store_field_data")->fetchAll();
    $stores = [];
    foreach ($store_results as $store_result) {
      $stores[] = current($store_result);
    }
    return $stores;
  }

}
