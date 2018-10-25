//{block name="backend/boxalino_emotion/store/list"}
Ext.define('Shopware.apps.BoxalinoEmotion.store.List', {
    extend:'Ext.data.Store',

    model:'Shopware.apps.BoxalinoEmotion.model.Main',

    proxy:{
        type:'ajax',

        url: '{url controller=BoxalinoPerformance action=getConfigOptions}',

        reader:{
            type:'json',
            root:'data',
            totalProperty:'total'
        }
    }
});
//{/block}
