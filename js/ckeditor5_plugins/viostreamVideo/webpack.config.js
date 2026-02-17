/**
 * @file
 * Webpack configuration for the Viostream CKEditor 5 plugin.
 *
 * Compiles the ES module source into a single JS file that Drupal can load.
 * CKEditor 5 packages are externalized since Drupal provides them at runtime.
 */
const path = require('path');
const TerserPlugin = require('terser-webpack-plugin');

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';

  return {
    entry: './src/index.js',
    output: {
      path: path.resolve(__dirname, 'build'),
      filename: 'viostreamVideo.js',
      library: ['CKEditor5', 'viostreamVideo'],
      libraryTarget: 'umd',
      libraryExport: 'default',
    },
    // CKEditor 5 packages are provided by Drupal core at runtime.
    // They must be externalized so webpack doesn't try to bundle them.
    externals: {
      'ckeditor5/src/core': {
        commonjs: 'ckeditor5/src/core',
        commonjs2: 'ckeditor5/src/core',
        amd: 'ckeditor5/src/core',
        root: ['CKEditor5', 'core'],
      },
      'ckeditor5/src/ui': {
        commonjs: 'ckeditor5/src/ui',
        commonjs2: 'ckeditor5/src/ui',
        amd: 'ckeditor5/src/ui',
        root: ['CKEditor5', 'ui'],
      },
      'ckeditor5/src/widget': {
        commonjs: 'ckeditor5/src/widget',
        commonjs2: 'ckeditor5/src/widget',
        amd: 'ckeditor5/src/widget',
        root: ['CKEditor5', 'widget'],
      },
      'ckeditor5/src/engine': {
        commonjs: 'ckeditor5/src/engine',
        commonjs2: 'ckeditor5/src/engine',
        amd: 'ckeditor5/src/engine',
        root: ['CKEditor5', 'engine'],
      },
    },
    module: {
      rules: [
        {
          test: /\.svg$/,
          type: 'asset/source',
        },
      ],
    },
    optimization: {
      minimize: isProduction,
      minimizer: [
        new TerserPlugin({
          terserOptions: {
            output: {
              comments: false,
            },
          },
          extractComments: false,
        }),
      ],
    },
    performance: {
      hints: false,
    },
  };
};
