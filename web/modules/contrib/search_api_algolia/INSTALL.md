INSTALLING MODULES AND LIBRARIES
--------------------------------
 * Download the module and all of its dependencies. Move them to the contributed
   modules location in your tree (e.g. /modules/ or /modules/contrib/).

 * Install Algolia search client PHP library from
   https://github.com/algolia/algoliasearch-client-php via command line
   `composer require algolia/algoliasearch-client-php:^3.0`

 * Enable the module's dependencies on the modules admin page url (/admin/modules).


CONFIGURING SEARCH API SERVER AND INDEX
---------------------------------------

 * On the Search API administration page (/admin/config/search/search-api), add
   a new server, select backend "Algolia", and check the "Enabled" checkbox.
   In the "Configure Algolia backend" section, fill in your Application ID and
   API key found in your Algolia dashboard on https://www.algolia.com.

 * On the Search API administration page (/admin/config/search/search_api), add
   a new index, enable it and select the server you just created in the previous
   section. The index name provided will be used to create a new index on the
   Algolia platform. Please read the "Known problems" section of the README.txt
   file for the latest updates about the type of entity supported.

 * On the "Fields" tab of your index
   (/admin/config/search/search_api/index/[YOUR INDEX NAME]/fields), check all
   the fields you want to have indexed in the Algolia index. At the very least,
   the entity ID field for the indexed entity type needs to be checked off (nid
   for nodes, uid for users, etc.). Please read the "Known problems" section of
   the README.txt file for the latest updates about the type of fields
   supported.

 * On the "Processors" of your index
   (/admin/config/search/search_api/index/[YOUR INDEX NAME]/processors),
   select the processors you want to apply before the data is being sent to
   Algolia's servers. A good starting combination could be "Entity status"
   (allowing you to Exclude unpublished content, unpublished comments and
   inactive users from being indexed). Please read the "Known problems" section
   of the README.txt file for the latest updates about the type of processor
   supported.

# INDEXING CONTENT
* On your newly created index page, (/admin/config/search/search_api/index/[YOUR
  INDEX NAME]), click the "Index now" button. You should start seeing content
  populating the index in the Algolia dashboard on https://www.algolia.com.

# MULTILINGUAL CONFIGURATION
* When using multi-lingual Drupal, we can create indexes with language code
  suffix. For instance if we have English and French, we can have indexes
  like PREFIX_en and PREFIX_fr where PREFIX could be anything like your
  environment - prod/stage/etc. This is recommended approach by Algolia team to
  allow having different rules, synonyms, etc. per language.
* To use this approach enable it in Index options
  (/admin/config/search/search_api/index/[YOUR INDEX NAME]/edit)
  Select "Yes" for the "Apply language suffix".

# FACETs using Drupal Views
* For facets, Algolia has separate setting. Administrators need to ensure facets
  configured in Drupal and in Algolia are the same.

# SORTING and AUTOCOMPLETE using Drupal Views
* Indexed content can be viewed using Search API Views in Drupal. There are some
  naming conventions to follow to support sorting and autocomplete.

* For autocomplete, we need to use Query Suggestions feature of Algolia. We need
  to have query indexes like INDEX_NAME_query

* For sorting, Algolia uses replicas. To allow exposed sorting, we need to
  create replica for each exposed sort option. For this we need to follow the
  format PREFIX_LANGCODE_fieldname_direction. Here fieldname is internal keyword
  and direction is asc/desc.
