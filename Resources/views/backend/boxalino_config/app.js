Ext.define('Shopware.apps.BoxalinoConfig', {
    extend:      'Enlight.app.SubApplication',
    name:        'Shopware.apps.BoxalinoConfig',
    bulkLoad:    true,
    loadPath:    '{url action=load}',
    controllers: ['Main'],
    views:       ['main.Window', 'apply.Window'],
    models:      ['Main', 'Config'],
    store:       ['Stores', 'Config'],
    launch:      function ()
    {
        var me = this;
        me.windowTitle = '{s namespace=Boxalino name=log_title}Boxalino Configuration Helper{/s}';
        var mainController = me.getController('Main');
        return mainController.mainWindow;
    }
});