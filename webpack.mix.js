const mix = require('laravel-mix');

const path = require("path");

mix.webpackConfig({
    resolve: {
        extensions: [".js", ".vue"],
        alias: {
            "@global": path.resolve(__dirname, "resources/js/global"),
            "@mmis": path.resolve(__dirname, "resources/js/mmis")
        }
    },
    output: {
        chunkFilename: "js/chunks/[name].js"
    },
    devServer: {
        headers: {
            "Access-Control-Allow-Origin": "*"
        }
    }
})

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/main/app.js', 'public/js')
    .vue()
    .sass('resources/css/main.scss', 'public/css');
mix.js('resources/js/mmis/mmis.js', 'public/js')
    .vue()
    // .sass('resources/sass/app.scss', 'public/css');