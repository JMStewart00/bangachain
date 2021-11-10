# Commerce Wishlist 8.x-3.x

Provides the ability for customers to add products to a list other than a cart.

## Data model

Architecturally, the third version of Commerce Wishlist for Drupal 7 was
converted to using Orders as the entity that is called a "Wishlist." Drupal 8 to
the contrary, offers a far more powerful Entity API, making it cheap and
comfortable to build custom entity types, with the benefit of having dedicated
classes and interfaces with dedicated functions. So we now have dedicated
Wishlist and Wishlist Item entities.

The data model and implementation of those entities however is still very close
to Commere Order (with reduced complexity of not needing to have adjustments,
totals, etc). Also the services responsible for providing and managing wishlists
are very similar to the ones that Commerce Cart has. So, if you are already
familiar with Commerce Order and Cart, you'll find yourself comfortable working
with Wishlist as well.

The same way, you are able to define your own bundles of Orders and Order Items,
or add your custom fields to them, you are also able to customize Wishlist and
Wishlist Item entities. This would allow for wishlist notes, links to external
products, prioritization, anything you can imagine saving next to a desired
product. **Note**: While fields may be added to order items, the ability for the
customers to create/modify those fields is likely not available quite yet.

### User Stories

**IMPORTANT NOTE**

The user stories below are currently not verified to exist as written. These are
still unchanged since alpha1, before the module was undergoing a full rewrite
until alpha2. But they still build a great base for defining our final road map
and the features and use cases we'll want to support.

> As a _(type of user)_, I can _(some goal)_ so that _(some reason)_.

* **Customers**
  * As a **customer**, I can add a product to a wishlist.
  * As a **customer**, I can remove a product from a wishlist.
  * As a **customer**, I can change the quantity of a product on a wishlist.
  * As a **customer**, I can add a product to my cart from my wislist.
  * As a **customer**, I can move a product from my cart to my wishlist.
  * As a **customer**, I can view my wishlist.
    * A wishlist MAY be accessed by clicking a link from the user's account
      page.
* **Administrators**
  * As an **administrator**, I can control the position of the "Add to wishlist"
      button.
    * The wishlist settings page MUST have a configurable weight integer.
    * The weight integer MUST be used _globally_ to position the button above or
        below other form elements.
  * As an **administrator**, I can choose to display the "Add to wishlist" as a
      button or an AJAX link.
    * The button MUST reload the page by default.
    * The AJAX link MUST accomplish the same task without a page reload.
  * As an **administrator**, I can add a "Move to wishlist" button to the cart
      view. 

## Backlogged User Stories

* **Anonymous Users**
  * As an **anonymous user**, I can add a product to a wishlist.
  * As an **anonymous user**, I can remove a product from a wishlist.
  * As an **anonymous user**, I can change the quantity of a product on a
      wishlist.
  * As an **anonymous user**, I can add a product to my cart from my wislist.
  * As an **anonymous user**, I can move items from my cart to my wishlist.
  * As an **anonymous user**, I can register and my wishlist will be saved to my
      account.
  * As an **anonymous user**, I can login and my wishlist will be saved to my
      account.
* **Customers**
  * As a **customer**, I can add or delete information stored in order items on
      my wishlist.
  * As a **customer**, I can view a list of my wishlists by accessing a menu
      link.
  * As a **customer**, I can create, update, and delete wishlists.
    * A customer MUST have a "default" wishlist.
    * A wishlist MUST be created if none exists when adding the first product to
        a wishlist.
    * A customer MAY provide a wishlist title.
  * As a **customer**, I can view a list of wishlists on my account page.
  * As a **customer**, I can select one of many wishlists before I click
      "Add to wishlist."
  * As a **customer**, I can create a new wishlist while saving a product to it.
  * As a **customer**, I can move products between wishlists.
  * As a **customer**, I can share a wishlist.
    * Sharing MAY be accessed with an unlisted url once a shared status has been
        set.
    * Limited sharing with a passcode MAY be enabled.
  * As a **customer**, I will see products from my wishlist(s) in a block
      underneath the cart.
* **Administrators**
  * As an **administrator**, I can mark order item fields to be included as
      customer-editable on wishlists.
  * As an **administrator**, I can choose which order types are treated as
      wishlists.
  * As an **administrator**, I can add a link to the menu system that links to a
      customer's wishlist.
    * The link MUST be used as a menu link token in the form of
        `<wishlist-dashboard>` 
    * The link MUST disappear if the user is not allowed to have a wishlist.
