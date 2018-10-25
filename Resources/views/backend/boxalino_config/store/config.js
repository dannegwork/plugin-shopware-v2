Ext.define('Shopware.apps.BoxalinoConfig.store.Config', {
    extend:'Ext.data.Store',

    model:'Shopware.apps.BoxalinoConfig.model.Config',

    proxy:{
        type:'ajax',

        url: '{url controller=BoxalinoConfig action=getStoreConfig}',

        reader:{
            type:'json',
            root:'data'
        }
    }
});
