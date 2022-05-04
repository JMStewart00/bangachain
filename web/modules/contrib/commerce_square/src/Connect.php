<?php

namespace Drupal\commerce_square;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Square\SquareClient;
use Square\Environment;

/**
 * Represents the Connect application for Square.
 */
class Connect {

  /**
   * The application settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new Connect object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state) {
    $this->settings = $config_factory->get('commerce_square.settings');
    $this->state = $state;
  }

  /**
   * Gets the application name.
   *
   * @return string
   *   The application name.
   */
  public function getAppName() {
    return $this->settings->get('app_name');
  }

  /**
   * Gets the application secret.
   *
   * @return string
   *   The secret.
   */
  public function getAppSecret() {
    return $this->settings->get('app_secret');
  }

  /**
   * Gets the application ID.
   *
   * @param string $mode
   *   The mode.
   *
   * @return string
   *   The application ID.
   */
  public function getAppId(string $mode) {
    if ($mode == Environment::PRODUCTION) {
      return $this->settings->get('production_app_id');
    }

    return $this->settings->get('sandbox_app_id');
  }

  /**
   * Gets the access token.
   *
   * @param string $mode
   *   The mode.
   *
   * @return string
   *   The access token.
   */
  public function getAccessToken(string $mode) {
    if ($mode == Environment::PRODUCTION) {
      return $this->state->get('commerce_square.production_access_token');
    }

    return $this->settings->get('sandbox_access_token');
  }

  /**
   * Gets the refresh token.
   *
   * @param string $mode
   *   The mode.
   *
   * @return string
   *   The refresh token.
   */
  public function getRefreshToken(string $mode) {
    if ($mode === Environment::PRODUCTION) {
      return $this->state->get('commerce_square.production_refresh_token');
    }

    return '';
  }

  /**
   * Gets the access token expiration timestamp.
   *
   * @param string $mode
   *   The mode.
   *
   * @return int
   *   The expiration timestamp. Or -1 if sandbox.
   */
  public function getAccessTokenExpiration(string $mode) {
    if ($mode == Environment::PRODUCTION) {
      return $this->state->get('commerce_square.production_access_token_expiry');
    }

    return -1;
  }

  /**
   * Gets a Square API client.
   *
   * @param string $mode
   *   The mode.
   *
   * @return \Square\SquareClient
   *   A configured API client for the Connect application.
   */
  public function getClient(string $mode) {
    return new SquareClient([
      'accessToken' => $this->getAccessToken($mode),
      'environment' => $mode,
    ]);
  }

}
