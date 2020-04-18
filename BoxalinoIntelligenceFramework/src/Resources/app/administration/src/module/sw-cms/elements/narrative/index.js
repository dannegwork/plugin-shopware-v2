import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'narrative',
    label: 'Boxalino Narrative',
    component: 'sw-cms-el-narrative',
    configComponent: 'sw-cms-el-config-narrative',
    previewComponent: 'sw-cms-el-preview-narrative',
    defaultConfig: {
        widget: {
            source: 'static',
            value: 'navigation',
            required: true
        },
        hitCount: {
            source: 'static',
            value: '',
            required: true
        },
        returnFields: {
            source: 'static',
            value: '',
            required: false
        },
        groupBy: {
            source: 'static',
            value: 'id',
            required: false
        },
        filters: {
            source: 'static',
            value: '',
            required: false
        },
        facets: {
            source: 'static',
            value: '',
            required: false
        },
        context: {
            source: 'static',
            value: 'listing'
        }
    }
});
