<?php

use Boxalino\Helper\BxData;
use Boxalino\Helper\P13NHelper;

class Shopware_Controllers_Frontend_RecommendationSlider extends Enlight_Controller_Action {

    /**
     * @var sMarketing
     */
    protected $marketingModule;

    /**
     * @var array
     */
    private $_productRecommendations = array(
        'sRelatedArticles' => 'boxalino_accessories_recommendation',
        'sSimilarArticles' => 'boxalino_similar_recommendation',
        'boughtArticles' => 'boxalino_complementary_recommendation',
        'viewedArticles' => 'boxalino_related_recommendation'
    );

    /**
     * @var Shopware_Components_Config
     */
    protected $config;

    public function indexAction() {
        $this->productStreamSliderRecommendationsAction();
    }

    public function detailAction() {
        $choiceIds = array();
        $this->config = Shopware()->Config();
        $id = $this->request->getParam('articleId');
        if($id == 'sCategory') {
            $exception = new \Exception("Request with empty parameters from : " . $_SERVER['HTTP_REFERER']);
            Shopware()->Plugins()->Frontend()->Boxalino()->logException($exception, __FUNCTION__, $this->request->getRequestUri());
            return;
        } else if($id == '') {
            return;
        }
        $categoryId = $this->request->getParam('sCategory');
        $number = $this->Request()->getParam('number', null);
        $selection = $this->Request()->getParam('group', array());

        $bxData = BxData::instance();
        if (!$bxData->isValidCategory($categoryId)) {
            $categoryId = 0;
        }
        $this->config->offsetSet('similarLimit', 0);

        try{
            $sArticles = Shopware()->Modules()->Articles()->sGetArticleById(
                $id,
                $categoryId,
                $number,
                $selection
            );
        }catch(\Exception $exception) {
            Shopware()->Plugins()->Frontend()->Boxalino()->logException($exception, __FUNCTION__, $this->request->getRequestUri());
            $sArticles = [];
        }
        $boughtArticles = [];
        $viewedArticles = [];
        $sRelatedArticles = isset($sArticles['sRelatedArticles']) ? $sArticles['sRelatedArticles'] : [];
        $sSimilarArticles = isset($sArticles['sSimilarArticles']) ? $sArticles['sSimilarArticles'] : [];

        $helper = P13NHelper::instance();
        $helper->setRequest($this->Request());
        foreach ($this->_productRecommendations as $var_name => $recommendation) {
            if ($this->config->get("{$recommendation}_enabled")) {
                $choiceId = $this->config->get("{$recommendation}_name");
                $max = $this->config->get("{$recommendation}_max");
                $min = $this->config->get("{$recommendation}_min");
                $excludes = array();
                if ($var_name == 'sRelatedArticles' ||$var_name == 'sSimilarArticles') {
                    foreach ($$var_name as $article) {
                        $excludes[] = $article['articleID'];
                    }
                }
                $helper->getRecommendation($choiceId, $max, $min, 0, $id, 'product', false, $excludes);
                $choiceIds[$recommendation] = $choiceId;
            }
        }

        foreach ($this->_productRecommendations as $var_name => $recommendation) {
            if (isset($choiceIds[$recommendation])) {
                $hitIds = $helper->getRecommendation($choiceIds[$recommendation]);
                $articles = array_merge($$var_name, $helper->getLocalArticles($hitIds));
                $sArticles[$var_name] = $articles;
            }
        }

        $path = Shopware()->Plugins()->Frontend()->Boxalino()->Path();
        $this->View()->addTemplateDir($path . 'Resources/views/emotion/');
        $this->View()->loadTemplate('frontend/plugins/boxalino/detail/recommendation.tpl');
        $this->View()->assign('sArticle', $sArticles);
    }

    /**
     * Recommendation for boxalino emotion slider
     */
    public function productStreamSliderRecommendationsAction() {
        if ($_REQUEST['dev_bx_debug'] == 'true') {
            $t1 = microtime(true);
        }
        $helper = P13NHelper::instance();
        $helper->setRequest($this->request);
        $choiceId = $this->Request()->getQuery('bxChoiceId');
        $count = $this->Request()->getQuery('bxCount');
        $context = $this->Request()->getQuery('category_id');
        $context = Shopware()->Shop()->getCategory()->getId() == $context ? null : $context;
        $helper->getRecommendation($choiceId, $count, $count, 0, $context, 'category', false);
        if ($_REQUEST['dev_bx_debug'] == 'true') {
            $helper->addNotification("Recommendation Slider before response took: " . (microtime(true) - $t1) * 1000 . "ms.");
        }
        if ($_REQUEST['dev_bx_debug'] == 'true') {
            $t2 = microtime(true);
        }
        $hitsIds = $helper->getRecommendation($choiceId);
        if ($_REQUEST['dev_bx_debug'] == 'true') {
            $helper->addNotification("Recommendation Slider response took: " . (microtime(true) - $t2) * 1000 . "ms.");
        }
        if ($_REQUEST['dev_bx_debug'] == 'true') {
            $t3 = microtime(true);
        }

        $path = Shopware()->Plugins()->Frontend()->Boxalino()->Path();
        $this->View()->addTemplateDir($path . 'Resources/views/emotion/');
        $this->View()->loadTemplate('frontend/plugins/boxalino/recommendation_slider/product_stream_slider_recommendations.tpl');

        if(!empty($hitsIds)) {
            if ($_REQUEST['dev_bx_debug'] == 'true') {
                $t4 = microtime(true);
            }
            $this->View()->assign('articles', $helper->getLocalArticles($hitsIds));
            $this->View()->assign('title', $helper->getSearchResultTitle($choiceId));
            if ($_REQUEST['dev_bx_debug'] == 'true') {
                $helper->addNotification("Recommendation Slider getLocalArticles took: " . (microtime(true) - $t4) * 1000 . "ms. IDS: " .json_encode($hitsIds));
            }
            $this->View()->assign('productBoxLayout', "emotion");
        }
        if ($_REQUEST['dev_bx_debug'] == 'true') {
            $helper->addNotification("Recommendation Slider after response took: " . (microtime(true) - $t3) * 1000 . "ms.");
            $helper->addNotification("Recommendation Slider took in total:" . (microtime(true) - $t1) * 1000 . "ms.");
            $helper->callNotification(true);
        }
    }



    public function portfolioRecommendationAction() {

        try{
            $helper = P13NHelper::instance();
            $helper->setRequest($this->request);
            $choiceId = $this->Request()->getQuery('bxChoiceId');
            $count = $this->Request()->getQuery('bxCount');
            $context = $this->Request()->getQuery('category_id');
            $account_id = $this->Request()->getQuery('account_id');
            $context = Shopware()->Shop()->getCategory()->getId() == $context ? null : $context;
            $refer = $this->Request()->getParam('category');
            if($account_id) {
                $contextParam = array('_system_customerid' => $account_id);
            } else {
                $contextParam = array('_system_customerid' => $helper->getCustomerID());
            }
            $helper->getRecommendation($choiceId, $count, $count, 0, $context, 'category', false, array(), false, $contextParam, true);
            $hitsIds = $helper->getRecommendation($choiceId);
            $articles = $helper->getLocalArticles($hitsIds);
            if($choiceId == 'rebuy') {

                $purchaseDates = $helper->getRecommendationHitFieldValues($choiceId, 'purchase_date');
                foreach ($articles as $i => $article) {
                    $add = array_shift($purchaseDates);
                    $date = reset($add['purchase_date']);
                    if(getdate(strtotime($date))['year'] != 1970) {
                        $article['bxTransactionDate'] = $date;
                        $articles[$i] = $article;
                    }
                }
            }
            $this->View()->loadTemplate('frontend/_includes/product_slider_items.tpl');
            $path = Shopware()->Plugins()->Frontend()->Boxalino()->Path();
            $this->View()->addTemplateDir($path . 'Resources/views/emotion/');
            $this->View()->extendsTemplate('frontend/plugins/boxalino/listing/product-box/box-emotion.tpl');
            $this->View()->assign('articles', $articles);
            $this->View()->assign('withAddToBasket', true);
            $this->View()->assign('productBoxLayout', "emotion");
        } catch(\Exception $exception) {
            Shopware()->Plugins()->Frontend()->Boxalino()->logException($exception, __FUNCTION__, $this->request->getRequestUri());
        }

    }


    public function blogRecommendationAction() {

        try{
            $helper = P13NHelper::instance();
            $bxData = BxData::instance();
            $choiceId = 'read_portfolio';
            $min = 10;
            $max = 10;
            $context = $this->Request()->getQuery('category_label');
            $helper->getRecommendation($choiceId, $max, $min, 0, $context, 'portfolio_blog', false, array(), true);
            $fields = ['products_blog_title', 'products_blog_id', 'products_blog_category_id', 'products_blog_short_description', 'products_blog_media_id'];
            $blogs = $helper->getRecommendationHitFieldValues($choiceId, $fields);
            $blogArticles = $bxData->transformBlog($blogs);
            $this->View()->loadTemplate('frontend/_includes/product_slider_items.tpl');
            $path = Shopware()->Plugins()->Frontend()->Boxalino()->Path();
            $this->View()->addTemplateDir($path . 'Resources/views/emotion/');
            $this->View()->extendsTemplate('frontend/plugins/boxalino/_includes/product_slider_item.tpl');
            $this->View()->assign('articles', $blogArticles);
            $this->View()->assign('bxBlogRecommendation', true);
        } catch(\Exception $exception) {
            Shopware()->Plugins()->Frontend()->Boxalino()->logException($exception, __FUNCTION__, $this->request->getRequestUri());
        }
    }

    public function detailBlogRecommendationAction()
    {
        $helper = P13NHelper::instance();
        $bxData = BxData::instance();
        $choiceId = Shopware()->Config()->get('boxalino_detail_blog_recommendation_name');
        $min = Shopware()->Config()->get('boxalino_detail_blog_recommendation_min');
        $max = Shopware()->Config()->get('boxalino_detail_blog_recommendation_max');
        $context = $this->Request()->getQuery('articleId');

        try
        {
            $relatedBlogs = $bxData->getRelatedBlogs($context);
            $contextParams = ["bx_{$choiceId}_$context" => $relatedBlogs];
            $helper->getRecommendation($choiceId, $max, $min, 0, $context, 'product', false, array(), true, $contextParams);
            $fields = ['products_blog_title', 'products_blog_id', 'products_blog_category_id', 'products_blog_short_description', 'products_blog_media_id'];
            $blogs = $helper->getRecommendationHitFieldValues($choiceId, $fields);
            $articles = $bxData->transformBlog($blogs);
            $this->View()->loadTemplate('frontend/plugins/boxalino/detail/ajax_blog_rec.tpl');
            $path = Shopware()->Plugins()->Frontend()->Boxalino()->Path();
            $this->View()->addTemplateDir($path . 'Resources/views/emotion/');
            $this->View()->extendsTemplate('frontend/plugins/boxalino/_includes/product_slider_item.tpl');
            $this->View()->assign('articles', $articles);
            $this->View()->assign('bxBlogRecommendation', true);
            $this->View()->assign('fixedImage', true);
            $this->View()->assign('productBoxLayout', 'emotion');
            $this->View()->assign('Data', ['article_slider_arrows' => 1]);
            $this->View()->assign('sBlogTitle', $helper->getSearchResultTitle($choiceId));
        } catch(\Exception $exception) {
            Shopware()->Plugins()->Frontend()->Boxalino()->logException($exception, __FUNCTION__, $this->request->getRequestUri());
        }
    }
}