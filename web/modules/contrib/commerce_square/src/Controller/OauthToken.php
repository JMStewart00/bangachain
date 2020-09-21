<?php

namespace Drupal\commerce_square\Controller;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a controller for Square access token retrieval via OAuth.
 */
class OauthToken extends ControllerBase {

  /**
   * Provides a route for square to redirect to when obtaining the oauth token.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The response.
   */
  public function obtain(Request $request) {
    $code = $request->query->get('code');
    $options = [
      'query' => [
        'code' => $code,
      ],
    ];
    return new RedirectResponse(Url::fromRoute('commerce_square.settings', [], $options)->toString());
  }

  /**
   * Controller access method.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function obtainAccess() {
    // $request is not passed in to _custom_access.
    // @see https://www.drupal.org/node/2786941
    if (\Drupal::csrfToken()->validate(\Drupal::request()->query->get('state'))) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden('Could not validate state in OAuth validation handshake.');
  }

}
