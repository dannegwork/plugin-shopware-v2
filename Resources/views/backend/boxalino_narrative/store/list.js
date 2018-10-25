//{block name="backend/boxalino_narrative/store/list"}
Ext.define('Shopware.apps.BoxalinoNarrative.store.List', {
    extend:'Ext.data.Store',

    model:'Shopware.apps.BoxalinoNarrative.model.Main',

    proxy:{
        type:'ajax',

        url: '{url controller=BoxalinoPerformance action=getNarrativeOptions}',

        reader:{
            type:'json',
            root:'data',
            totalProperty:'total'
        }
    }
});
//{/block}
