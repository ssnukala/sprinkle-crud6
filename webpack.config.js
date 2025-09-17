const mix = require('laravel-mix');
const fs = require('fs');

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

mix.options({
    processCssUrls: false
});

// CRUD6 JavaScript
mix.js('app/assets/js/crud6-table.js', 'public/js/crud6-table.js');

// If you have CSS files, add them here
// mix.sass('app/assets/css/crud6.scss', 'public/css/crud6.css');