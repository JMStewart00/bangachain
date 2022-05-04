CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Integer to Decimal module allows site builders to update node fields of type
integer with any data associated with them to type decimal.

This module only supports field types associated with entities of type node
only.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/integer_to_decimal

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/integer_to_decimal


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Integer to Decimal module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Structure and select the manage fields
       operation of the desired content type
    3. Select the edit operation for the desired integer field with data
       associated to it.
    4. Select the 'Field Settings' tab for the selected field. (The option to
       convert from integer to decimal is only available if the field selected
       has data associated with it).
    5. Check the checkbox (Enable integer to decimal conversion).
    6. From the available drop downs select the desired precision and scale.
    7. Save the field settings.


MAINTAINERS
-----------

Supporting organization:

 * John Snow, Inc. (JSI) - https://www.drupal.org/john-snow-inc-jsi
