/* global browser, describe, it */

describe('Test Instantsearch features', function () {

    before(function () {
        return browser.updateConfig('--enable-instantsearch');
    });

    it('should show Algolia instantsearch input', function () {
        return browser.url('/women.html').isVisible('.ais-search-box input#search').should.be.true;
    })
});
