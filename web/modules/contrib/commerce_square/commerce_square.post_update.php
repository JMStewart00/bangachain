<?php

/**
 * @file
 * Post update functions for Commerce Square.
 */

use Drupal\Core\Utility\UpdateException;
use SquareConnect\Api\OAuthApi;
use SquareConnect\ApiException;
use SquareConnect\Model\ObtainTokenRequest;

/**
 * Permission scope has changed and OAuth token needs to be regenerated.
 */
function commerce_square_post_update_oauth_token_warning() {
  $messenger = \Drupal::messenger();
  $messenger->addWarning(t('The Square integration requires the ORDERS_WRITE permission to add purchase details for orders sent to Square. You must <a href=":link">reauthorize with Square</a>', [
    // Url generation is odd in the update kernel, see https://www.drupal.org/project/drupal/issues/2956953.
    ':link' => \Drupal\Core\Url::fromRoute('commerce_square.settings', [], ['base_url' => ''])->toString(),
  ]));
}

/**
 * Migrate legacy Square Connect OAuth access token.
 */
function commerce_square_post_update_oauth_token_migration() {
  $messenger = \Drupal::messenger();
  $state = \Drupal::state();
  $connect = \Drupal::getContainer()->get('commerce_square.connect');
  $client = $connect->getClient('production');

  try {
    $oauth_api = new OAuthApi($client);
    $obtain_token_request = new ObtainTokenRequest();
    $obtain_token_request->setClientId($connect->getAppId('production'));
    $obtain_token_request->setClientSecret($connect->getAppSecret());
    $obtain_token_request->setGrantType('migration_token');
    $obtain_token_request->setMigrationToken($connect->getAccessToken('production'));

    $token_response = $oauth_api->obtainToken($obtain_token_request);

    $state->setMultiple([
      'commerce_square.production_access_token' => $token_response->getAccessToken(),
      'commerce_square.production_access_token_expiry' => strtotime($token_response->getExpiresAt()),
      'commerce_square.production_refresh_token' => $token_response->getRefreshToken(),
    ]);
    $messenger->addStatus(t('Commerce Square Connect OAuth access token has been migrated'));
  }
  catch (ApiException $e) {
    $respone_body = $e->getResponseBody();
    if ($respone_body->message !== 'Cannot migrate access token generated by or returned with refresh token') {
      throw new UpdateException($respone_body->message);
    }
  }
}