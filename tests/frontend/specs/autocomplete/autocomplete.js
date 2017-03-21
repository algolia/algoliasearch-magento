/* global browser, describe, it */

describe('Test Autocomplete', function () {
    it('should show the algolia search form', function () {
        return browser.url('/').isVisible('.algolia-autocomplete input#search').should.be.true;
    });
});