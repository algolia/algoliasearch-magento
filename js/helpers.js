var tranformHit = function (hit) {
    if (Array.isArray(hit.categories))
        hit.categories = hit.categories.join(', ');

    if (Array.isArray(hit.categories_without_path)) {
        hit.categories_without_path = hit.categories_without_path.join(', ');
    }

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

    return hit;
};

var getFacetWidget = function (facet, templates) {
    if (facet.attribute === 'categories')
        facet.type = 'hierarchical';

    if (facet.type == 'hierarchical') {

        var hierarchical_levels = [];
        for (var l = 0; l < 10; l++)
            hierarchical_levels.push('categories.level' + l.toString());

        return algoliaBundle.instantsearch.widgets.hierarchicalMenu({
            container: facet.wrapper.appendChild(document.createElement('div')),
            attributes: hierarchical_levels,
            separator: ' /// ',
            alwaysGetRootLevel: true,
            templates: templates,
            sortBy: ['name:asc'],
            cssClasses: {
                list: 'hierarchical',
                root: 'facet hierarchical'
            }
        });
    }

    if (facet.type === 'conjunctive') {
        return algoliaBundle.instantsearch.widgets.refinementList({
            container: facet.wrapper.appendChild(document.createElement('div')),
            facetName: facet.attribute,
            operator: 'and',
            templates: templates,
            cssClasses: {
                root: 'facet conjunctive'
            }
        });
    }

    if (facet.type === 'disjunctive') {
        return algoliaBundle.instantsearch.widgets.refinementList({
            container: facet.wrapper.appendChild(document.createElement('div')),
            facetName: facet.attribute,
            operator: 'or',
            templates: templates,
            cssClasses: {
                root: 'facet disjunctive'
            }
        });
    }

    if (facet.type == 'slider') {
        return algoliaBundle.instantsearch.widgets.rangeSlider({
            container: facet.wrapper.appendChild(document.createElement('div')),
            facetName: facet.attribute,
            templates: templates,
            cssClasses: {
                root: 'facet slider'
            }
        });
    }
};
