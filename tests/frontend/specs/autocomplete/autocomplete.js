/* global browser, describe, it */

describe('Test Autocomplete features', function () {
    before(function () {
        return browser.updateConfig('--enable-autocomplete');
    });

    it('should show the algolia search form', function () {
        console.log(browser.url('/').getHTML('body', false));
        return browser.url('/').isVisible('.algolia-autocomplete input#search').should.be.true;
    });
});
