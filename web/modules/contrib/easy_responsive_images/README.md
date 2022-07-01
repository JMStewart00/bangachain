INTRODUCTION
------------

When using media to add images to content, having media view modes defined by
aspect ratio, combined with a bunch of different image styles for the images in
that specific aspect ratio seems to solve the responsive images problem in a
pretty easy way.

Easy Responsive Images needs a minimum and maximum width in combination with a
preferred amount of pixels between each image style. An optional list of aspect
ratio's can also be defined. When the configuration is saved, the image styles
are automatically generated. For example generating image styles for a 4:3
ratio and 16:9 ratio will produce the following styles:

* responsive_4_3_50w
* responsive_16_9_50w
* responsive_4_3_150w
* responsive_16_9150w
* …
* responsive_4_3_1450w
* responsive_16_9_1450w


USAGE
------------

After generating the desired image styles for the different aspect ratio's,
create a media view mode for each aspect ratio. The Easy Responsive Images
formatter can be used to select an aspect ratio for the image in the media
view mode. When using the media item in other content, render the media item
using the view mode needed for the specific display.


### Twig

The module also provides a Twig filter to create URLs for the images styles
(including an optimized WebP version of the image when using
[ImageAPI Optimize WebP](https://www.drupal.org/project/imageapi_optimize_webp)).

Create a template for your media view mode, eg. `media--image--16-9.html.twig`
for a `16_9` view mode, containing the following code:

```
{#
/**
 * @file
 * Default theme implementation to display an image.
 */
#}
{{ attach_library('easy_responsive_images/resizer') }}

{% set file = media.field_media_image.entity %}
{% set src = file.uri.value|image_url('responsive_16_9_50w') %}
{% set srcset = [
  file.uri.value|image_url('responsive_16_9_150w')|render ~ ' 150w',
  file.uri.value|image_url('responsive_16_9_350w')|render ~ ' 350w',
  file.uri.value|image_url('responsive_16_9_550w')|render ~ ' 550w',
  file.uri.value|image_url('responsive_16_9_950w')|render ~ ' 950w',
  file.uri.value|image_url('responsive_16_9_1250w')|render ~ ' 1250w',
  file.uri.value|image_url('responsive_16_9_1450w')|render ~ ' 1450w',
] %}
<img
  src="{{ src }}"
  data-srcset="{{ srcset|join(',') }}"
  alt="{{ media.field_media_image.alt }}"
  loading="lazy" />
```

Each time media is displayed using the view mode, the JavaScript will check the
available width for the image container, and load the optimized image style.
Also notice the HTML5 "loading" attribute to enable lazy loading of images for
even more optimization.

SUPPORTED MODULES
------------

This module works even better in combination with:
* [Focal Point](https://www.drupal.org/project/focal_point)
* [Image Optimize](https://www.drupal.org/project/imageapi_optimize)
* [Image Optimize Binaries](https://www.drupal.org/project/imageapi_optimize_binaries)
* [ImageAPI Optimize WebP](https://www.drupal.org/project/imageapi_optimize_webp)

It also supports external images via [Imagecache External](https://www.drupal.org/project/imagecache_external).
