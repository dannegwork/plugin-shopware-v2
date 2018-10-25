Ext.define('Shopware.apps.BoxalinoConfig.model.Config', {
    extend: 'Ext.data.Model',

    fields: [
        { name: 'name', type: 'string'},
        { name: 'value', type: 'string'},
        { name: 'label', type: 'string'},
        { name: 'exclude', type: 'boolean'},
        { name: 'id', type: 'integer'},
        { name: 'type', type: 'string'}
    ]
});