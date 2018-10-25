<?php

class Shopware_Controllers_Backend_BoxalinoPerformance extends Shopware_Controllers_Backend_ExtJs
{

    public function getListingSortingsAction()
    {
        /**@var $namespace Enlight_Components_Snippet_Namespace*/
        $namespace = $this->get('snippets')->getNamespace('frontend/listing/listing_actions');

        $coreSortings = array(
            array('id' => 1, 'name' => $namespace->get('ListingSortRelease')),
            array('id' => 2, 'name' => $namespace->get('ListingSortRating')),
            array('id' => 3, 'name' => $namespace->get('ListingSortPriceLowest')),
            array('id' => 4, 'name' => $namespace->get('ListingSortPriceHighest')),
            array('id' => 5, 'name' => $namespace->get('ListingSortName')),
            array('id' => 7, 'name' => $namespace->get('ListingSortRelevance')),
        );

        $this->View()->assign(array(
            'success' => true,
            'data' => $coreSortings
        ));
    }

    public function getConfigOptionsAction(){
        $data = [
            ['id' => 1, 'widget_type' => 'QuickSearch'],
            ['id' => 2, 'widget_type' => 'ProductFinder']
        ];

        $this->View()->assign(array(
            'success' => true,
            'data' => $data,
            'total' => 3
        ));
    }

    public function getNarrativeOptionsAction(){
        $data = [
            ['id' => 1, 'render_option' => 'Server Side'],
            ['id' => 2, 'render_option' => 'AJAX']
        ];

        $this->View()->assign(array(
            'success' => true,
            'data' => $data,
            'total' => 3
        ));
    }
}
