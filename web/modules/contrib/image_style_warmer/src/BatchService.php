<?php

namespace Drupal\image_style_warmer;

use Drupal\file\Entity\File;

/**
 * Class BatchService
 *
 * @package Drupal\image_style_warmer
 */
class BatchService {

  /**
   * Batch process callback.
   *
   * @param int $fid
   *   Id of the file.
   * @param object $context
   *   Context for operations.
   */
  public function warmUpFileProcess($fid, $count, &$context) {
    /** @var \Drupal\image_style_warmer\ImageStylesWarmerInterface $image_styles_warmer */
    $image_styles_warmer = \Drupal::service('image_style_warmer.warmer');
    $file = File::load($fid);
    $image_styles_warmer->warmUp($file);
    // Store some results for post-processing in the 'finished' callback.
    // The contents of 'results' will be available as $results in the
    // 'finished' function (in this example, batch_example_finished()).
    $context['results'][] = $fid;
    $i = count($context['results']);
    // Optional message displayed under the progressbar.
    $context['message'] = t('Warming up styles for file @fid (@i/@count)',
      ['@fid' => $fid, '@i' => $i, '@count' => $count]
    );
  }

  /**
   * Batch Finished callback.
   *
   * @param bool $success
   *   Success of the operation.
   * @param array $results
   *   Array of results for post processing.
   * @param array $operations
   *   Array of operations.
   */
  public function warmUpFileFinished($success, array $results, array $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      $messenger->addMessage(t('@count files warmed up.', ['@count' => count($results)]));
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $messenger->addMessage(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }

}
