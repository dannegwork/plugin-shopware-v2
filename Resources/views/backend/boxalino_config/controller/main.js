/**
 * main
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 */

Ext.define('Shopware.apps.BoxalinoConfig.controller.Main', {
    extend:     'Ext.app.Controller',
    mainWindow: null,
    init:       function ()
    {
        var me = this;
        me.mainWindow = null;
        me.mainWindow = me.getView('main.Window').create({
            listStore: me.getStore('Stores').load(),
            configStore: me.getStore('Config').load()
        });
        me.control({
            'boxalino-config-main-window': {
                openApply: me.openApplyWindow
            }
        });
        me.callParent(arguments);
    },
    openApplyWindow: function(listStore, configStore) {
        var me = this;
        me.getView('apply.Window').create({
            listStore: listStore,
            configStore: configStore
        });
    }
});