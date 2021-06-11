Commerce loyalty points
-----------------------
Let users collect loyalty points on every dollar spent on a purchase.
Redemption can be made in terms of a promotion code, wherein a user can redeem
loyalty points as a discount coupon and get this discount on their next purchase
with the store.


Installation
------------
- Download the module from D.O and place it in the /modules directory,
- or composer install using:
- Enable the module as usual from the 'Extends' page, or via drush


Description
-----------
- After enabling the module, a customer needs to subscribe with the loyalty
  points system
- A customer needs to go to /user/[uid]/edit page and check
  'Subscribe to loyalty points' to participate in the system
- For site customers: go to /user/[uid]/loyalty-points to see loyalty points
  earned for each dollar (currency unit) spent
- For admins: go to /admin/commerce/loyalty-points to view loyalty points
  earned by all the users in the site, lookup available too
- The module creates a field '' for each Product Variation type
- The admin, or content moderator needs to fill out this field to allow
  customers gain points
- On a successful checkout and payment complete, the number of loyalty points
  in the field will be multiplied with the base price of the product
- The result will be added as the Loyalty points of the customer/buyer
- For redeeming loyalty points, there are 3 approaches:
  - Prepare a commerce promotion code with a 'Limit by loyalty points' condition
  - Integration with POS module is proposed

Hooks
-----
- Use `hook_loyalty_points_alter()` to alter loyalty points for operations `add` or `deduct`:

For example, to make loyalty points into whole number -
```
/**
 * Implements hook_loyalty_points_alter().
 */
function mymodule_loyalty_points_alter($operation, Price &$loyalty_points) {

  // Make loyalty points into a whole number.
  if ($operation == 'add') {
    $currency_code = $loyalty_points->getCurrencyCode();
    $number = $loyalty_points->getNumber();
    $number = (string) round($number);
    $loyalty_points = new Price($number, $currency_code);
  }
}
```

- Use `hook_loyalty_points_view_alter()` to alter loyalty points display with keys `table_aggregate` or `pos_aggregate`:

For example, to make loyalty points to display as whole number -
```
/**
 * Implements hook_loyalty_points_alter().
 */
function mymodule_loyalty_points_view_alter(string &$loyalty_points, $key) {

  if ($key == 'table_aggregate') {
    $points = explode('.', $loyalty_points);
    $loyalty_points = $points[0];
  }
}
```
  - `table_aggregate` will be used for displaying on page: `user/[UID]/loyalty-points`
  - `pos_aggregate` will be used within the field widget provided with sub-modules.


TODO
----
- Let the customers redeem and convert their loyalty points into promotion
  code on their own, that can be used for next purchase (like a 50% discount
  coupon for every 450 points)
- Admin can suspend loyalty points program temporarily -- this should keep all the
  data safe and restrict any further loyalty points from creating


Uninstall process
-----------------
- Delete the Loyalty points entities, either manually or from admin/modules/uninstall
- Uninstall


Caution
-------
- The field storage for 'field_loyalty_points' will be deleted on uninstall
- Role 'Loyalty point subscriber' will be deleted automatically on uninstall << TODO


Module maintainer
-----------------
gauravjeet (https://www.drupal.org/u/gauravjeet) @ Acro Media Inc.
