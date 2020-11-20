Sitewide Alert
---------------

The Sitewide Alert module provides you with the ability to show alerts at the top of your site.

This module can be used for showing alerts to your visitors to inform them of planned maintenance periods, shipping 
delays, flash sales, and more. How you use it is up to you.

Features / Design Decisions
---------------------------

- Alerts are rendered at the top of the site regardless of theme used (no block configuration).
- Multiple alerts can be displayed at once.
- Alerts can have different styles. The number and type of styles are configurable. For example, you can create a very 
important alert that is red and a subtle one that is white.
- Alerts can be (optionally) dismissed by visitors so they are not notified again.
- Alerts can be displayed on all pages (including administrative pages) or can be limited to a subset of pages.
- Alerts are loaded and displayed using a React component that will display new alerts immediately, even if a visitor 
is already on the page.
- Alerts were designed to avoid breaking your full page and dynamic caches.
- Alerts can be scheduled to show and hide at specific times.

Installation
---------------------------

We recommend installing the Sitewide Alert module via Composer. See [Installing Drupal 8 Modules](https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules).

Configuration
---------------------------

Once installed Sitewide Alert is ready to use. However some customization is likely necessary.

Upon installation there is only one Alert Style (Default). This can be extended by navigating to /admin/config/sitewide_alerts 
and defining new Alert Style types. Define each key|value pair on its own line.

To show Sitewide Alerts on administrative pages navigate to /admin/config/sitewide_alerts and select the 
"Show on Administrative Pages" checkbox.

Generating a Sitewide Alert
---------------------------

Navigate to /admin/content/sitewide_alert and click the "Add New Sitewide Alert" button and complete the following fields:

* Name
    * Give your alert an administrative name.
* Alert Style
    * Select a style for your alert.
* Alert Message
    * Compose your alert message. Full WYSIWYG support available.
* Dismissible
    * Select this checkbox if alert should be dismissible by visitors.
* Schedule Alert
    * Select this checkbox if alert should only appear during a specific time period.
* PAGE VISIBILITY
   * Limit by Page
       * Select this checkbox if alert should only appear on specific pages. Use the "Pages" input field to specify 
       pages by using their paths.

Styling Sidewide Alerts
---------------------------

Sitewide Alerts receive a wrapping class that can be used to apply specific styles. For each entry added to the Alert 
Style field a corresponding HTML class is added to the element's wrapper. The class is created by concatenating "alert-"]
with each key defined under Alert Style. Use these classes to apply different styles to each type of alert needed.


Troubleshooting
---------------------------

### Scheduled alerts not showing/disappearing when they should.

- Make sure the scheduled sitewide alert is marked as "Active". Scheduled alerts still need to be set as active to be
shown.
- If using the [Redis module](https://www.drupal.org/project/redis), you may need to apply
[this patch](https://www.drupal.org/project/redis/issues/2877893#comment-12082921) to allow Drupal's Page Cache to
actually expire when it should.
