<?php

namespace Drupal\Tests\commerce_wishlist\Kernel\Entity;

use Drupal\commerce_wishlist\Entity\Wishlist;
use Drupal\commerce_wishlist\Entity\WishlistItem;
use Drupal\commerce_wishlist\Entity\WishlistType;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce_wishlist\Kernel\WishlistKernelTestBase;

/**
 * Tests the wishlist entity.
 *
 * @coversDefaultClass \Drupal\commerce_wishlist\Entity\Wishlist
 * @group commerce_wishlist
 */
class WishlistTest extends WishlistKernelTestBase {

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp():void {
    parent::setUp();

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);
  }

  /**
   * Tests the wishlist entity and its methods.
   *
   * @covers ::getCode
   * @covers ::setCode
   * @covers ::getName
   * @covers ::setName
   * @covers ::getOwner
   * @covers ::setOwner
   * @covers ::getOwnerId
   * @covers ::setOwnerId
   * @covers ::getShippingProfile
   * @covers ::setShippingProfile
   * @covers ::getItems
   * @covers ::setItems
   * @covers ::hasItems
   * @covers ::addItem
   * @covers ::removeItem
   * @covers ::hasItem
   * @covers ::getCreatedTime
   * @covers ::setCreatedTime
   * @covers ::isPublic
   * @covers ::setPublic
   * @covers ::getKeepPurchasedItems
   * @covers ::setKeepPurchasedItems
   */
  public function testWishlist() {
    $profile = Profile::create([
      'type' => 'customer',
    ]);
    $profile->save();
    $profile = $this->reloadEntity($profile);

    /** @var \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item */
    $wishlist_item = WishlistItem::create([
      'type' => 'commerce_product_variation',
    ]);
    $wishlist_item->save();
    $wishlist_item = $this->reloadEntity($wishlist_item);
    /** @var \Drupal\commerce_wishlist\Entity\WishlistItemInterface $another_wishlist_item */
    $another_wishlist_item = WishlistItem::create([
      'type' => 'commerce_product_variation',
      'quantity' => '2',
    ]);
    $another_wishlist_item->save();
    $another_wishlist_item = $this->reloadEntity($another_wishlist_item);

    $wishlist = Wishlist::create([
      'type' => 'default',
    ]);
    $wishlist->save();

    $this->assertNotEmpty($wishlist->getCode());
    $wishlist->setCode('new_code');
    $this->assertEquals('new_code', $wishlist->getCode());
    $this->assertEquals('/wishlist/new_code', $wishlist->toUrl()->toString());

    $wishlist->setName('My wishlist');
    $this->assertEquals('My wishlist', $wishlist->getName());

    $wishlist->setOwner($this->user);
    $this->assertEquals($this->user, $wishlist->getOwner());
    $this->assertEquals($this->user->id(), $wishlist->getOwnerId());
    $wishlist->setOwnerId(0);
    $this->assertEquals(NULL, $wishlist->getOwner());
    $wishlist->setOwnerId($this->user->id());
    $this->assertEquals($this->user, $wishlist->getOwner());
    $this->assertEquals($this->user->id(), $wishlist->getOwnerId());

    $wishlist->setShippingProfile($profile);
    $this->assertEquals($profile, $wishlist->getShippingProfile());

    $wishlist->setItems([$wishlist_item, $another_wishlist_item]);
    $this->assertEquals([$wishlist_item, $another_wishlist_item], $wishlist->getItems());
    $this->assertTrue($wishlist->hasItems());
    $wishlist->removeItem($another_wishlist_item);
    $this->assertEquals([$wishlist_item], $wishlist->getItems());
    $this->assertTrue($wishlist->hasItem($wishlist_item));
    $this->assertFalse($wishlist->hasItem($another_wishlist_item));
    $wishlist->addItem($another_wishlist_item);
    $this->assertEquals([$wishlist_item, $another_wishlist_item], $wishlist->getItems());
    $this->assertTrue($wishlist->hasItem($another_wishlist_item));

    $wishlist->setCreatedTime(635879700);
    $this->assertEquals(635879700, $wishlist->getCreatedTime());

    $this->assertFalse($wishlist->isPublic());
    $wishlist->setPublic(TRUE);
    $this->assertTrue($wishlist->isPublic());

    $this->assertTrue($wishlist->getKeepPurchasedItems());
    $wishlist->setKeepPurchasedItems(FALSE);
    $this->assertFalse($wishlist->getKeepPurchasedItems());
  }

  /**
   * Tests the default wishlist functionality.
   */
  public function testDefaultWishlist() {
    $wishlist_type = WishlistType::create([
      'id' => 'test_defaults',
      'label' => 'test_defaults',
    ]);
    $wishlist_type->save();

    /** @var \Drupal\commerce_wishlist\Entity\WishlistInterface $wishlist1 */
    $wishlist1 = Wishlist::create([
      'type' => $wishlist_type->id(),
      'uid' => $this->user->id(),
    ]);
    $wishlist1->save();
    // Confirm that the wishlist was set as default.
    $this->assertTrue($wishlist1->isDefault());

    /** @var \Drupal\commerce_wishlist\Entity\WishlistInterface $wishlist2 */
    $wishlist2 = Wishlist::create([
      'type' => $wishlist_type->id(),
      'uid' => $this->user->id(),
    ]);
    $wishlist2->setDefault(TRUE);
    $wishlist2->save();

    // Confirm that setting the second wishlist as default removed the
    // flag from the first wishlist.
    $wishlist2 = $this->reloadEntity($wishlist2);
    $wishlist1 = $this->reloadEntity($wishlist1);
    $this->assertTrue($wishlist2->isDefault());
    $this->assertFalse($wishlist1->isDefault());
  }

}
