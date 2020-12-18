<?php

namespace Drupal\commerce_add_to_cart_link;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\PrivateKey;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;

/**
 * Default cart link token service implementation.
 */
class CartLinkToken implements CartLinkTokenInterface {

  /**
   * The module configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The Drupal private key.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $privateKey;

  /**
   * Constructs a new CartLinkToken object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\PrivateKey $private_key
   *   The Drupal private key.
   */
  public function __construct(AccountInterface $current_user, ConfigFactoryInterface $config_factory, PrivateKey $private_key) {
    $this->config = $config_factory->get('commerce_add_to_cart_link.settings');
    $this->currentUser = $current_user;
    $this->privateKey = $private_key;
  }

  /**
   * {@inheritdoc}
   */
  public function generate(ProductVariationInterface $variation) {
    if (!$this->needsCsrfProtection($this->currentUser)) {
      return '';
    }
    $value = $this->generateTokenValue($variation);
    return substr(Crypt::hmacBase64($value, $this->privateKey->get() . $this->getHashSalt()), 0, 16);
  }

  /**
   * {@inheritdoc}
   */
  public function validate(ProductVariationInterface $variation, string $token) {
    if (!$this->needsCsrfProtection($this->currentUser)) {
      return TRUE;
    }
    $value = $this->generate($variation);
    return hash_equals($value, $token);
  }

  /**
   * {@inheritdoc}
   */
  public function needsCsrfProtection(AccountInterface $account = NULL) {
    if (is_null($account)) {
      $account = $this->currentUser;
    }
    $csrf_protected_roles = $this->config->get('csrf_token.roles');
    if (empty($csrf_protected_roles)) {
      return FALSE;
    }
    return !empty(array_intersect($csrf_protected_roles, $account->getRoles()));
  }

  /**
   * Generates the value used for the token generation.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The product variation.
   *
   * @return string
   *   The value used for the token generation.
   */
  protected function generateTokenValue(ProductVariationInterface $variation) {
    return sprintf('cart_link:%s:%s', $variation->getProductId(), $variation->id());
  }

  /**
   * Gets a salt useful for hardening against SQL injection.
   *
   * @return string
   *   A salt based on information in settings.php, not in the database.
   *
   * @throws \RuntimeException
   */
  protected function getHashSalt() {
    return Settings::getHashSalt();
  }

}
