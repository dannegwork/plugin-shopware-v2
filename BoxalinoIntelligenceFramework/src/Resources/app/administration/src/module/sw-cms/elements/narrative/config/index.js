import template from './sw-cms-el-config-narrative.html.twig';
import './sw-cms-el-config-narrative.scss';

const { Component, Mixin } = Shopware;

Component.register('sw-cms-el-config-narrative', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    created() {
        this.createdComponent();
    },

    computed: {
        widget: {
            get() {
                return this.element.config.widget.value;
            },

            set(widget) {
                this.element.config.widget.value = widget;
            }
        }
    },

    methods: {
        createdComponent() {
            this.initElementConfig('narrative');
        }
    }
});
