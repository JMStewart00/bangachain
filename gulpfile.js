const autoprefixer = require('gulp-autoprefixer');
const babel = require('gulp-babel');
const eslint = require('gulp-eslint');
const gulp = require('gulp');
const rename = require('gulp-rename');
const sass = require('gulp-sass');
const stylelint = require('gulp-stylelint');
const sassGlob = require('gulp-sass-glob');

const webpack = require('webpack')
const webpackConfig = require('./webpack.config.js');

// Directories to search SCSS files to compile. By default, node-sass does not
// compile files that begin with _.
const scssFilePaths = [
  "web/modules/custom/**/*.scss",
  "web/themes/custom/**/*.scss",
];

// Directories to search ES6 JavaScript files to compile. Files will be compiled
// to a .js file extension.
const javascriptFilePaths = [
  "web/modules/custom/**/*.es6.js",
  "web/themes/custom/**/*.es6.js",
];

const reactFilePaths = [
  "web/modules/custom/react_search/js/**/*.js",
];

gulp.task('build:js', () => {
  return gulp
    .src(javascriptFilePaths)
    .pipe(babel({
      presets: ['@babel/env']
    }))
    .pipe(rename((path) => {
      path.basename = path.basename.replace('.es6', '');
    }))
    .pipe(gulp.dest((file) => {
      return file.base;
    }));
})

gulp.task('build:sass', () => {
  return gulp
    .src(scssFilePaths)
    .pipe(sassGlob())
    .pipe(sass({
      includePaths: [
        "node_modules",
        "web/libraries",
      ]
    }))
    .pipe(autoprefixer({
      browsers: ['last 2 versions'],
      cascade: false,
      grid: true
    }))
    .pipe(sass().on('error', sass.logError))
    .pipe(gulp.dest((file) => {
      return file.base;
    }));
})

gulp.task('build:react', () => {
  return buildReact();
});

gulp.task('watchJS', () => {
  return gulp.watch(javascriptFilePaths, ['build:js']);
});
gulp.task('watchSass', () => {
  return gulp.watch(scssFilePaths, ['build:sass']);
});
gulp.task('watchReact', () => {
  return gulp.watch(reactFilePaths, ['build:react']);
});

gulp.task('validateSass', () => {
  return gulp
    .src(scssFilePaths)
    .pipe(stylelint({
      reporters: [
        {
          formatter: 'verbose',
          console: true,
        }
      ],
      debug: true,
    }));
})

gulp.task('validateJS', () => {
  return gulp
    .src(javascriptFilePaths)
    .pipe(eslint())
    .pipe(eslint.format())
    .pipe(eslint.failAfterError());
});

gulp.task('fixJS', () => {
  return gulp
    .src(javascriptFilePaths)
    .pipe(eslint({ fix: true }))
    .pipe(gulp.dest((file) => {
      return file.base;
    }))
});

gulp.task('fixSass', () => {
  return gulp
    .src(scssFilePaths)
    .pipe(stylelint({ fix: true }))
    .pipe(gulp.dest((file) => {
      return file.base;
    }));
});


function buildReact() {
  return new Promise((resolve, reject) => {
    webpack(webpackConfig, (err, stats) => {
        if (err) {
            return reject(err)
        }
        if (stats.hasErrors()) {
            return reject(new Error(stats.compilation.errors.join('\n')))
        }
        resolve()
    })
  })
}

// Default task.
gulp.task('default', gulp.series('build:js', 'build:sass', 'build:react'));

// Build tasks.
gulp.task('build', gulp.series('build:js', 'build:sass', 'build:react'))

// Watch tasks.
gulp.task('watch', gulp.series('watchJS', 'watchSass', 'watchReact'))

// Validate tasks.
gulp.task('validate', gulp.series('validateSass', 'validateJS'));

// Syntax fixer tasks.
gulp.task('fix', gulp.series('fixJS', 'fixSass'))
