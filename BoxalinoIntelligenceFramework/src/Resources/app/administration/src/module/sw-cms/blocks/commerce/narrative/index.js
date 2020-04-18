import './component';
import './preview';

/**
 * @TODO add the widget definition here (search, navigation, similar, etc, etc)
 */
Shopware.Service('cmsService').registerCmsBlock({
    name: 'narrative',
    label: 'Boxalino Narrative',
    category: 'commerce',
    hidden: false,
    removable: false,
    component: 'sw-cms-block-narrative',
    previewComponent: 'sw-cms-preview-narrative',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px',
        sizingMode: 'boxed'
    },
    slots: {
        narrative: {
            type: 'narrative',
            default: {
                config: {
                    context: { source: 'static', value: 'listing' }
                }
            }
        }
    }
});
