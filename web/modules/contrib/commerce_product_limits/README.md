Commerce Product Limits lets you add minimum or maximum purchase quantities to product variations in Drupal Commerce.

To use this feature, enable the module, edit the product variation types you would like to add limits to, and enable the "Minimum quantity" or "Maximum quantity" trait as need be. Update any product variation that should have a limit applied to it.

This module defines an Availability Checker that Commerce Core's Availability Manager uses to ensure any given order item is "available" to purchase as is. This system is used by other modules to enforce availability dates or stock checks, and this module is using it to determine whether or not it's available for purchase in the given quantity.

Customers will either see a message upon Add to Cart form submission indicating they have not met the minimum or maximum requirements. On the core Shopping Cart form, the quantity fields are altered to use HTML's min / max attributes for basic client-side validation.

* Not compatible with Commerce Cart Flyout.
