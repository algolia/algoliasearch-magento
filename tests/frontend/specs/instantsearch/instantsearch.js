/* global browser, describe, it */

describe('Test Instantsearch features', function () {

    before(function () {
        return browser.updateConfig('--enable-instantsearch');
    });

    it('shouldn\'t show Algolia autocomplete form', function () {
        return browser.url('/').isVisible('.algolia-autocomplete input#search').should.be.false;
    })
});