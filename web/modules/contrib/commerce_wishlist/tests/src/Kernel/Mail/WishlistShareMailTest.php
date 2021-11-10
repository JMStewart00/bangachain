<?php

namespace Drupal\Tests\commerce_wishlist\Kernel\Mail;

use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_wishlist\Entity\Wishlist;
use Drupal\commerce_wishlist\Entity\WishlistItem;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\Tests\commerce_wishlist\Kernel\WishlistKernelTestBase;

/**
 * Tests the sending of wishlist share emails.
 *
 * @coversDefaultClass \Drupal\commerce_wishlist\Mail\WishlistShareMail
 * @group commerce_wishlist
 */
class WishlistShareMailTest extends WishlistKernelTestBase {

  use AssertMailTrait;

  /**
   * The wishlist share mail.
   *
   * @var \Drupal\commerce_wishlist\Mail\WishlistShareMail
   */
  protected $mail;

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The wishlist.
   *
   * @var \Drupal\commerce_wishlist\Entity\WishlistInterface
   */
  protected $wishlist;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->config('system.site')
      ->set('name', 'Drupal')
      ->save();

    $this->user = $this->createUser(['mail' => 'customer@example.com']);
    $this->mail = $this->container->get('commerce_wishlist.wishlist_share_mail');

    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => 9.99,
        'currency_code' => 'USD',
      ],
    ]);
    $variation->save();

    $wishlist_item = WishlistItem::create([
      'type' => 'commerce_product_variation',
      'purchasable_entity' => $variation,
      'quantity' => 1,
    ]);
    $wishlist_item->save();
    $wishlist_item = $this->reloadEntity($wishlist_item);

    $this->wishlist = Wishlist::create([
      'type' => 'default',
      'title' => 'My wishlist',
      'uid' => $this->user->id(),
      'wishlist_items' => [$wishlist_item],
    ]);
    $this->wishlist->save();
    $this->wishlist = $this->reloadEntity($this->wishlist);
  }

  /**
   * @covers ::send
   */
  public function testSend() {
    $this->mail->send($this->wishlist, 'test-recipient@example.com');

    $emails = $this->getMails();
    $this->assertEquals(1, count($emails));
    $email = reset($emails);
    $this->assertEquals('text/html; charset=UTF-8;', $email['headers']['Content-Type']);
    $this->assertEquals('commerce_wishlist_share', $email['id']);
    $this->assertEquals('test-recipient@example.com', $email['to']);
    $this->assertFalse(isset($email['headers']['Bcc']));
    $this->assertEquals($this->wishlist->getOwner()->getEmail(), $email['from']);
    $this->assertEquals('Check out my Drupal wishlist', $email['subject']);
    $wishlist_url = $this->wishlist->toUrl('canonical', ['absolute' => TRUE]);
    $this->assertContains($wishlist_url->toString(), $email['body']);
    $this->assertContains('Thanks for having a look!', $email['body']);
  }

}
