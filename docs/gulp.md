---
title: Gulp
tags:
  - gulp
  - node
  - sass
  - js
  - javascript
  - scss
  - frontend
---
# Gulp

This project uses [Gulp][] for frontend tooling. The `gulpfile.js` included with
this project has the following commands:

* `lando gulp build[:js|sass|react]`
* `lando gulp watch[:js|sass|react]`
* `lando gulp validate[:js|sass]`
* `lando gulp fix[:js|sass]`

## Build command

The `build` command compiles all SCSS, React app and JavaScript (ES6) files in the project.
The compiler recursively searches the following directories to compile files:

* `/web/modules/custom`
* `/web/themes/custom`
* `/web/modules/custom/lifespan_react_search/`

**Note:** Files with the extensions `.css` and `.js` will be ignored by git.
Compiled assets should not exist in version control. For the react search app, the
ignored files are located at `/web/modules/custom/lifespan_react_search/dist/js` with
the `.js` file extension.

### SCSS compilation

The `build:sass` command will compile files with the extension `.scss` that do
**not** begin with an underscore (`_`) to `.css` files in the same directory.

#### Example

The file:
`/web/themes/custom/my_theme/assets/styles/main.scss`

Compiles to:
`/web/themes/custom/my_theme/assets/styles/main.css`

### JavaScript compilation

The `build:js` command will compile files with the extension `.es6.js` to `.js`
files in the same directory.

#### Example

The file:
`/web/modules/custom/my_module/assets/scripts/my_module.es6.js`

Compiles to:
`/web/modules/custom/my_module/assets/scripts/my_module.js`

### React compilation

The `build:react` command will compile files with the extension `.js` to minified `.js`
files in the `/web/modules/custom/lifespan_react_search/dist/js` directory.

Gulp serves as a task runner in this case, running a webpack build that can
be found in the `./webpack/config.js` file. Webpack relies on the file to find
and minify files into a production compilation of js files.

## Watch command

The `watch` command will watch SCSS and JavasScript (ES6) files for changes and
then automatically compile those files. This command leverages the `build`
command to compile assets. Like the `build` command, you can specify to watch
either SCSS (`watch:sass`) or JavaScript (`watch:js`) specifically.

## Validate command

The `validate` command runs code linters for SCSS and JavaScript (ES6). It is
recommended to run the `validate` command regularly during development as the
builds in Travis-CI will fail if there is an coding standards violation. Like
the `build` command, you can specify to validate either SCSS (`validate:sass`)
or JavaScript (`validate:js`) specifically.

## Fix command

The `fix` command runs the syntax fixers provided by the code linters used for
validating SCSS and JavaScript (ES6). The command will try its _best_ to fix
coding standard violations based on specific rulesets for each language.

**Note:** Files from the React app in this project are not yet included in the
`fix` and `validate` commands due to the nature of the JSX written for the React
applications included with the custom module.

[Gulp]: https://gulpjs.com/
