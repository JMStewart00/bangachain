const path = require('path');

const CleanWebpackPlugin = require('clean-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const FriendlyErrorsWebpackPlugin = require('friendly-errors-webpack-plugin');
const globImporter = require('node-sass-glob-importer');
const autoprefixer = require('autoprefixer');
const MinifyPlugin = require('babel-minify-webpack-plugin');
const FixStyleOnlyEntriesPlugin = require('webpack-fix-style-only-entries');
const BrowserSyncPlugin = require('browser-sync-webpack-plugin');

module.exports = {
  entry: {
    gin_login: ['./src/css/gin_login.scss'],
  },
  output: {
    devtoolLineToLine: true,
    path: path.resolve(__dirname, 'dist'),
    chunkFilename: 'js/async/[name].chunk.js',
    pathinfo: true,
    filename: 'js/[name].js',
  },
  module: {
    rules: [{
        test: /\.(config.js)$/,
        use: [
          {
            loader: 'file-loader',
            options: {
              name: '[path][name].[ext]',
              outputPath: './'
            }
          }
        ]
      },
      {
        test: /\.(png|jpe?g|gif|svg)$/,
        use: [{
            loader: 'file-loader',
            options: {
              name: 'images/[name].[ext]?[hash]',
            },
        }],
      },
      {
        test: /modernizrrc\.js$/,
        loader: 'expose-loader?Modernizr!webpack-modernizr-loader',
      },
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
        },
      },
      {
        test: /\.(css|sass|scss)$/,
        use: [
          {
            loader: MiniCssExtractPlugin.loader,
            options: {
              publicPath: '../'
            }
          },
          {
            loader: 'css-loader',
            options: {
              sourceMap: false,
              importLoaders: 2,
            },
          },
          {
            loader: 'postcss-loader',
            options: {
              plugins: () => [autoprefixer()],
              sourceMap: false,
            },
          },
          {
            loader: 'sass-loader',
            options: {
              importer: globImporter(),
              sourceMap: false,
            },
          },
        ],
      },
    ],
  },
  resolve: {
    modules: [
      path.join(__dirname, 'node_modules'),
    ],
    extensions: ['.js', '.json'],
  },
  plugins: [
    new FriendlyErrorsWebpackPlugin(),
    new FixStyleOnlyEntriesPlugin(),
    new CleanWebpackPlugin(['dist'], {
      root: path.resolve(__dirname),
    }),
    new MiniCssExtractPlugin({
      filename: 'css/[name].css',
    }),
    new MinifyPlugin({}, {
      comments: false,
      sourceMap: '',
    }),
    new BrowserSyncPlugin({
      proxy: {
        target: 'https://drupal.local',
        proxyReq: [
          function(proxyReq) {
            proxyReq.setHeader('Cache-Control', 'no-cache, no-store');
          }
        ]
      },
      browser: 'chrome',
      open: false,
      https: false,
      notify: true,
      logConnections: true,
      reloadOnRestart: true,
      injectChanges: true,
      online: true,
      // reloadDelay: 500,
      ghostMode: {
        clicks: false,
        forms: false,
        scroll: false,
      },
      files: [
       {
         match: ['**/*.css', '**/*.js'],
         fn: (event, file) => {
           if (event == 'change') {
             const bs = require("browser-sync").get("bs-webpack-plugin");
             if (file.split('.').pop()=='js') {
               bs.reload();
             } else {
               bs.stream();
             }
           }
         }
       }
     ]
    }, {
      // prevent BrowserSync from reloading the page
      // and let Webpack Dev Server take care of this
      reload: false,
      injectCss: true,
      name: 'bs-webpack-plugin'
    })
  ],
  watchOptions: {
    aggregateTimeout: 300,
  }
};
