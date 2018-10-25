<?php

use Boxalino\Helper\BxData;
use Boxalino\Helper\BxRender;
use Boxalino\Helper\P13NHelper;

class Shopware_Controllers_Frontend_BxNarrative extends Enlight_Controller_Action {

    public function indexAction() {
        $this->getNarrativeAction();
    }

    protected function setRequestWithReferrerParams($request) {

        $address = $_SERVER['HTTP_REFERER'];
        $basePath = $request->getBasePath();
        $start = strpos($address, $basePath) + strlen($basePath);
        $end = strpos($address, '?');
        $length = $end ? $end - $start : strlen($address);
        $pathInfo = substr($address, $start, $length);
        $request->setPathInfo($pathInfo);
        $params = explode('&', substr ($address,strpos($address, '?')+1, strlen($address)));
        foreach ($params as $index => $param){
            $keyValue = explode("=", $param);
            $params[$keyValue[0]] = $keyValue[1];
            unset($params[$index]);
        }
        foreach ($params as $key => $value) {
            $request->setParam($key, $value);
            if($key == 'p') {
                $request->setParam('sPage', (int) $value);
            }
        }
        return $request;
    }

    protected function getDependencyElement($url, $type) {
        $element = '';
        if($type == 'css'){
            $element = "<link href=\"{$url}\" type=\"text/css\" rel=\"stylesheet\" />";
        } else if($type == 'js') {
            $element = "<script src=\"{$url}\" type=\"text/javascript\"></script>";
        }
        return $element;
    }

    protected function renderDependencies($dependencies) {
        $html = '';
        if(isset($dependencies['js'])) {
            foreach ($dependencies['js'] as $js) {
                $url = $js;
                $html .= $this->getDependencyElement($url, 'js');
            }
        }
        if(isset($dependencies['css'])) {
            foreach ($dependencies['css'] as $css) {
                $url = $css;
                $html .= $this->getDependencyElement($url, 'css');
            }
        }
        return $html;
    }

    public function getNarrativeAction() {
        try{
            $choiceId = $this->Request()->getQuery('choice_id');
            $additional = $this->Request()->getQuery('additional');
            $request = Shopware()->Front()->Request();
            $request = $this->setRequestWithReferrerParams($request);
            $params = $request->getParams();
            $helper = P13NHelper::instance();

            $context  = Shopware()->Container()->get('shopware_storefront.context_service')->getShopContext();
            $criteria = Shopware()->Container()->get('shopware_search.store_front_criteria_factory')->createSearchCriteria($request, $context);
            $hitCount = $criteria->getLimit();
            $pageOffset = $criteria->getOffset();
            $orderParam = Shopware()->Container()->get('query_alias_mapper')->getShortAlias('sSort');
            $defaultSort = null;
            if(is_null($request->getParam($orderParam))) {
                $request->setParam('sSort', 7);
            }
            if(is_null($request->getParam('sSort')) && is_null($request->getParam($orderParam))) {
                if(Shopware()->Config()->get('boxalino_navigation_sorting')) {
                    $request->setParam('sSort', 7);
                } else {
                    $default = Shopware()->Container()->get('config')->get('defaultListingSorting');
                    $request->setParam('sSort', $default);
                }
            }

            $searchInterceptor = Shopware()->Plugins()->Frontend()->Boxalino()->getSearchInterceptor();
            $sort =  $searchInterceptor->getSortOrder($criteria, null, true);
            $facets = $criteria->getFacets();
            $options = $searchInterceptor->getFacetConfig($facets, $request);
            $narratives = $helper->getNarrative($choiceId, $additional, $options, $hitCount, $pageOffset, $sort, $params);
            $dependencies = $this->renderDependencies($helper->getNarrativeDependencies($choiceId));
            $bxData = BxData::instance();
            $bxRender = new BxRender($helper, $bxData, $searchInterceptor, $request);

            $path = Shopware()->Plugins()->Frontend()->Boxalino()->Path();
            $this->View()->addTemplateDir($path . 'Resources/views/emotion/');
            $this->View()->loadTemplate('frontend/plugins/boxalino/journey/main.tpl');

            $this->View()->assign('dependencies', $dependencies);
            $this->View()->assign('NarrativeRenderer', $narratives);
            $this->View()->assign('bxRender', $bxRender);
        }catch (\Exception $e) {
            var_dump($e->getMessage());exit;
        }

    }
}