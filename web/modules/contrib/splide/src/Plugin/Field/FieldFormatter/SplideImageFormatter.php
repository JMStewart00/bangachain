<?php

namespace Drupal\splide\Plugin\Field\FieldFormatter;

/**
 * Plugin implementation of the 'Splide Image' formatter.
 *
 * @FieldFormatter(
 *   id = "splide_image",
 *   label = @Translation("Splide Image"),
 *   description = @Translation("Display the images as a Splide slider."),
 *   field_types = {"image"},
 *   quickedit = {"editor" = "disabled"}
 * )
 */
class SplideImageFormatter extends SplideFileFormatterBase {}
