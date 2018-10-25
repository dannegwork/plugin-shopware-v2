<?php
namespace Boxalino\Helper;

use Shopware\Components\DependencyInjection\Container;
use Shopware_Components_Config;

/**
 * Class \Boxalino\Helper\BxData
 */
class BxData {

    /**
     * @var \Boxalino\Helper\BxData
     */
    protected static $instance = null;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var Shopware_Components_Config
     */
    protected $config;

    /**
     * \Boxalino\Helper\BxData constructor.
     */
    public function __construct()
    {
        $this->container = Shopware()->Container();
        $this->config = Shopware()->Config();
    }

    /**
     * @return \Boxalino\Helper\BxData
     */
    public static function instance() {
        if (self::$instance == null)
            self::$instance = new \Boxalino\Helper\BxData();
        return self::$instance;
    }

    /**
     * @return Shopware_Components_Config
     */
    public function Config() {
        return $this->config;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function get($name) {
        return $this->container->get($name);
    }

    public function getFacetConfig($facets, $request) {
        $snippetManager = Shopware()->Snippets()->getNamespace('frontend/listing/facet_labels');
        $options = [];
        $mapper = $this->get('query_alias_mapper');
        $params = $request->getParams();
        foreach ($facets as $fieldName => $facet) {

            switch ($fieldName) {
                case 'price':
                    $min = isset($params[$mapper->getShortAlias('priceMin')]) ? $params[$mapper->getShortAlias('priceMin')] : "*";
                    $max = isset($params[$mapper->getShortAlias('priceMax')]) ? $params[$mapper->getShortAlias('priceMax')] : "*";

                    $value = ["{$min}-{$max}"];
                    $options['discountedPrice'] = [
                        'value' => $value,
                        'type' => 'ranged',
                        'bounds' => true,
                        'label' => $snippetManager->get('price', 'Preis')
                    ];
                    break;
                case 'category':
                    $options['category'] = [
                        'label' => $snippetManager->get('category', 'Kategorie')
                    ];
                    $id = null;
                    if(version_compare(Shopware::VERSION, '5.3.0', '<')) {
                        if(isset($params[$mapper->getShortAlias('sCategory')])){
                            $id = $params[$mapper->getShortAlias('sCategory')];
                        } else if (isset($params['sCategory'])){
                            $id = $params['sCategory'];
                        } else {
                            $id = Shopware()->Shop()->getCategory()->getId();
                        }
                    } else {
                        if (isset($params[$mapper->getShortAlias('categoryFilter')])){
                            $id = $params[$mapper->getShortAlias('categoryFilter')];
                        } else if (isset($params['categoryFilter'])){
                            $id = $params['categoryFilter'];
                        } else {
                            $id = Shopware()->Shop()->getCategory()->getId();
                        }
                    }
                    if(!is_null($id)) {
                        $ids = explode('|', $id);
                        foreach ($ids as $i) {
                            $options['category']['value'][] = $i;
                        }
                    }
                    break;
                case 'property':
                    $options = array_merge($options, $this->getAllFilterableOptions());
                    $id = null;
                    if( isset($params[$mapper->getShortAlias('sFilterProperties')])) {
                        $id = $params[$mapper->getShortAlias('sFilterProperties')];
                    } else if($params['sFilterProperties']) {
                        $id = $params['sFilterProperties'];
                    }
                    if($id) {
                        $ids = explode('|', $id);
                        foreach ($ids as $i) {
                            $option = $this->getOptionFromValueId($i);
                            $name = trim($option['value']);
                            $select = "{$name}_bx_{$i}";
                            $bxFieldName = 'products_optionID_mapped_' . $option['id'];
                            $options[$bxFieldName]['value'][] = $select;
                        }

                    }
                    break;
                case 'manufacturer':
                    $id = isset($params[$mapper->getShortAlias('sSupplier')]) ? $params[$mapper->getShortAlias('sSupplier')] : null;
                    $options['products_brand']['label'] = $snippetManager->get('manufacturer', 'Hersteller');
                    if($id) {
                        $ids = explode('|', $id);
                        foreach ($ids as $i) {
                            $name = trim($this->getSupplierName($i));
                            $options['products_brand']['value'][] = $name;
                        }
                    }

                    break;
                case 'shipping_free':
                    $freeShipping = isset($params[$mapper->getShortAlias('shippingFree')]) ? $params[$mapper->getShortAlias('shippingFree')] : null;
                    $options['products_shippingfree']['label'] = $snippetManager->get('shipping_free', 'Versandkostenfrei');
                    if($freeShipping) {
                        $options['products_shippingfree']['value'] = [1];
                    }
                    break;
                case 'immediate_delivery':
                    $immediate_delivery = isset($params[$mapper->getShortAlias('immediateDelivery')]) ? $params[$mapper->getShortAlias('immediateDelivery')] : null;
                    $options['products_bx_purchasable']['label'] = $snippetManager->get('immediate_delivery', 'Sofort lieferbar');
                    if($immediate_delivery) {
                        $options['products_bx_purchasable']['value'] = [1];
                    }
                    break;
                case 'vote_average':
                    $top = (version_compare(Shopware::VERSION, '5.3.0', '<')) ? 5 : 4;
                    $vote = isset($params['rating']) ? range($params['rating'], $top) : null;
                    $options['di_rating']['label'] = $snippetManager->get('vote_average', 'Bewertung');
                    if($vote) {
                        $options['di_rating']['value'] = $vote;
                    }
                    break;
                default:
                    break;
            }
        }
        return $options;
    }

    protected function getSupplierName($supplier) {
        $supplier = $this->get('dbal_connection')->fetchColumn(
            'SELECT name FROM s_articles_supplier WHERE id = :id',
            ['id' => $supplier]
        );

        if ($supplier) {
            return $supplier;
        }

        return null;
    }

    protected function getOptionFromValueId($id){
        $option = array();
        $db = Shopware()->Db();
        $sql = $db->select()->from(array('f_v' => 's_filter_values'), array('value'))
            ->join(array('f_o' => 's_filter_options'), 'f_v.optionID = f_o.id', array('id','name'))
            ->where('f_v.id = ?', $id);
        $stmt = $db->query($sql);
        if($stmt->rowCount()) {
            $option = $stmt->fetch();
        }
        return $option;
    }

    protected function useTranslation($shop_id, $objectType){
        $db = Shopware()->Db();
        $sql = $db->select()->from(array('c_t' => 's_core_translations'))
            ->where('c_t.objectlanguage = ?', $shop_id)
            ->where('c_t.objecttype = ?', $objectType);
        $stmt = $db->query($sql);
        $use = $stmt->rowCount() == 0 ? false : true;
        return $use;
    }

    public function getShopId(){
        return $shop_id = $this->Config()->get('boxalino_overwrite_shop') != '' ? (int) $this->Config()->get('boxalino_overwrite_shop') : Shopware()->Shop()->getId();
    }

    protected function getAllFilterableOptions() {
        $options = array();
        $db = Shopware()->Db();
        $sql = $db->select()->from(array('f_o' => 's_filter_options'))
            ->where('f_o.filterable = 1');
        $shop_id = $this->getShopId();
        $useTranslation = $this->useTranslation($shop_id, 'propertyoption');

        if($useTranslation) {
            $sql
                ->joinLeft(array('t' => 's_core_translations'),
                    'f_o.id = t.objectkey AND t.objecttype = ' . $db->quote('propertyoption') . ' AND t.objectlanguage = ' . $shop_id,
                    array('objectdata'));
        }
        $stmt = $db->query($sql);

        if($stmt->rowCount()) {
            while($row = $stmt->fetch()){
                if($useTranslation && isset($row['objectdata'])) {
                    $translation = unserialize($row['objectdata']);
                    $row['name'] = isset($translation['optionName']) && $translation['optionName'] != '' ?
                        $translation['optionName'] : $row['name'];
                }
                $options['products_optionID_mapped_' . $row['id']] = ['label' => trim($row['name'])];
            }
        }
        return $options;
    }

    public function getSortOrder(\Shopware\Bundle\SearchBundle\Criteria $criteria, $default_sort = null, $listing = false) {

        $sort = current($criteria->getSortings());
        $dir = null;
        $additionalSort = null;
        if($listing && is_null($default_sort) && $this->Config()->get('boxalino_navigation_sorting')){
            return array();
        }

        switch ($sort->getName()) {
            case 'popularity':
                $field = 'products_sales';
                break;
            case 'prices':
                $field = 'products_bx_grouped_price';
                break;
            case 'product_name':
                $field = 'title';
                break;
            case 'release_date':
                $field = 'products_datum';
                $additionalSort = true;
                break;
            default:
                if ($listing == true) {
                    $default_sort = is_null($default_sort) ? $this->getDefaultSort() : $default_sort;
                    switch ($default_sort) {
                        case 1:
                            $field = 'products_datum';
                            $additionalSort = true;
                            break 2;
                        case 2:
                            $field = 'products_sales';
                            break 2;
                        case 3:
                        case 4:
                            if ($default_sort == 3) {
                                $dir = false;
                            }
                            $field = 'products_bx_grouped_price';
                            break 2;
                        case 5:
                        case 6:
                            if ($default_sort == 5) {
                                $dir = false;
                            }
                            $field = 'title';
                            break 2;
                        default:
                            if ($this->Config()->get('boxalino_navigation_sorting') == false) {
                                $field = 'products_datum';
                                $additionalSort = true;
                                break 2;
                            }
                            break;
                    }
                }
                return array();
        }
        $sortReturn[] = array(
            'field' => $field,
            'reverse' => (is_null($dir) ? $sort->getDirection() == \Shopware\Bundle\SearchBundle\SortingInterface::SORT_DESC : $dir)
        );
        if($additionalSort) {
            $sortReturn[] = array(
                'field' => 'products_changetime',
                'reverse' => (is_null($dir) ? $sort->getDirection() == \Shopware\Bundle\SearchBundle\SortingInterface::SORT_DESC : $dir)
            );
        }
        return $sortReturn;
    }

    protected function getDefaultSort(){
        $db = Shopware()->Db();
        $sql = $db->select()
            ->from(array('c_e' => 's_core_config_elements', array('c_v.value')))
            ->join(array('c_v' => 's_core_config_values'), 'c_v.element_id = c_e.id')
            ->where("name = ?", "defaultListingSorting");
        $result = $db->fetchRow($sql);
        return isset($result) ? unserialize($result['value']) : null;

    }

    public function getLocalArticles($ids) {
        if (empty($ids)) {
            return array();
        }
        $unsortedArticles = $this->container->get('legacy_struct_converter')->convertListProductStructList(
            $this->container->get('shopware_storefront.list_product_service')->getList(
                $ids,
                $this->container->get('shopware_storefront.context_service')->getProductContext()
            )
        );
        $articles = array();
        foreach ($ids as $id) {
            if(isset($unsortedArticles[$id])){
                $articles[$unsortedArticles[$id]['ordernumber']] = $unsortedArticles[$id];
            }
        }
        return $articles;
    }

    public function useValuesAsKeys($array){
        return array_combine(array_keys(array_flip($array)),$array);
    }

    public function getRelatedBlogs($articleId) {
        $relatedBlogs = array();
        if($articleId != '') {
            $db = Shopware()->Db();
            $sql = $db->select()->from(array('a_b' => 's_blog_assigned_articles'))
                ->where('a_b.article_id = ?', (int) $articleId);
            $stmt = $db->query($sql);
            if($stmt->rowCount()) {
                while($row = $stmt->fetch()) {
                    $relatedBlogs[] = "blog_{$row['blog_id']}";
                }
            }
        }
        return $relatedBlogs;
    }

    public function findStreamIdByCategoryId($categoryId)
    {
        $streamId = $this->container->get('dbal_connection')->fetchColumn(
            'SELECT stream_id FROM s_categories WHERE id = :id',
            ['id' => $categoryId]
        );

        if ($streamId) {
            return (int)$streamId;
        }

        return null;
    }

    public function transformBlog($blogs) {
        $blogArticles = array();
        foreach ($blogs as $blog) {
            $article = array();
            foreach ($blog as $fieldName => $value) {
                $value = reset($value);
                $field = substr($fieldName, 14);
                if($field == 'id') {
                    $value = substr($value, 5);
                }
                if($field == 'short_description') {
                    $field = 'shortDescription';
                }
                $article[$field] = $value;
            }
            $blogArticles[$article['id']] = $article;
        }
        $blogArticles = $this->loadBlogMedia($blogArticles);
        return $blogArticles;
    }

    public function loadBlogMedia($blogArticles) {
        if(empty($blogArticles)) {
            return $blogArticles;
        }
        $mediaIds = array_map(function ($blogArticle) {
            if (isset($blogArticle['media_id']) && $blogArticle['media_id'] != '') {
                return $blogArticle['media_id'];
            }
        }, $blogArticles);

        $context = $this->container->get('shopware_storefront.context_service')->getShopContext();
        $medias = $this->container->get('shopware_storefront.media_service')->getList($mediaIds, $context);


        foreach ($blogArticles as $key => $blogArticle) {

            $mediaId = null;
            if (isset($blogArticle['media_id']) && $blogArticle['media_id'] != '') {
                $mediaId = $blogArticle['media_id'];
            }
            if (!isset($medias[$mediaId])) {
                continue;
            }

            $media = $medias[$mediaId];
            $media = $this->container->get('legacy_struct_converter')->convertMediaStruct($media);
            $blogArticles[$key]['media'] = $media;
        }
        return $blogArticles;
    }

    public function enhanceBlogArticles($blogArticles) {
        $mediaIds = array_map(function ($blogArticle) {
            if (isset($blogArticle['media']) && $blogArticle['media'][0]['mediaId']) {
                return $blogArticle['media'][0]['mediaId'];
            }
        }, $blogArticles);

        $context = $this->container->get('shopware_storefront.context_service')->getShopContext();
        $medias = $this->container->get('shopware_storefront.media_service')->getList($mediaIds, $context);


        foreach ($blogArticles as $key => $blogArticle) {
            //adding number of comments to the blog article
            $blogArticles[$key]["numberOfComments"] = count($blogArticle["comments"]);

            //adding thumbnails to the blog article
            if (empty($blogArticle["media"][0]['mediaId'])) {
                continue;
            }

            $mediaId = $blogArticle["media"][0]['mediaId'];

            if (!isset($medias[$mediaId])) {
                continue;
            }

            /**@var $media \Shopware\Bundle\StoreFrontBundle\Struct\Media*/
            $media = $medias[$mediaId];
            $media = $this->container->get('legacy_struct_converter')->convertMediaStruct($media);

            if (Shopware()->Shop()->getTemplate()->getVersion() < 3) {
                $blogArticles[$key]["preview"]["thumbNails"] = array_column($media['thumbnails'], 'source');
            } else {
                $blogArticles[$key]['media'] = $media;
            }
        }
        return $blogArticles;
    }

    public function isValidCategory($categoryId) {
        $defaultShopCategoryId = Shopware()->Shop()->getCategory()->getId();

        /**@var $repository \Shopware\Models\Category\Repository*/
        $repository = Shopware()->Models()->getRepository('Shopware\Models\Category\Category');
        $categoryPath = $repository->getPathById($categoryId);

        if (!$categoryPath) {
            return true;
        }

        if (!array_key_exists($defaultShopCategoryId, $categoryPath)) {
            return false;
        }

        return true;
    }

}