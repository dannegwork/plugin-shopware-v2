Ext.define('Shopware.apps.BoxalinoConfig.model.Main', {
    extend: 'Ext.data.Model',

    fields: [
        { name: 'id', type: 'int'},
        { name: 'name', type: 'string'},
        { name: 'exclude', type: 'boolean'}
    ]
});