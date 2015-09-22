var renderHit = function (instantHitTemplate) {
    return function (hit) {

        if (Array.isArray(hit.categories))
            hit.categories = hit.categories.join(', ');

        if (Array.isArray(hit._highlightResult.name))
            hit._highlightResult.name = hit._highlightResult.name[0];

        if (Array.isArray(hit.price))
            hit.price = hit.price[0];

        var time = Math.floor(Date.now() / 1000);

        if ((hit.special_price_from_date != undefined && (hit.special_price_from_date > time && hit.special_price_from_date !== '')) ||
            (hit.special_price_to_date != undefined && (hit.special_price_to_date < time && hit.special_price_to_date !== ''))) {
            delete hit.special_price_from_date;
            delete hit.special_price_to_date;
            delete hit.special_price;
            delete hit.special_price_with_tax;
            delete hit.special_price_formated;
            delete hit.special_price_with_tax_formated;
        }

        if (hit.min_formated !== undefined) {
            delete hit.price;
            delete hit.price_formated;
            delete hit.price_with_tax;
            delete hit.price_with_tax_formated;
        }

        return instantHitTemplate.render(hit);
    }
};

var getFacetWidget = function (facet) {
    if (facet.type === 'conjunctive') {
        return instantsearch.widgets.refinementList({
            container: facet.wrapper.appendChild(document.createElement('div')),
            facetName: facet.attribute,
            operator: 'and',
            template: facet.template
        })
    }

    if (facet.type === 'disjunctive') {
        return instantsearch.widgets.refinementList({
            container: facet.wrapper.appendChild(document.createElement('div')),
            facetName: facet.attribute,
            operator: 'or',
            template: facet.template
        })
    }

    if (facet.type == 'slider') {
        return instantsearch.widgets.rangeSlider({
            container: facet.wrapper.appendChild(document.createElement('div')),
            facetName: facet.attribute
        })
    }
};