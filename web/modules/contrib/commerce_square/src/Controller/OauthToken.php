<?php

namespace Drupal\commerce_square\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a controller for Square access token retrieval via OAuth.
 */
class OauthToken extends ControllerBase {

  /**
   * The csrf token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * Constructs a new OauthToken object.
   *
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token_generator
   *   The CSRF token generator.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(CsrfTokenGenerator $csrf_token_generator, RequestStack $request_stack) {
    $this->csrfToken = $csrf_token_generator;
    $this->currentRequest = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('csrf_token'),
      $container->get('request_stack')
    );
  }

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
    if ($this->csrfToken->validate($this->currentRequest->query->get('state'))) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden('Could not validate state in OAuth validation handshake.');
  }

}
