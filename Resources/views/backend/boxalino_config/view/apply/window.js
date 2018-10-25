Ext.require([
    'Ext.grid.*', 'Ext.data.*', 'Ext.panel.*'
]);
Ext.define('Shopware.apps.BoxalinoConfig.view.apply.Window', {
    extend: 'Enlight.app.Window',
    title: '{s namespace=Boxalino name=log_title}Boxalino Configuration Helper{/s}',
    alias: 'widget.boxalino-config-apply-window',
    border: false,
    autoShow: true,
    resizable: true,
    layout: {
        type: 'fit'
    },
    height: 450,
    width: 300,
    initComponent: function () {
        var me = this;
        me.items = [
            me.createApplyGrid(me)
        ];
        me.callParent(arguments);
    },
    createApplyGrid: function (me) {
        var grid = Ext.create('Ext.grid.Panel', {
            store: me.listStore,
            selType: 'cellmodel',
            width: '100%',
            title: "Select Stores To Apply Configuration",
            plugins: [
                Ext.create('Ext.grid.plugin.CellEditing', {
                    clicksToEdit: 1
                })
            ],
            columns: [
                {
                    text: "Name",
                    width: '70%',
                    hideable: false,
                    dataIndex: 'name'
                },
                {
                    xtype: 'booleancolumn',
                    header: 'Exclude?',
                    width: '30%',
                    flex: 1,
                    dataIndex: 'exclude',
                    renderer:  function(value, metaData, record) {
                        var isExclude = record.get('exclude');
                        if (isExclude) {
                            return '<span style="display:block; margin: 0 auto; height:16px; width:16px;" class="sprite-ui-check-box"></span>';
                        }
                    },
                    editor: {
                        xtype: 'checkbox',
                        inputValue: 1,
                        uncheckedValue: 0
                    }
                }
            ],
            buttons: [
                {
                    text: 'Exclude All',
                    handler: function() {
                        me.listStore.data.items.forEach(function(item) {
                            item.data.exclude = true;
                        });
                        grid.getView().refresh();
                    }
                },
                {
                    text: 'Apply To Shops',
                    handler: function () {
                        var data = [];
                        me.configStore.data.items.forEach(function(item) {
                            data.push(item.data);
                        });
                        var stores = [];
                        me.listStore.data.items.forEach(function(item) {
                            if(item.data.exclude === false) {
                                stores.push(item.data.id);
                            }
                        });
                        Ext.Ajax.request({
                            url: '{url module=backend controller=BoxalinoConfig action=applyConfig}',
                            method: 'POST',
                            params: {
                                exportedConfig: JSON.stringify(data),
                                stores: JSON.stringify(stores)
                            },
                            success: function(response) {
                                if(response.responseText === 'applied') {
                                    Ext.Msg.alert('Result', "Configuration successfully applied.");
                                    grid.up('window').close();
                                }
                            }
                        });
                    }
                }
            ]
        });
        return grid;
    }
});