Ext.define('Shopware.apps.BoxalinoConfig.store.Stores', {
    extend:'Ext.data.Store',

    model:'Shopware.apps.BoxalinoConfig.model.Main',

    proxy:{
        type:'ajax',

        url: '{url controller=BoxalinoConfig action=getStores}',

        reader:{
            type:'json',
            root:'data',
            totalProperty:'total'
        }
    }
});
