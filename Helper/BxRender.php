<?php
namespace Boxalino\Helper;

class BxRender{

    protected $p13Helper;

    protected $dataHelper;

    protected $request;

    protected $searchInterceptor;

    protected $resourceManager;

    public function __construct($p13Helper, $dataHelper, $searchInterceptor, $request)
    {
        $this->p13Helper = $p13Helper;
        $this->dataHelper = $dataHelper;
        $this->searchInterceptor = $searchInterceptor;
        $this->request = $request;
        $this->resourceManager = \Boxalino\Helper\BxResourceManager::instance();
    }

    protected function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }


    protected function getDecodedValues($values) {
        if(is_array($values)) {
            foreach ($values as $i => $value) {
                if($this->isJson($value)) {
                    $values[$i] = json_decode($value, true);
                }
            }
        }
        return $values;
    }

    public function renderElement($viewElement, $additionalParameter = array()) {

        $html = '';
        if($viewElement) {
            $view = $this->createView($viewElement, $additionalParameter);
            $parameters = $viewElement['parameters'];
            foreach ($parameters as $parameter) {
                $paramName = $parameter['name'];
                $assignValues = $this->getDecodedValues($parameter['values']);
                $assignValues = sizeof($assignValues) == 1 ? reset($assignValues) : $assignValues;
                $view->assign($paramName, $assignValues);
                if (strpos($paramName, 'shopware_smarty_function_') === 0) {
                    $function = substr($paramName, strlen('shopware_smarty_function_'));
                    foreach ($parameter['values'] as $value) {
                        if($function == 'assign') {
                            $args = [json_decode($value, true)];
                        } else {
                            $args = [$value];
                        }
                        call_user_func_array(array($view, $function), $args);
                    }
                }
            }
            try{
                $html = $view->render();
            }catch(\Exception $e) {
                var_dump($e->getMessage());
                var_dump($additionalParameter);
                var_dump($viewElement);exit;
            }
        }
        return $html;
    }

    protected function createView($viewElement, $additionalParameter) {
        $view =  new Enlight_View_Default(Shopware()->Container()->get('Template'));
        $view = $this->prepareView($view, $viewElement, $additionalParameter);
        return $view;
    }

    protected function prepareView($view, $viewElement, $additionalParameter) {
        $this->applyThemeConfig($view);
        $this->assignSubRenderings($view, $viewElement);
        $this->assignTemplateData($view, $viewElement, $additionalParameter);
        $templateDir = Shopware()->Plugins()->Frontend()->Boxalino()->Path();
        $view->addTemplateDir($templateDir . 'Resources/views/emotion/');
        return $view;
    }

    protected function assignTemplateData(&$view, $viewElement, $additionalParameter) {
        $parameters = $viewElement['parameters'];
        foreach ($parameters as $parameter) {
            if($parameter['name'] == 'format') {
                $viewElement['format']  = reset($parameter['values']);
                break;
            }
        }
        switch($viewElement['format']) {
            case 'product':
                if(!empty($additionalParameter)) {
                    $index = isset($additionalParameter['list_index']) ? $additionalParameter['list_index'] : 0;
                    $view->assign('sArticle', $this->getViewElementProduct($viewElement, $index));
                } else {
                    $this->getListingData($view, $viewElement);
                    $this->prepareCollection($viewElement);
                    $view->assign('sPage', $this->request->getParam('sPage', 1));

                }
                break;
            case 'filter_panel':
                $this->getFilterPanelData($view);
                break;
            case 'banner':
                $this->getBannerData($view, $viewElement);
                break;
            case 'blog':
                $view->assign('sArticle', $this->getBlogArticle($viewElement, $additionalParameter));
                $view->assign('productBoxLayout', 'minimal');
            case 'voucher':
                $this->getVoucherData($view, $viewElement);
            default:
                break;
        }
    }

    protected function getBlogArticle($visualElement, $additionalParameter) {
        $product = false;
        $variant_index = 0;
        $index = isset($additionalParameter['list_index']) ? $additionalParameter['list_index'] : 0;
        foreach ($visualElement['parameters'] as $parameter) {
            if($parameter['name'] == 'variant') {
                $variant_index = reset($parameter['values']);
            } else if($parameter['name'] == 'index') {
                $index = reset($parameter['values']);
            }
        }
        $choiceId = $this->p13Helper->getVariantChoiceId($variant_index);
        $ids = $this->p13Helper->getHitFieldValues('id', 'blog', $choiceId);
        foreach ($ids as $i => $id) {
            $ids[$i] = str_replace('blog_', '', $id);
        }
        $entity_id = isset($ids[$index]) ? $ids[$index] : null;
        if($entity_id) {
            $collection = $this->resourceManager->getResource($variant_index, 'collection');
            if(!is_null($collection)) {
                foreach ($collection as $product) {
                    if($product['id'] == $entity_id){
                        return $product;
                    }
                }
            }

            $product = $this->resourceManager->getResource($entity_id, 'blog');
            if(is_null($product)) {
                $articles = $this->dataHelper->enhanceBlogArticles($this->p13Helper->getBlogs([$entity_id]));
                $product = reset($articles);
                $this->resourceManager->setResource($product, $entity_id, 'blog');
            }
        }
        return $product;
    }

    protected function getBannerData(&$view, $visualElement) {
        $variant_index = 0;
        foreach ($visualElement['parameters'] as $parameter) {
            if($parameter['name'] == 'variant') {
                $variant_index = reset($parameter['values']);
            }
        }
        $choice_id = $this->p13Helper->getResponse()->getChoiceIdFromVariantIndex($variant_index);
        $bannerData = $this->p13Helper->getBannerData($choice_id);
        $view->assign('Data', $bannerData);
    }

    protected function getVoucherData(&$view, $visualElement) {
        $variant_index = 0;
        foreach ($visualElement['parameters'] as $parameter) {
            if($parameter['name'] == 'variant') {
                $variant_index = reset($parameter['values']);
            }
        }
        $choice_id = $this->p13Helper->getResponse()->getChoiceIdFromVariantIndex($variant_index);
        $voucherData = $this->p13Helper->getVoucherResponse($choice_id);
        $view->assign('voucher', $voucherData);
    }

    protected function getFilterPanelData(&$view) {
        $context = Shopware()->Container()->get('shopware_storefront.context_service')->getShopContext();

        $criteria = Shopware()->Container()->get('shopware_search.store_front_criteria_factory')->createSearchCriteria($this->request, $context);

        $criteria->removeCondition("term");
        $criteria->removeBaseCondition("search");
        $facets = $criteria->getFacets();
        $facets = $this->searchInterceptor->updateFacetsWithResult($facets, $context, $this->request);
        $view->assign('facets', $facets);
        $view->assign('bxFacets', $this->p13Helper->getFacets('product'));
        $view->assign('criteria', $criteria);
        $view->assign('listingMode', 'full_page_reload');
        $view->assign('sSort', $this->request->getParam('sSort', 7));
        $view->assign('facetOptions', $this->searchInterceptor->getFacetOptions());
        $view->assign('shortParameters', Shopware()->Container()->get('query_alias_mapper')->getQueryAliases());
    }

    protected function getListingData(&$view, $visualElement) {
        $variant_index = 0;
        foreach ($visualElement['parameters'] as $parameter) {
            if($parameter['name'] == 'variant') {
                $variant_index = reset($parameter['values']);
                break;
            }
        }
        $choice_id = $this->p13Helper->getResponse()->getChoiceIdFromVariantIndex($variant_index);
        $context = Shopware()->Container()->get('shopware_storefront.context_service')->getShopContext();
        $criteria = Shopware()->Container()->get('shopware_search.store_front_criteria_factory')->createSearchCriteria($this->request, $context);
        $view->assign('sPage', $this->request->getParam('sPage', 1));
        $view->assign('sSort', $this->request->getParam('sSort', 7));
        $view->assign('baseUrl', '/shopware_5_3v2/');
        $view->assign('pages', ceil($this->p13Helper->getTotalHitCount('product', $choice_id) / $criteria->getLimit()));
        $view->assign('shortParameters', Shopware()->Container()->get('query_alias_mapper')->getQueryAliases());
        $view->assign('listingMode', 'full_page_reload');
        $sortingIds = Shopware()->Container()->get('config')->get('searchSortings');
        $sortingIds = array_filter(explode('|', $sortingIds));
        $service = Shopware()->Container()->get('shopware_storefront.custom_sorting_service');
        $sortings = $service->getList($sortingIds, $context);
        $view->assign('sortings', $sortings);
        $view->assign('criteria', $criteria);
        $view->assign('pageSizes', explode('|', Shopware()->Container()->get('config')->get('numberArticlesToShow')));
    }

    protected function applyThemeConfig(&$view) {
        $inheritance = Shopware()->Container()->get('theme_inheritance');

        /** @var \Shopware\Models\Shop\Shop $shop */
        $shop = Shopware()->Container()->get('Shop');

        $config = $inheritance->buildConfig($shop->getTemplate(), $shop, false);

        Shopware()->Container()->get('template')->addPluginsDir(
            $inheritance->getSmartyDirectories(
                $shop->getTemplate()
            )
        );
        $view->assign('theme', $config);
    }

    protected function assignSubRenderings(&$view, $viewElement) {
        $subRenderings = array();
        if(isset($viewElement['subRenderings'][0]['rendering']['visualElements'])) {
            $subRenderings = $viewElement['subRenderings'][0]['rendering']['visualElements'];
        }
        $view->assign('bxSubRenderings', $subRenderings);
        $view->assign('bxRender', $this);
    }

    protected function getViewElementProduct($visualElement, $index) {

        $product = false;
        $variant_index = 0;
        foreach ($visualElement['parameters'] as $parameter) {
            if($parameter['name'] == 'variant') {
                $variant_index = reset($parameter['values']);
            } else if($parameter['name'] == 'index') {
                $index = reset($parameter['values']);
            }
        }
        $choice_id = $this->p13Helper->getResponse()->getChoiceIdFromVariantIndex($variant_index);
        $ids = $this->p13Helper->getHitFieldValues('products_ordernumber', 'product', $choice_id);
        $entity_id = isset($ids[$index]) ? $ids[$index] : null;
        if($entity_id) {
            $collection = $this->resourceManager->getResource($variant_index, 'collection');
            if(!is_null($collection)) {
                foreach ($collection as $product) {
                    if($product['ordernumber'] == $entity_id){
                        return $product;
                    }
                }
            }

            $product = $this->resourceManager->getResource($entity_id, 'product');
            if(is_null($product)) {
                $product = reset($this->p13Helper->getLocalArticles([$entity_id]));
                $this->resourceManager->setResource($product, $entity_id, 'product');
            }
        }
        return $product;
    }

    protected function prepareCollection($visualElement) {
        $variant_index = 0;
        foreach ($visualElement['parameters'] as $parameter) {
            if($parameter['name'] == 'variant') {
                $variant_index = reset($parameter['values']);
                break;
            }
        }
        $collection = $this->resourceManager->getResource($variant_index, 'collection');
        if(is_null($collection)) {
            $choice_id = $this->p13Helper->getResponse()->getChoiceIdFromVariantIndex($variant_index);
            $ids = $this->p13Helper->getHitFieldValues('products_ordernumber', 'product', $choice_id);
            $collection = $this->p13Helper->getLocalArticles($ids);
            $this->resourceManager->setResource($collection, $variant_index, 'collection');
        }
    }

    public function getLocalizedValue($values) {
        return $this->p13Helper->getResponse()->getLocalizedValue($values);
    }
}