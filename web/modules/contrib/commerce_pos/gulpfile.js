/**
 * @file
 */

'use strict';

var gulp = require('gulp');
var sass = require('gulp-sass');
var postcss = require('gulp-postcss');
var autoprefixer = require('autoprefixer');
var sourcemaps = require('gulp-sourcemaps');
var del = require('del');

var paths = {
  'styles': [
    { src: './modules/label/sass/labels/*.scss', dest: './modules/label/css/labels'},
    { src: './modules/label/sass/**/*.scss', dest: './modules/label/css'},
    { src: './modules/keypad/sass/**/*.scss', dest: './modules/keypad/css'},
    { src: './modules/reports/sass/**/*.scss', dest: './modules/reports/css'},
    { src: './modules/receipt/sass/**/*.scss', dest: './modules/receipt/css'},
    { src: './modules/barcode_scanning/sass/**/*.scss', dest: './modules/barcode_scanning/css'},
    { src: './modules/customer_display/sass/**/*.scss', dest: './modules/customer_display/css'},
    { src: './sass/**/*.scss', dest: './css' }
  ]
};

function clean() {
  return del([' assets' ]);
}

function styles() {
    var results = true;

    paths.styles.map(function (dirInfo) {
        var result = gulp.src(dirInfo.src)
            .pipe(sourcemaps.init())
            .pipe(sass({ outputStyle: 'compressed' }).on('error', sass.logError))
            .pipe(postcss([
                autoprefixer({
                    browsers: ['> 5%']
                }),
            ]))
            .pipe(sourcemaps.write('./'))
            .pipe(gulp.dest(dirInfo.dest));

        if(result !== true) {
            results = result;
        }
    });

    return results;
}

function build() {
  gulp.series(clean, styles);
}

function watch() {
    paths.styles.map(function (dirInfo) {
        gulp.watch(dirInfo.src, styles);
    });
}

exports.clean = clean;
exports.styles = styles;
exports.build = build;
exports.watch = watch;

gulp.task('default', build);
