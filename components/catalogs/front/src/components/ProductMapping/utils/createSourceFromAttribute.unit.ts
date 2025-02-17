import {createSourceFromAttribute} from './createSourceFromAttribute';

jest.unmock('./createSourceFromAttribute');

const attributeToSourceTests = [
    {
        attribute: {
            code: 'name',
            label: 'Name',
            type: 'pim_catalog_text',
            scopable: false,
            localizable: false,
            attribute_group_code: 'marketing',
            attribute_group_label: 'Marketing',
        },
        expectedSource: {
            source: 'name',
            locale: null,
            scope: null,
        },
    },
    {
        attribute: {
            code: 'name',
            label: 'Name',
            type: 'pim_catalog_text',
            scopable: true,
            localizable: true,
            attribute_group_code: 'marketing',
            attribute_group_label: 'Marketing',
        },
        expectedSource: {
            source: 'name',
            locale: null,
            scope: null,
        },
    },
    {
        attribute: {
            code: 'color',
            label: 'Color',
            type: 'pim_catalog_simpleselect',
            scopable: true,
            localizable: true,
            attribute_group_code: 'marketing',
            attribute_group_label: 'Marketing',
        },
        expectedSource: {
            source: 'color',
            locale: null,
            scope: null,
            parameters: {
                label_locale: null,
            },
        },
    },
    {
        attribute: {
            code: 'amenities',
            label: 'Amenities',
            type: 'pim_catalog_multiselect',
            scopable: true,
            localizable: true,
            attribute_group_code: 'marketing',
            attribute_group_label: 'Marketing',
        },
        expectedSource: {
            source: 'amenities',
            locale: null,
            scope: null,
            parameters: {
                label_locale: null,
            },
        },
    },
    {
        attribute: {
            code: 'price',
            label: 'price',
            type: 'pim_catalog_price_collection',
            scopable: true,
            localizable: true,
            attribute_group_code: 'marketing',
            attribute_group_label: 'Marketing',
        },
        expectedSource: {
            source: 'price',
            locale: null,
            scope: null,
            parameters: {
                currency: null,
            },
        },
    },
];

test.each(attributeToSourceTests)('it creates targets from an attribute', ({attribute, expectedSource}) => {
    expect(createSourceFromAttribute(attribute)).toEqual(expectedSource);
});
