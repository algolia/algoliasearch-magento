module.exports = {
  $: require('jquery'),
  Hogan: require('hogan.js'),
  algoliasearch: require('algoliasearch'),
  algoliasearchHelper: require('algoliasearch-helper')
};

require('jquery-ui/slider');

// typeahead 0.10.5 is not commonJS, so we just do the firty work
// we use 0.10.5 because typeahead 0.11 is completely broken
var oldJQuery = window.jQuery;
window.jQuery = module.exports.$;
require('typeahead.js');
window.jQuery = oldJQuery;
