<?php

namespace Drupal\commerce_pos_customer_display;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

/**
 * Handles pushing pos checkout updates to the customer display.
 *
 * @package Drupal\commerce_pos_customer_display
 */
class DisplayServer implements MessageComponentInterface {

  protected $clients;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->clients = new \SplObjectStorage();
  }

  /**
   * {@inheritdoc}
   */
  public function onOpen(ConnectionInterface $conn) {
    $conn->{"register_id"} = 0;
    $conn->{"type"} = '';

    $this->clients->attach($conn);
  }

  /**
   * {@inheritdoc}
   */
  public function onMessage(ConnectionInterface $from, $msg) {
    $msg = json_decode($msg);
    if (!isset($msg->type)) {
      return;
    }

    foreach ($this->clients as $client) {
      if ($msg->type == 'register' && $from === $client) {
        $client->{"register_id"} = $msg->register_id;
        $client->{"type"} = $msg->display_type;
      }

      // If the registers listed don't pair with us, ignore.
      if ($client->register_id != $msg->register_id) {
        continue;
      }

      if ($client->type == 'customer') {
        if (isset($msg->cashier)) {
          $send_message = [
            'register_id' => $msg->register_id,
            'type' => 'cashier',
            'cashier' => $msg->cashier,
          ];

          $client->send(json_encode($send_message));
        }

        if ($msg->type == 'update') {
          $send_message = [
            'register_id' => $client->register_id,
            'type' => 'display',
            'display_totals' => $msg->display_totals,
            'display_items' => $msg->display_items,
          ];

          $client->send(json_encode($send_message));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onClose(ConnectionInterface $connection) {
    $this->clients->detach($connection);
  }

  /**
   * {@inheritdoc}
   */
  public function onError(ConnectionInterface $connection, \Exception $exception) {
    trigger_error("An error has occurred: {$exception->getMessage()}\n", E_USER_WARNING);

    $connection->close();
  }

}
