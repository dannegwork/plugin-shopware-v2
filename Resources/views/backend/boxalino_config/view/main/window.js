
Ext.require([
    'Ext.grid.*', 'Ext.data.*', 'Ext.panel.*'
]);
Ext.define('Shopware.apps.BoxalinoConfig.view.main.Window', {
    extend:    'Enlight.app.Window',
    title:     '{s namespace=Boxalino name=log_title}Boxalino Configuration Helper{/s}',
    alias:     'widget.boxalino-config-main-window',
    border:    false,
    autoShow:  true,
    resizable: true,
    layout:    {
        type: 'fit'
    },
    height: 450,
    width:  600,
    initComponent:   function ()
    {
        var me = this;
        me.items = [
            me.createMainGrid(me)
        ];
        me.callParent(arguments);
    },
    createMainGrid:  function (me) {
        return Ext.create('Ext.panel.Panel', {
            id: 'mainGrid',
            forceFit: true,
            border: false,
            autoScroll: true,
            height: '100%',
            width: '100%',
            layout: {
                type: 'vbox',
                align: 'stretch',
                padding: 5
            },
            items: [
                Ext.create('Ext.form.Panel', {
                    title: 'Upload Configuration',
                    frame: true,
                    items: [{
                        xtype: 'filefield',
                        name: 'bx_config',
                        fieldLabel: 'File',
                        labelWidth: 50,
                        msgTarget: 'side',
                        allowBlank: false,
                        anchor: '100%',
                        buttonText: 'Select Config...'
                    }],
                    buttons: [{
                        text: 'Upload File',
                        handler: function() {
                            var form = this.up('form').getForm();
                            if(form.isValid()){
                                form.submit({
                                    url: '{url controller=BoxalinoConfig action=uploadConfig}',
                                    waitMsg: 'Uploading your config...',
                                    success: function (fp, o) {
                                        Ext.Msg.alert('Configuration Upload', o.result.message);
                                        me.show();
                                        me.configStore.getProxy().extraParams['load_import'] = true;
                                        me.configStore.load();
                                        me.configStore.getProxy().extraParams['load_import'] = null;
                                    },
                                    failure: function (fp, o) {
                                        Ext.Msg.alert('Upload Error', o.result.message);
                                        me.show();
                                    }
                                });
                            }
                        }
                    }]
                }),{
                    xtype: 'splitter'
                },
                Ext.create('Ext.form.ComboBox', {
                    fieldLabel: 'Choose Store Config',
                    store: me.listStore,
                    displayField: 'name',
                    valueField: 'id',
                    listeners: {
                        'select': function(a,b) {
                            me.configStore.getProxy().extraParams['store_id'] = b[0]['data']['id'];
                            me.configStore.load();
                        }
                    }
                }),{
                    xtype: 'splitter'
                },
                me.createButtonPanel(me),
                {
                    xtype: 'splitter'
                },
                me.createPanelGrid(me)
            ]
        });
    },
    createButtonPanel: function(me) {
        return Ext.create('Ext.panel.Panel', {
            layout: 'column',
            border: false,
            items: [
                {
                    xtype:'button',
                    text: 'Export Configuration',
                    padding: 5,
                    style: { marginRight: '10px' },
                    handler: function() {
                        var data = [];
                        me.configStore.data.items.forEach(function(item) {
                            data.push(item.data);
                        });
                        Ext.Ajax.request({
                            url: '{url module=backend controller=BoxalinoConfig action=saveConfig}',
                            method: 'POST',
                            params: {
                                exportedConfig: JSON.stringify(data)
                            },
                            success: function(response) {
                                if(response.responseText === 'saved') {
                                    window.open('{url module=backend controller=BoxalinoConfig action=exportConfig}');
                                }
                            }
                        });
                    }
                },{
                    padding: 5,
                    xtype:'button',
                    text: 'Apply Configuration ',
                    handler: function() {
                        me.fireEvent('openApply', me.listStore, me.configStore);
                    }
                }
            ]
        });
    },
    createPanelGrid: function(me) {
        var panel =  Ext.create('Ext.grid.Panel', {
            store: me.configStore,
            selType: 'cellmodel',
            width: '100%',
            title: " ",
            plugins: [
                Ext.create('Ext.grid.plugin.CellEditing', {
                    clicksToEdit: 1
                })
            ],
            header: {
                titlePosition: 0,
                items:[{
                    xtype:'button',
                    text: 'Exclude All Fields',
                    handler: function() {
                        me.configStore.data.items.forEach(function(item) {
                            item.data.exclude = true;
                        });
                        panel.getView().refresh();
                    }
                }, {
                    xtype:'button',
                    text: 'Include All Fields',
                    handler: function() {
                        me.configStore.data.items.forEach(function(item) {
                            item.data.exclude = false;
                        });
                        panel.getView().refresh();
                    }
                }]
            },
            columns: [
                {
                    text: "Name",
                    width: '55%',
                    hideable: false,
                    dataIndex: 'label'
                },
                {
                    text: "Value",
                    width: '30%',
                    hideable: false,
                    dataIndex: 'value',
                    editor: 'textfield'
                }
                ,{
                    xtype: 'booleancolumn',
                    header: 'Exclude?',
                    dataIndex: 'exclude',
                    width: '15%',
                    flex: 1,
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
            ]
        });
        return panel;
    },
    registerEvents: function() {
        this.addEvents('openApply')
    }
});