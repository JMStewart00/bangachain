---
title: Configuration Management
tags:
  - config
  - drupal
  - drush
  - cex
  - cim
---
# Configuration Management

This project uses the core Configuration Management utilities included with
Drupal 8 in conjunction with the [`config_split`][] module to provide
environment specific configuration.

Configuration files are located in the `/config` directory. The `/config/common`
directory is the configuration files that are common to all environments.

## Drush

It is recommended to use [Drush][] for importing and exporting configuration in
Drupal.

## Exporting configuration

Configuration is exported using the `config-export` Drush command, e.g.

Any changes made to settings in the site will need to be exported and then
committed to code.

```bash
lando drush config-export
```

This will export any active configuration to the file system in the `config`
directories. If a piece of configuration is unique to the active configuration
split, it will end up in that configuration splits directory instead of the
common directory.

## Importing configuration

Configuration is imported using the `config-import` Drush command, e.g.

```bash
lando drush config-import
```

This will import all of the common configuration, and then import any
configuration files that are unique to the active configuration split.

[`config_split`]: https://www.drupal.org/project/config_split
