import template from './sw-cms-el-narrative.html.twig';
import './sw-cms-el-narrative.scss';

const { Component, Mixin } = Shopware;

Component.register('sw-cms-el-narrative', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    computed: {
        widget() {
            return this.element.config.widget.value;
        },

        hitCount() {
            return this.element.config.hitCount.value;
        },

        returnFields() {
            return this.element.config.returnFields.value;
        },

        groupBy() {
            return this.element.config.groupBy.value;
        },

        filters() {
            return this.element.config.filters.value;
        },

        facets() {
            return this.element.config.facets.value;
        },

        context() {
            return this.element.config.context.value;
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('narrative');
        }
    }
});
