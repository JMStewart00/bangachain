
// The streaming build system.
var gulp = require('gulp');

// Define gulp-load-plugins.
var $ = require('gulp-load-plugins')();

// Gulp plugin for sass.
var sass = require('gulp-sass');

// Prefix CSS.
// var autoprefixer = require('gulp-autoprefixer');

// Prevent pipe breaking caused by errors from gulp plugins.
var plumber = require('gulp-plumber');

// Live CSS Reload & Browser Syncing.
var browserSync = require('browser-sync').create();

// Rename files
// var rename = require('gulp-rename');

// Allows you to use glob syntax in imports (i.e. @import "dir/*.sass"). Use as a custom importer for node-sass.
var importer = require('node-sass-globbing');

// Default settings.
var src = {
  root: '.',
  scss: './scss/*.scss',
  css: './css',
  watch: './scss/**',
  proxy: 'estore.loc'
};

// For developers. Contain better outputStyle for reading.
gulp.task('default', function () {
  'use strict';

  gulp.src(src.scss)
    .pipe(plumber())
    .pipe(sass({
      importer: importer,
      includePaths: [
        './node_modules/breakpoint-sass/stylesheets/',
        './node_modules/compass-mixins/lib/'
      ],
      outputStyle: 'expanded',
      sourceComments: false,
      indentWidth: 2
    }).on('error', sass.logError))
    .pipe($.csscomb())
    .pipe(gulp.dest(src.css))
    .pipe(browserSync.stream());
});


// Watch task.
gulp.task('watch', function () {
  'use strict';

  gulp.watch(src.watch, ['default']);
});
