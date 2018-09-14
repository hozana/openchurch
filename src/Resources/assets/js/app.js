/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you require will output into a single css file (app.css in this case)
require('../../../../node_modules/highlight.js/styles/arta.css')
require('../css/global.scss');

var $ = require('jquery');
require('bootstrap');
var hljs = require('highlight.js');

// or you can include specific pieces
// require('bootstrap/js/dist/tooltip');
// require('bootstrap/js/dist/popover');

hljs.initHighlightingOnLoad();
