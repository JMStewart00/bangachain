const path = require('path');

const config = {
  entry: {
    'main': './web/modules/custom/react_search/js/main.js',
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /(node_modules)/,
        use: {
          loader: 'babel-loader'
        }
      }
    ]
  },
  output: {
    path: __dirname + '/web/modules/custom/react_search/dist/js',
    publicPath: '/js',
    filename: '[name].js'
  }
};

module.exports = config;
