<?php

namespace Drupal\commerce_pos;

/**
 * Manage recent cashiers.
 */
class RecentCashiers {

  /**
   * A list of cashiers, each an array with name and timestamp keys.
   *
   * @var array
   */
  protected $cashiers = [];

  /**
   * True if the cashier list is sorted.
   *
   * @var bool
   */
  protected $sorted = FALSE;

  /**
   * The name of the cookie recent cashiers are saved in.
   *
   * @var string
   */
  protected $cookieName = 'commerce_pos_cashiers';

  /**
   * The number of seconds for the cookie to live.
   *
   * @var int
   */
  protected $cookieDuration = 31557600;

  /**
   * Create the recent cashiers list from a cookie.
   */
  public function __construct() {
    if (isset($_COOKIE[$this->cookieName])) {
      $cashiers = json_decode($_COOKIE[$this->cookieName], TRUE);
      if ($this->validate($cashiers)) {
        $this->cashiers = $cashiers;
      }
    }
  }

  /**
   * Add another user to the recent cashiers list.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $user
   *   The account to add. Defaults to the current user.
   */
  public function add($user = NULL) {
    if (!$user) {
      $user = \Drupal::currentUser();
    }
    $this->cashiers[$user->id()] = [
      'name' => $user->getAccountName(),
      'timestamp' => time(),
    ];

    $this->sorted = FALSE;
    $this->setCookie();
  }

  /**
   * Get the cashier list.
   *
   * @return array
   *   A list of cashiers keyed by user id.
   *
   *   Each cashier is an array with name and timestamp keys.
   */
  public function get() {
    if (!$this->sorted) {
      $this->sort();
    }

    // Only remember the last 10 cashiers.
    if (count($this->cashiers) > 10) {
      $this->cashiers = array_slice($this->cashiers, 0, 10);
    }

    return $this->cashiers;
  }

  /**
   * Determine if cashier data is valid.
   *
   * @param mixed $cashiers
   *   Cashier data from user input.
   *
   * @return bool
   *   TRUE if the data meets the required format.
   */
  protected function validate($cashiers) {
    if (!is_array($cashiers)) {
      return FALSE;
    }

    foreach ($cashiers as $cashier) {
      if (!is_array($cashier)) {
        return FALSE;
      }

      if (empty($cashier['name']) || empty($cashier['timestamp'])) {
        return FALSE;
      }

      if (!is_string($cashier['name'])) {
        return FALSE;
      }

      if (!ctype_digit($cashier['timestamp'])) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Sort the cashier records.
   */
  protected function sort() {
    usort($this->cashiers, [$this, 'compare']);
    $this->sorted = TRUE;
  }

  /**
   * Compare cashier records to see which is more recent.
   */
  protected function compare(array $a, array $b) {
    return $a['timestamp'] - $b['timestamp'];
  }

  /**
   * Set the cashier list cookie.
   */
  protected function setCookie() {
    setcookie($this->cookieName, json_encode($this->cashiers),
      time() + $this->cookieDuration, '/');
  }

}
