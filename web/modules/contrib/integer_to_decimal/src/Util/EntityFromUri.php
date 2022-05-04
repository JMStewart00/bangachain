<?php

namespace Drupal\integer_to_decimal\Util;

/**
 * A utility class that extracts the content type from the url
 *
 * @package Drupal\field_updater\Util
 */
class EntityFromUri
{
  /**
   * @return string id of the bundle or content type
   */
    public static function currentUriEntity()
    {
        $currentUri = \Drupal::request()->getRequestUri();
        return explode('/', $currentUri)[5];
    }
  /**
   * @return string field name being updated from integer to decimal
   */
    public static function currentUriField(){
      $currentUri = \Drupal::request()->getRequestUri();

      return explode('.', explode('/', $currentUri)[7])[2];
  }
}