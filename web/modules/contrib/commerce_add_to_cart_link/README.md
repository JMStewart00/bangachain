Drupal Commerce Add To Cart Link
================================

## Introduction

The Commerce Add To Cart Link module extends Commerce product displays with an
"add to cart" field as link instead of having an add to cart form.

Primary use case is to use this on listings such as overview pages or blocks
displaying related products, bestsellers, etc. Currently, building product
listing blocks is often accompanied by some headache due to existing Drupal
Core limitations described below.

The links can be optionally secured with a token, so that the links cannot be
guessed, hence reduces problems with bots and reduces risks of tricking users
into clicking a link and adding products to cart they don't want.

The link itself is rendered via Twig template to enable full customization
possibilities for themers, enabling to change texts, add additional markup, etc.

The 'Commerce add to wishlist link' sub module offers the same functionality for
Commerce Wishlist.

### Core limitation: disappearing forms

The biggest problem is that Drupal forms won't work when they disappear on the
submitting request, like in the following scenario:

You want to build a "related products" block, showing up to 4 related products.
Let's say, there are 20 possible candidates for a given product, and you want to
choose the 4 products randomly. You may want to set some Cache conditions
though, but even then the 4 shown products are subject to change. So, what
happens, if you use a form to show the add to cart button, and the selected
product is no longer displayed in that request you pressed the "add to cart"
button? Nothing, just nothing happens. No message, no warning, no error, but
also no product in your cart. That's just, how Form API works in Drupal, and
there's little to nothing that Commerce could do to prevent that.

Offering a dedicated add to cart link instead of using the Form API prevents
that problem.

### Core limitation: ajaxified Views are hijacking your forms

Ever tried to build a View with Ajax enabled (eg. for infinite pagination) and
list products including the add to cart form? You'll fail. The forms will only
work on initial page load. After the first Views Ajax link was clicked, you'll
run into 404 errors because Views has "stolen" the forms from Commerce. You can
now either disable Ajax on that view or remove the add to cart form completely
from the teaser view mode and only link to the detail page.

Again, eliminating the form here and replacing it by a link will not break
anything and allows peaceful co-existence of an Ajax enabled View and the cart
button.

See these links for more information on this topic:

* https://www.drupal.org/project/commerce/issues/2916671
* https://www.drupal.org/node/2185239#comment-8431647

## Requirements

The module depends on Drupal Commerce of course, depending on both Product and
Cart sub modules.

## Installation

It is recommended to use [Composer](https://getcomposer.org/) to get this module
with all dependencies, which is actually inevitable for running Commerce itself.

Use [Composer](https://getcomposer.org/) to get Drupal + Commerce with all dependencies.

See the [official dosc](https://www.drupal.org/docs/8/extending-drupal-8/installing-modules-composer-dependencies)
and/or th e[install documentation](https://docs.drupalcommerce.org/commerce2/developer-guide/install-update/installation)
of Drupal commerce for more details.

## Configuration

This module offers two possibilities to include the add to cart link:

1. "add_to_cart_link" pseudo field for Product entities.
2. "add_to_cart_link" pseudo field for Product variation entities.

The first one (Product entity field) is recommended, if you either do not have
multiple variations per product at all or only want to show the add to cart link
for the default (in most cases the first referenced) variation.

Use the second option ()variation field) instead, if you have multiple
variations and want to show links for all of them.

The fields are automatically available for all your product and product
variation bundles, but are hidden by default - you most likely only want to
activate it for single view mode of either product or variation entities. So
this approach is less effort at the end.

To enable the display for the shipped "default" product type of Commerce visit
admin/commerce/config/product-types/default/edit/display and from there
navigate to the desired view mode via the displayed local tabs on that page.
Please note, that Commerce only ships with a "default" view mode for products.
We recommend to use the normal "add to cart" form of Commerce for full display
of products on their detail pages, and only use our link formatters for
catalog views. So, if haven't already defined a dedicated view mode for that
(our suggestion: name it "catalog" or "teaser"), you should define one by
visiting this page: admin/structure/display-modes/view/add/commerce_product.

If you want to customize the markup and/or text of the link, copy the
"commerce-add-to-cart-link.html.twig" file from the "templates" directory of
this module into your theme and customize it like you want. You can provide
different templates per variation type or ID.

### Token protection

Visit /admin/commerce/config/add-to-cart-link to configure, which user roles
should have the add to cart and add to wishlist links protected with a token.
By default, this is disabled for any role. Users with multiple roles will be
protected, as soon as a single of his/her attached roles is enabled for token
protection.

## Maintainers

Commerce Quantity Increments module was originally developed and is currently
maintained by [Mag. Andreas Mayr](https://www.drupal.org/u/agoradesign).

All initial development was sponsored by
[agoraDesign KG](https://www.agoradesign.at).

To submit bug reports and feature suggestions, or to track changes:
  https://drupal.org/project/issues/commerce_add_to_cart_link
