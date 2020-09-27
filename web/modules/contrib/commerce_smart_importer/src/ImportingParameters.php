<?php

namespace Drupal\commerce_smart_importer;

/**
 * Parameters what will be imported.
 */
class ImportingParameters {

  /**
   * Indicates wheter or not fields with duplicate values will be imported.
   *
   * @var bool
   */
  public $duplicateValues = TRUE;

  /**
   * Indicates wheter or not fields with incorrect values will be imported.
   *
   * @var bool
   */
  public $incorrectValues = TRUE;

  /**
   * Import products with fields thats exceeds cardinality.
   *
   * @var bool
   */
  public $exceedsCardinality = TRUE;

  /**
   * Indicates wheter or not to import fields where default value will be used.
   *
   * @var bool
   */
  public $defaultValues = TRUE;

  /**
   * Update: indicates wheter or not images will be appended to current images.
   *
   * @var bool
   */
  public $appendImages = TRUE;

  /**
   * Import product with invalid variations.
   *
   * @var bool
   */
  public $notValidVariations = TRUE;

  /**
   * If this option is FALSE even perfect products will be skipped.
   *
   * @var bool
   */
  public $createProduct = TRUE;

  /**
   * If sku exists TRUE will generate new, FALSE will skip product.
   *
   * @var bool
   */
  public $sku = TRUE;

  /**
   * Disable all parameters.
   */
  public function disableAll() {
    $this->duplicateValues = FALSE;
    $this->incorrectValues = FALSE;
    $this->exceedsCardinality = FALSE;
    $this->defaultValues = FALSE;
    $this->notValidVariations = FALSE;
    $this->createProduct = FALSE;
    $this->sku = FALSE;
  }

  /**
   * Decide to create product or not.
   */
  public function matchParameters($field_logs) {
    if ($this->createProduct === FALSE) {
      return FALSE;
    }
    foreach ($field_logs as $field_log) {
      if ($field_log['required'] === FALSE) {
        return FALSE;
      }
      if ($this->defaultValues === FALSE && $field_log['default_value'] === FALSE) {
        return FALSE;
      }
      if ($this->exceedsCardinality === FALSE && $field_log['cardinality'] === FALSE) {
        return FALSE;
      }
      if ($this->duplicateValues === FALSE && $field_log['duplicates'] === FALSE) {
        return FALSE;
      }
      if ($this->incorrectValues === FALSE && count($field_log['not_valid'])) {
        return FALSE;
      }
      if (is_array($field_log) && array_key_exists('sku', $field_log)) {
        if ($this->sku === FALSE && count($field_log['not_valid'])) {
          return FALSE;
        }
      }
    }
    return TRUE;
  }

  /**
   * Decides if field should be createf based given parameters.
   */
  public function matchOneFieldLog($field_log) {
    if ($field_log['required'] === FALSE) {
      return FALSE;
    }
    if ($this->defaultValues === FALSE && $field_log['default_value'] === FALSE) {
      return FALSE;
    }
    if ($this->exceedsCardinality === FALSE && $field_log['cardinality'] === FALSE) {
      return FALSE;
    }
    if ($this->duplicateValues === FALSE && $field_log['duplicates'] === FALSE) {
      return FALSE;
    }
    if ($this->incorrectValues === FALSE && count($field_log['not_valid'])) {
      return FALSE;
    }
    return TRUE;
  }

}
