var webpack = require('webpack');
var path = require('path');

// workaround for old node version shipping w/ ubuntu
var es6_promise = require('es6-promise').polyfill()

var BUILD_DIR = path.resolve(__dirname, 'dist');
var APP_DIR = path.resolve(__dirname, 'src');

var config = {
    entry: {
        entry: APP_DIR + '/index.jsx',
        vendor: ['react', 'react-dom', 'jquery', 'react-bootstrap']
    },
    output: {
        path: BUILD_DIR,
        filename: 'bundle.js'
    },
    module : {
        rules : [
            {
                test : /\.jsx?/,
                include : APP_DIR,
                loader : 'babel-loader',
                query: {
                    presets: ['env', 'react']
                }
            },
            { test : /\.css?/, loader : 'style-loader!css-loader' },
            { test: /\.png$/, loader: "url-loader?limit=100000" },
            { test: /\.jpg$/, loader: "file-loader" },
            { test: /\.(woff|woff2)(\?v=\d+\.\d+\.\d+)?$/, loader: 'url-loader?limit=10000&mimetype=application/font-woff' },
            { test: /\.ttf(\?v=\d+\.\d+\.\d+)?$/, loader: 'url-loader?limit=10000&mimetype=application/octet-stream' },
            { test: /\.eot(\?v=\d+\.\d+\.\d+)?$/, loader: 'file-loader' },
            { test: /\.svg(\?v=\d+\.\d+\.\d+)?$/, loader: 'url-loader?limit=10000&mimetype=image/svg+xml' }
        ]
    },
    optimization: {
        runtimeChunk: "single", // enable "runtime" chunk
        splitChunks: {
            cacheGroups: {
                vendor: {
                    test: /[\\/]node_modules[\\/]/,
                    name: "vendor",
                    chunks: "all"
                }
            }
        }
    }
};

module.exports = config;

