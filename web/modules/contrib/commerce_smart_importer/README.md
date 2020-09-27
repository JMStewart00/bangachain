CONTENTS OF THIS FILE
---------------------

 * Introduction and Features
 * CSV format
 * CSV Import
 * CSV Export
 * CSV Update
 * Maintainers
 * Plans for future
 
INTRODUCTION AND FEATURES
------------
Commerce Smart Importer is created primarily for massive import of products because it’s almost impossible to import a massive amount manually.

To most users it’s much simpler to edit products in table format because they already have data like this from ERP software.

The module has the ability to add products from a CSV file. A template CSV can be downloaded directly from the module, and then users only need to populate the data.

If some mistakes are made when populating data, Commerce Smart Importer will recognize them and will try to correct them.

When some mistakes are recognized you don't need to change the values in the CSV file, you can change them directly in Commerce Smart Importer.

All products that are valid will be created in Drupal Commerce. Biggest advantage of this importer is that it will work on any Drupal Commerce website. All fields will be automatically recognized from the Drupal configuration.

The module also has the ability to export products in a CSV file based on selected categories and fields.

It is also possible to update existing products, which is useful for massive updates of certain fields.

Big benefit is that beside textual fields, you can import and update even images!

CSV FORMAT
----------
 * Before we jump into exporter, updater and importer, first we need to clear how you should format your CSV file.
 * Values you want in each field should be in same position(column) like in header.
 * If your field allows more than one value on field you shuld delimit values with | ex. value1|value2|value3. This applies to all fields.
 * When you download template first labels will be from product, and after it will be variation fields leading with SKU
 * It is possible to add more than one variation to one product, all you have to do is to leave product fields empty, and variation will be added to last product populated.
 * Always leave second row empty or populate it with some junk text because it will be ignored.
 
CSV IMPORT
----------
 * First download your CSV template.
 * Than populate that CSV file in format explained in this readme. 
 * To upload images first you need to write full image name(with extension) to image field. And when you are uploading CSV file upload those image also. Commerce Smart Importer will try to find them based on names you have entered. You can also just enter link to images.
 * Second step is to read Notices and Errors carefully.
 * Difference between notice and error is that notice will notify about certain action that Commerce Smart Importer will do. While error will affect wheter or not product will be created and there is nothing Commerce Smart importer can do about it, so Importer product will just skip this product or variation.
 * If you are not happy with action that will be taken, you can always override that value.
 * Check options that you want to ignore and proceed.
 
CSV EXPORT
----------
 * This is simplest part of importer. 
 * If you have some field that references taxonomy term you can export only products that are referenced with chosen term. 
 * Check all fields you want to export. You need at least one Identifier.
 * After export finishes, click download last export.
 
CSV UPDATE
----------
 * This is very similar to importing part.
 * In CSV you need to have at least one identifier so Commerce Smart Importer can identify which product you are trying to update
 * Product have only one identifier ID(product) and variation have 2 identifiers ID(variation) and SKU, you need at least one for each of these entities. Products can be identified by sku, but it is not recomended because of obvious reasons.
 * It is recomended to use file from export, you can delete products that you don't want to update, or you can just leave them.
 * For now there is no override function
 
 
PLANS FOR FUTURE
---------------- 
 * I am not  not sure if this will be added and when, but i have it in plan, also if you have some suggestion on how we can improve this Commerce Smart Importer, contact me on drupal.org or mail address given in maintainers.
 * If you can help me with coding, you can post patch in some issues and I will consider merging. In case you have something bigger in mind contact me first.
 * Plans:
   1. Saving import, and later loading it.
   2. Excel support.
   3. Importing any entity
   4. Hook(event subscriber) for custom field formatter.
 
MAINTAINERS
-----------
 > Davor Horvacki (DavorHorvacki) - https://www.drupal.org/u/davorhorvacki 
 > davor@studiopresent.com
