<?php

class Shopware_Plugins_Frontend_Boxalino_DataExporter {

    protected $request;
    protected $manager;

    private static $instance = null;

    protected $propertyDescriptions = array();

    protected $dirPath;
    protected $db;
    protected $log;
    protected $delta;
    protected $deltaLast;
    protected $fileHandle;

    protected $deltaIds = array();
    protected $_config;
    protected $bxData;
    protected $_attributes = array();
    protected $config = array();
    protected $locales = array();
    protected $languages = array();
    protected $rootCategories = array();

    protected $translationFields = array(
        'name',
        'keywords',
        'description',
        'description_long',
        'attr1',
        'attr2',
        'attr3',
        'attr4',
        'attr5'
    );

    /**
     * constructor
     *
     * @param string $dirPath
     * @param bool   $delta
     */
    public function __construct($dirPath, $delta = false) {

        $this->delta = $delta;
        $this->dirPath = $dirPath;
        $this->db = Shopware()->Db();
        $this->log = Shopware()->PluginLogger();
        $libPath = __DIR__ . '/lib';
        require_once($libPath . '/BxClient.php');
        \com\boxalino\bxclient\v1\BxClient::LOAD_CLASSES($libPath);
    }

    public static function instance($dir, $delta = false) {

        if (self::$instance == null){
            self::$instance = new Shopware_Plugins_Frontend_Boxalino_DataExporter($dir, $delta);
        }
        return self::$instance;
    }

    /**
     * run the exporter
     *
     * iterates over all shops and exports them according to their settings
     *
     * @return array
     */
    public function run() {

        set_time_limit(3600);
        $data = array();
        $type = $this->delta ? 'delta' : 'full';
        try {
            $this->log->info("BxIndexLog: Start of boxalino {$type} data sync.");
            $this->_config = new Shopware_Plugins_Frontend_Boxalino_Helper_BxIndexConfig();

            foreach ($this->_config->getAccounts() as $account) {

                $this->log->info("BxIndexLog: Exporting store ID : {$this->_config->getAccountStoreId($account)}");
                $this->log->info("BxIndexLog: Initialize files on account: {$account}");
                $files = new Shopware_Plugins_Frontend_Boxalino_Helper_BxFiles($this->dirPath, $account, $type);

                $bxClient = new \com\boxalino\bxclient\v1\BxClient($account, $this->_config->getAccountPassword($account), "");
                $this->bxData = new \com\boxalino\bxclient\v1\BxData($bxClient, $this->_config->getAccountLanguages($account), $this->_config->isAccountDev($account), false);
                $this->log->info("BxIndexLog: verify credentials for account: " . $account);
                try {
                    $this->bxData->verifyCredentials();
                } catch (\Exception $e){
                    $this->log->error("BxIndexException: {$e->getMessage()}");
                    throw $e;
                }

                $this->log->info('BxIndexLog: Preparing the attributes and category data for each language of the account: ' . $account);

                $this->log->info("BxIndexLog: Preparing products.");
                $exportProducts = $this->exportProducts($account, $files);
                if ($type == 'full') {
                    if ($this->_config->isCustomersExportEnabled($account)) {
                        $this->log->info("BxIndexLog: Preparing customers.");
                        $this->exportCustomers($account, $files);
                    }

                    if ($this->_config->isTransactionsExportEnabled($account)) {
                        $this->log->info("BxIndexLog: Preparing transactions.");
                        $this->exportTransactions($account, $files);
                    }
                }

                if (!$exportProducts) {
                    $this->log->info('BxIndexLog: No Products found for account: ' . $account);
                    $this->log->info('BxIndexLog: Finished account: ' . $account);
                } else {
                    if ($type == 'full') {

                        $this->log->info('BxIndexLog: Prepare the final files: ' . $account);
                        $this->log->info('BxIndexLog: Prepare XML configuration file: ' . $account);

                        try {
                            $this->log->info('BxIndexLog: Push the XML configuration file to the Data Indexing server for account: ' . $account);
                            $this->bxData->pushDataSpecifications();
                        } catch (\Exception $e) {
                            $value = @json_decode($e->getMessage(), true);
                            if (isset($value['error_type_number']) && $value['error_type_number'] == 3) {
                                $this->log->info('BxIndexLog: Try to push the XML file a second time, error 3 happens always at the very first time but not after: ' . $account);
                                $this->bxData->pushDataSpecifications();
                            } else {
                                throw $e;
                            }
                        }

                        $this->log->info('BxIndexLog: Publish the configuration changes from the owner for account: ' . $account);
                        $publish = $this->_config->publishConfigurationChanges($account);
                        $changes = $this->bxData->publishChanges($publish);
                        $data['token'] = $changes['token'];
                        if (sizeof($changes['changes']) > 0 && !$publish) {
                            $this->log->info("BxIndexLog: changes in configuration detected but not published as publish configuration automatically option has not been activated for account: " . $account);
                        }
                        $this->log->info('BxIndexLog: Push the Zip data file to the Data Indexing server for account: ' . $account);

                    }
                    $this->log->info('BxIndexLog: pushing to DI');
                    try {
                        $this->bxData->pushData();
                    } catch (\Exception $e){
                        $this->log->info("BxIndexLog: pushData failed with exception: " . $e->getMessage());
                    }
                    $this->log->info('BxIndexLog: Finished account: ' . $account);
                }
            }
        } catch(\Exception $e) {
            $this->log->info("BxIndexLog: failed with exception: " . $e->getMessage());
        }
        $this->log->info("BxIndexLog: End of boxalino $type data sync ");
        $this->updateExportTable();
        return var_export($data);
    }

    private function exportProducts($account, $files) {

        $this->log->info("BxIndexLog: Preparing products - main.");
        $export_products = $this->exportMainProducts($account, $files);
        $this->log->info("BxIndexLog: Finished products - main.");
        if ($export_products) {
            $this->log->info("BxIndexLog: Preparing products - categories.");
            $this->exportItemCategories($account, $files);
            $this->log->info("BxIndexLog: Finished products - categories.");
            $this->log->info("BxIndexLog: Preparing products - translations.");
            $this->exportItemTranslationFields($account, $files);
            $this->log->info("BxIndexLog: Finished products - translations.");
            $this->log->info("BxIndexLog: Preparing products - brands.");
            $this->exportItemBrands($files);
            $this->log->info("BxIndexLog: Finished products - brands.");
            $this->log->info("BxIndexLog: Preparing products - facets.");
            $this->exportItemFacets($account, $files);
            $this->log->info("BxIndexLog: Finished products - facets.");
            $this->log->info("BxIndexLog: Preparing products - price.");
            $this->exportItemPrices($files);
            $this->log->info("BxIndexLog: Finished products - price.");
            if ($this->_config->exportProductImages($account)) {
                $this->log->info("BxIndexLog: Preparing products - image.");
                $this->exportItemImages($account, $files);
                $this->log->info("BxIndexLog: Finished products - image.");
            }
            if ($this->_config->exportProductUrl($account)) {
                $this->log->info("BxIndexLog: Preparing products - url.");
                $this->exportItemUrls($account, $files);
                $this->log->info("BxIndexLog: Finished products - url.");
            }
            $this->log->info("BxIndexLog: Preparing products - blogs.");
            $this->exportItemBlogs($account, $files);
            $this->log->info("BxIndexLog: Finished products - blogs.");
        }
        return $export_products;
    }

    private function exportItemPrices($files) {

        $db = $this->db;
        $sql = $db->select()
            ->from(array('a' => 's_articles'),array()
            )
            ->join(
                array('d' => 's_articles_details'),
                $this->qi('d.articleID') . ' = ' . $this->qi('a.id') . ' AND ' .
                $this->qi('d.kind') . ' <> ' . $db->quote(3),
                array('d.id')
            )
            ->join(array('a_p' => 's_articles_prices'), 'a_p.articledetailsID = d.id', array('price', 'pseudoprice'))
            ->join(array('c_c' => 's_core_customergroups'), 'c_c.groupkey = a_p.pricegroup',array())
            ->join(array('c_t' => 's_core_tax'), 'c_t.id = a.taxID', array('tax'))
            ->where('a_p.pricegroup = ?', 'EK')
            ->where('a_p.from = ?', 1)
            ->where($this->qi('a.active') . ' = ?', 1);
        if ($this->delta) {
            $sql->where('a.id IN(?)', $this->deltaIds);
        }

        $stmt = $db->query($sql);
        while ($row = $stmt->fetch()) {

            $taxFactor = ((floatval($row['tax']) + 100.0) /100);
            $pseudo = floatval($row['pseudoprice']) * $taxFactor;
            $discount = floatval($row['price']) * $taxFactor;
            $price = $pseudo > $discount ? $pseudo : $discount;
            $data[$row['id']] = array("id" => $row['id'], "price" => number_format($price,2), "discounted" => number_format($discount,2));

        }
        $data = array_merge(array(array_keys(end($data))), $data);
        $files->savepartToCsv('product_price.csv', $data);
        $sourceKey = $this->bxData->addCSVItemFile($files->getPath('product_price.csv'), 'id');
        $this->bxData->addSourceDiscountedPriceField($sourceKey, 'discounted');
        $this->bxData->addSourceListPriceField($sourceKey, 'price');
    }

    /**
     * @param $account
     * @param $files
     */
    private function exportItemFacets($account, $files) {

        $db = $this->db;
        $option_values = array();
        $languages = $this->_config->getAccountLanguages($account);

        $sql = $db->select()->from(array('f_o' => 's_filter_options'));
        $facets = $db->fetchAll($sql);
        
        foreach ($facets as $facet) {

            $facet_id = $facet['id'];
//            $facet_name = preg_replace('/[\{\}\(\)]/', '',  trim($facet['name']));
//            $facet_name = str_replace(' ', '_', $facet_name);
            $facet_name = "option_{$facet_id}";// . preg_replace('/[^äöü ÄÖÜ A-Za-z0-9\_\&]/', '_', strtolower($facet_name));

            $data = array();
            $localized_columns = array();
            foreach ($languages as $shop_id => $language) {
                $localized_columns[$language] = "value_{$language}";
                $sql = $db->select()
                    ->from(array('f_v' => 's_filter_values'))
                    ->joinLeft(
                        array('c_t' => 's_core_translations'),
                        'c_t.objectkey = f_v.id AND c_t.objecttype = \'propertyvalue\' AND c_t.objectlanguage = ' . $shop_id,
                        array('objectdata')
                    )
                    ->where('f_v.optionId = ?', $facet_id);
                $stmt = $db->query($sql);
                while ($facet_value = $stmt->fetch()) {
                    $value = $facet_value['objectdata'] == null ? $facet_value['value'] : reset(unserialize($facet_value['objectdata']));
                    if (isset($option_values[$facet_value['id']])) {
                        $option_values[$facet_value['id']]["value_{$language}"] = $value;
                        continue;
                    }
                    $option_values[$facet_value['id']] = array("{$facet_name}_id" => $facet_value['id'], "value_{$language}" => $value);
                }

            }
            $option_values = array_merge(array(array_keys(end($option_values))), $option_values);
            $files->savepartToCsv("{$facet_name}.csv", $option_values);
            $optionSourceKey = $this->bxData->addResourceFile($files->getPath("{$facet_name}.csv"), "{$facet_name}_id", $localized_columns);

            $sql = $db->select()
                ->from(array('a' => 's_articles'),
                    array()
                )
                ->join(
                    array('d' => 's_articles_details'),
                    $this->qi('d.articleID') . ' = ' . $this->qi('a.id') . ' AND ' .
                    $this->qi('d.kind') . ' <> ' . $db->quote(3),
                    array('d.id')
                )
                ->join(array('f_v' => 's_filter_values'),
                    "f_v.optionID = {$facet['id']}",
                    array("{$facet_name}_id" => 'f_v.id')
                )
                ->join(array('f_a' => 's_filter_articles'),
                    'f_a.articleID = a.id  AND f_v.id = f_a.valueID',
                    array()
                )
                ->where($this->qi('a.active') . ' = ?', 1);
            if ($this->delta) {
                $sql->where('a.id IN(?)', $this->deltaIds);
            }
            $stmt = $db->query($sql);

            while ($row = $stmt->fetch()) {
                $data[] = $row;
            }
            if(count($data)){
                $data = array_merge(array(array_keys(end($data))), $data);
            }else{
                $data = array(array("id", "{$facet_name}_id"));
            }
            $files->savepartToCsv("product_{$facet_name}.csv", $data);
            $attributeSourceKey = $this->bxData->addCSVItemFile($files->getPath("product_{$facet_name}.csv"), 'id');
            $this->bxData->addSourceLocalizedTextField($attributeSourceKey, "optionID_{$facet_id}", "{$facet_name}_id", $optionSourceKey);
            $this->bxData->addSourceStringField($attributeSourceKey, "optionID_{$facet_id}_id", "{$facet_name}_id");
        }
    }

    protected function getShopCategoryIds($id) {

        if (!array_key_exists($id, $this->rootCategories)) {
            $db = $this->db;
            $sql = $db->select()
                ->from('s_core_shops', array('category_id'))
                ->where($this->qi('id') . ' = ?', $id)
                ->orWhere($this->qi('main_id') . ' = ?', $id);

            $cPath = $this->qi('c.path');
            $catIds = array();
            foreach ($db->fetchCol($sql) as $categoryId) {
                $catIds[] = "$cPath LIKE " . $db->quote("%|$categoryId|%");
            }
            if (count($catIds)) {
                $this->rootCategories[$id] = ' AND (' . implode(' OR ', $catIds) . ')';
            } else {
                $this->rootCategories[$id] = '';
            }
        }
        return $this->rootCategories[$id];
    }

    private function exportItemBlogs($account, $files){

        $db = $this->db;
        $headers = array('id', 'title', 'author_id', 'active', 'short_description', 'description', 'views',
            'display_date', 'category_id', 'template', 'meta_keywords', 'meta_description', 'meta_title',
            'assigned_articles', 'tags', 'shop_id');
        $id = $this->_config->getAccountStoreId($account);
        $data = array();
        $sql = $db->select()
            ->from(array('b' => 's_blog'),
                array('id' => new Zend_Db_Expr("CONCAT('blog_', b.id)"),
                    'b.title','b.author_id','b.active',
                    'b.short_description','b.description','b.views',
                    'b.display_date','b.category_id','b.template',
                    'b.meta_keywords','b.meta_keywords','b.meta_description','b.meta_title',
                    'assigned_articles' => new Zend_Db_Expr("GROUP_CONCAT(bas.article_id)"),
                    'tags' => new Zend_Db_Expr("GROUP_CONCAT(bt.name)")
                )
            )
            ->joinLeft(array('bas' => 's_blog_assigned_articles'), 'bas.blog_id = b.id',array())
            ->joinLeft(array('bt' => 's_blog_tags'), 'bt.blog_id = b.id',array())
            ->join(
                array('c' => 's_categories'),
                $this->qi('c.id') . ' = ' . $this->qi('b.category_id') .
                $this->getShopCategoryIds($id),
                array('path')
            )
            ->group('b.id');

        $stmt = $db->query($sql);
        while ($row = $stmt->fetch()) {
            $id = explode('|', $row['path']);
            unset($row['path']);
            $row['shop_id'] = $id[count($id) - 2];
            $data[] = $row;
        }

        if (count($data)) {
            $data = array_merge(array(array_keys(end($data))), $data);
        } else {
            $data = array_merge(array($headers), $data);
        }

        $files->savepartToCsv('product_blog.csv', $data);
        $attributeSourceKey = $this->bxData->addCSVItemFile($files->getPath('product_blog.csv'), 'id');
        $this->bxData->addSourceParameter($attributeSourceKey, 'additional_item_source', 'true');
        foreach ($headers as $header){
            $this->bxData->addSourceStringField($attributeSourceKey, 'blog_'.$header, $header);
        }
        $this->bxData->addFieldParameter($attributeSourceKey,'blog_id', 'multiValued', 'false');
    }

    private function exportItemUrls($account, $files) {

        $db = $this->db;
        $aId = $this->qi('a.id');
        $ruaPath = $this->qi('r_u_a.path');
        $rubPath = $this->qi('r_u_b.path');
        $ruaMain = $this->qi('r_u_a.main');
        $rubMain = $this->qi('r_u_b.main');
        $ruaOrgPath = $this->qi('r_u_a.org_path');
        $rubOrgPath = $this->qi('r_u_b.org_path');
        $main_shopId = $this->_config->getAccountStoreId($account);
        $repository = Shopware()->Container()->get('models')->getRepository('Shopware\Models\Shop\Shop');
        $shop = $repository->getActiveById($main_shopId);
        $defaultPath = 'http://'. $shop->getHost() . $shop->getBasePath() . '/';
        $languages = $this->_config->getAccountLanguages($account);
        $lang_header = array();
        $data = array();
        foreach ($languages as $shopId => $language) {
            $lang_header[$language] = "value_$language";
            $shop = $repository->getActiveById($shopId);
            $productPath = 'http://' . $shop->getHost() . $shop->getBasePath() . '/';
            $shop = null;

            $sql = $db->select()
                ->from(array('r_u' => 's_core_rewrite_urls'),
                    array('subshopID', 'path', 'org_path', 'main',
                        new Zend_Db_Expr("SUBSTR(org_path, LOCATE('sArticle=', org_path) + CHAR_LENGTH('sArticle=')) as articleID")
                    )
                )
                ->where("r_u.subshopID = {$shopId} OR r_u.subshopID = ?", $main_shopId)
                ->where("r_u.main = ?", 1)
                ->where("org_path like '%sArticle%'");
            if ($this->delta) {
                $sql->having('articleID IN(?)', $this->deltaIds);
            }

            $stmt = $db->query($sql);
            if ($stmt->rowCount()) {
                while ($row = $stmt->fetch()) {
                    $basePath = $row['subshopID'] == $shopId ? $productPath : $defaultPath;
                    if (isset($data[$row['articleID']])) {
                        if (isset($data[$row['articleID']]['value_' . $language])) {
                            if ($data[$row['articleID']]['subshopID'] < $row['subshopID']) {
                                $data[$row['articleID']]['value_' . $language] = $basePath . $row['path'];
                                $data[$row['articleID']]['subshopID'] = $row['subshopID'];
                            }
                        } else {
                            $data[$row['articleID']]['value_' . $language] = $basePath . $row['path'];
                            $data[$row['articleID']]['subshopID'] = $row['subshopID'];
                        }
                        continue;
                    }
                    $data[$row['articleID']] = array(
                        'articleID' => $row['articleID'],
                        'subshopID' => $row['subshopID'],
                        'value_' . $language => $basePath . $row['path']
                    );
                }
            }
        }

        if (count($data) > 0) {
            $data = array_merge(array(array_merge(array('articleID', 'subshopID'), $lang_header)), $data);
        }
        $files->savepartToCsv('url.csv', $data);
        $referenceKey = $this->bxData->addResourceFile($files->getPath('url.csv'), 'articleID', $lang_header);
        $sql = $db->select()
            ->from(array('a' => 's_articles'), array())
            ->join(
                array('d' => 's_articles_details'),
                $this->qi('d.articleID') . ' = ' . $this->qi('a.id') . ' AND ' .
                $this->qi('d.kind') . ' <> ' . $db->quote(3),
                array('id', 'articleID')
            )
            ->where($this->qi('a.active') . ' = ?', 1);
        if ($this->delta) {
            $sql->where('a.id IN(?)', $this->deltaIds);
        }
        $stmt = $db->query($sql);
        if ($stmt->rowCount()) {
            while ($row = $stmt->fetch()) {
                $data[$row['id']] = array('id' => $row['id'], 'articleID' => $row['articleID']);
            }
        }
        $data = array_merge(array(array_keys(end($data))), $data);
        $files->savepartToCsv('products_url.csv', $data);
        $attributeSourceKey = $this->bxData->addCSVItemFile($files->getPath('products_url.csv'), 'id');
        $this->bxData->addSourceLocalizedTextField($attributeSourceKey, "url", "articleID", $referenceKey);
    }

    private function exportItemImages($account, $files) {

        $db = $this->db;
        $data = array();
        $dot = $db->quote('.');
        $pipe = $db->quote('|');
        $fieldMain = $this->qi('s_articles_img.main');
        $fieldPosition = $this->qi('s_articles_img.position');
        $main_shopId = $this->_config->getAccountStoreId($account);
        $repository = Shopware()->Container()->get('models')->getRepository('Shopware\Models\Shop\Shop');
        $shop = $repository->getActiveById($main_shopId);
        $imagePath = $db->quote('http://'. $shop->getHost() . $shop->getBasePath()  . '/media/image/');

        $inner_select = $db->select()
            ->from('s_articles_img',
                new Zend_Db_Expr("GROUP_CONCAT(
                CONCAT($imagePath, img, $dot, extension)
                ORDER BY $fieldMain, $fieldPosition
                SEPARATOR $pipe)")
            )
            ->where('s_articles_img.articleID = a.id');

        $sql = $db->select()
            ->from(array('a' => 's_articles'), array('images' => new Zend_Db_Expr("($inner_select)")))
            ->join(
                array('d' => 's_articles_details'),
                $this->qi('d.articleID') . ' = ' . $this->qi('a.id') . ' AND ' .
                $this->qi('d.kind') . ' <> ' . $db->quote(3),
                array('id')
            )
            ->where($this->qi('a.active') . ' = ?', 1);
        if ($this->delta) {
            $sql->where('a.id IN(?)', $this->deltaIds);
        }
        $stmt = $db->query($sql);
        while ($row = $stmt->fetch()) {
            $data[] = $row;
        }
        $data = array_merge(array(array_keys(end($data))), $data);
        $files->savepartToCsv('product_image_url.csv', $data);
        $sourceKey = $this->bxData->addCSVItemFile($files->getPath('product_image_url.csv'), 'id');
        $this->bxData->addSourceStringField($sourceKey, 'image', 'images');
        $this->bxData->addFieldParameter($sourceKey,'image', 'splitValues', '|');
    }
    
    private function exportItemBrands($files) {

        $db = $this->db;
        $data = array();
        $sql = $db->select()
            ->from(array('a' => 's_articles'), array())
            ->join(
                array('d' => 's_articles_details'),
                $this->qi('d.articleID') . ' = ' . $this->qi('a.id') . ' AND ' .
                $this->qi('d.kind') . ' <> ' . $db->quote(3),
                array('id')
            )
            ->join(
                array('asup' => 's_articles_supplier'),
                $this->qi('asup.id') . ' = ' . $this->qi('a.supplierID'),
                array('brand' => 'name')
            )
            ->where($this->qi('a.active') . ' = ?', 1);
        if ($this->delta) {
            $sql->where('a.id IN(?)', $this->deltaIds);
        }
        $stmt = $db->query($sql);
        while ($row = $stmt->fetch()) {
            $row['brand'] = trim($row['brand']);
            $data[] = $row;
        }
        $data = array_merge(array(array_keys(end($data))), $data);
        $files->savepartToCsv('product_brands.csv', $data);
        $attributeSourceKey = $this->bxData->addCSVItemFile($files->getPath('product_brands.csv'), 'id');
        $this->bxData->addSourceStringField($attributeSourceKey, "brand", "brand");
    }
    
    private function getTableNameForTranslationColumn($name) {

        $tables = ['s_articles', 's_articles_attributes'];
        $db = $this->db;
        $db_name = $db->getConfig()['dbname'];
        $sql = $db->select()
            ->from(array('col' => 'information_schema.columns'), array('COLUMN_NAME', 'TABLE_NAME'))
            ->where('col.TABLE_SCHEMA = ?', $db_name)
            ->where('col.COLUMN_NAME = ?', $name)
            ->where('col.TABLE_NAME IN(?)', $tables)
            ->where('col.TABLE_NAME <> ?', 's_articles_translations');

        $stmt = $db->query($sql);
        return $stmt->fetch()['TABLE_NAME'];
    }

    private function exportItemTranslationFields($account, $files) {
        $db = $this->db;
        $data = array();
        $selectFields = array();
        $attributeValueHeader = array();
        foreach ($this->translationFields as $field) {

            $attributeValueHeader[$field] = array();
            foreach ($this->_config->getAccountLanguages($account) as $shop_id => $language) {
                $column = "{$field}_{$language}";
                $attributeValueHeader[$field][$language] = $column;
                $table = $this->getTableNameForTranslationColumn($field);
                $a_ref = $table == 's_articles' ? 'a.articleID' : 'a.id';
                $b_ref = $table == 's_articles' ? 'b.id' : 'b.articledetailsID';
                $innerSelect = $db->select()
                    ->from(array('b'=> $table),array(new Zend_Db_Expr("CASE WHEN t.{$field} IS NULL THEN b.{$field} ELSE t.{$field} END as value")))
                    ->joinLeft(array('t' => 's_articles_translations'),"t.articleID = b.id AND t.languageID = {$shop_id}", array())
                    ->where("{$a_ref} = {$b_ref}");

                $selectFields[$column] = new Zend_Db_Expr("($innerSelect)");
            }
        }
        $selectFields[] = 'a.id';
        $sql = $db->select()
            ->from(array('a' => 's_articles_details'), $selectFields)
            ->where($this->qi('a.active') . ' = ?', 1);
        if ($this->delta) {
            $sql->where('a.articleID IN(?)', $this->deltaIds);
        }

        $stmt = $db->query($sql);
        while ($row = $stmt->fetch()) {
            $data[] = $row;
        }
        $data = array_merge(array(array_keys(end($data))), $data);
        $files->savepartToCsv('product_translations.csv', $data);
        $attributeSourceKey = $this->bxData->addCSVItemFile($files->getPath('product_translations.csv'), 'id');

        foreach ($attributeValueHeader as $field => $values) {
            if ($field == 'name') {
                $this->bxData->addSourceTitleField($attributeSourceKey, $values);
            } else if ($field == 'description_long') {
                $this->bxData->addSourceDescriptionField($attributeSourceKey, $values);
            } else {
                $this->bxData->addSourceLocalizedTextField($attributeSourceKey, $field, $values);
            }
        }
    }

    private function extractValueLanguageKeys($fields) {
        $header = array();
        foreach ($fields as $key => $field) {
            if (strpos($key, 'value_') !== false) {
                $header[substr($key, 6)] = $key;
            }
        }
        return $header;
    }

    private function exportItemCategories($account, $files) {

        $categories = $this->exportCategories($account);
        $categories = array_merge(array(array_keys(end($categories))), $categories);
        $language_headers = $this->extractValueLanguageKeys(end($categories));
        $files->savePartToCsv('categories.csv', $categories);
        $categories = null;
        $this->bxData->addCategoryFile($files->getPath('categories.csv'), 'category_id', 'parent_id', $language_headers);
        $db = $this->db;
        $data = array();
        $sql = $db->select()
            ->from(array('ac' => 's_articles_categories'), array())
            ->join(
                array('d' => 's_articles_details'),
                $this->qi('d.articleID') . ' = ' . $this->qi('ac.articleID') . ' AND ' .
                $this->qi('d.kind') . ' <> ' . $db->quote(3),
                array('id', 'ac.categoryID')
            );
        if ($this->delta) {
            $sql->where('d.articleID IN(?)', $this->deltaIds);
        }

        $stmt = $db->query($sql);
        while ($row = $stmt->fetch()) {
            $data[] = $row;
        }
        $data = array_merge(array(array_keys(end($data))), $data);
        $files->savePartToCsv('product_categories.csv', $data);
        $productToCategoriesSourceKey = $this->bxData->addCSVItemFile($files->getPath('product_categories.csv'), 'id');
        $this->bxData->setCategoryField($productToCategoriesSourceKey, 'categoryID');
    }

    private function exportMainProducts($account, $files) {

        $db = $this->db;
        $product_attributes = $this->getProductAttributes($account);
        $product_properties = array_flip($product_attributes);

        $countMax = 1000000;
        $limit = 1000;
        $totalCount = 0;
        $page = 1;
        $header = true;
        $main_properties = array();
        $data = array();

        while ($countMax > $totalCount + $limit) {
            $sql = $db->select()
                ->from(array('s_articles'), $product_properties)
                ->join(array('s_articles_details'), 's_articles_details.articleID = s_articles.id', array())
                ->join(array('s_articles_attributes'), 's_articles_attributes.articledetailsID = s_articles_details.id', array())
                ->where('s_articles.active = ?', 1)
                ->where('s_articles.mode = ?', 0)
                ->limit($limit, ($page - 1) * $limit);
            if ($this->delta) {
                $sql->where('s_articles.changetime > ?', $this->getLastDelta());
            }

            $stmt = $db->query($sql);
            if ($stmt->rowCount()) {
                while ($row = $stmt->fetch()) {
                    if ($this->delta && !isset($this->deltaIds[$row['articleID']])) {
                        $this->deltaIds[$row['articleID']] = $row['articleID'] ;
                    }
                    $row['group_id'] = $row['articleID'];
                    $data[] = $row;
                    $totalCount++;
                }
            }else{
                if ($totalCount == 0) {
                    return false;
                }
                break;
            }

            if ($header && count($data) > 0) {
                $main_properties = array_keys(end($data));
                $data = array_merge(array(array_keys(end($data))), $data);
                $header = false;
            }
            $files->savePartToCsv('products.csv', $data);
            $page++;
        }

        $mainSourceKey = $this->bxData->addMainCSVItemFile($files->getPath('products.csv'), 'id');
        $this->bxData->addSourceStringField($mainSourceKey, 'bx_type', 'id');
        $this->bxData->addFieldParameter($mainSourceKey,'bx_type', 'pc_fields', 'CASE WHEN group_id IS NULL THEN "blog" ELSE "product" END AS final_value');
        $this->bxData->addFieldParameter($mainSourceKey,'bx_type', 'multiValued', 'false');

        foreach ($main_properties as $property) {

            if ($property == 'id') {
                continue;
            }
            if ($property == 'sales') {
                $this->bxData->addSourceNumberField($mainSourceKey, $property, $property);
                continue;
            }
            $this->bxData->addSourceStringField($mainSourceKey, $property, $property);
            if ($property == 'group_id') {
                $this->bxData->addFieldParameter($mainSourceKey, 'group_id', 'multiValued', 'false');
            }
        }
        return true;
    }

    private function getCustomerAttributes($account) {

        $all_attributes = array();
        $this->log->info('BxIndexLog: get all customer attributes for account: ' . $account);
        $db = $this->db;
        $db_name = $db->getConfig()['dbname'];
        $tables = ['s_user', 's_user_billingaddress'];
        $select = $db->select()
            ->from(array('col' => 'information_schema.columns'), array('COLUMN_NAME', 'TABLE_NAME'))
            ->where('col.TABLE_SCHEMA = ?', $db_name)
            ->where("col.TABLE_NAME IN (?)", $tables);

        $attributes = $db->fetchAll($select);
        foreach ($attributes as $attribute) {
            if ($attribute['COLUMN_NAME'] == 'userID' || $attribute['COLUMN_NAME'] == 'id') {
                if ($attribute['TABLE_NAME'] == 's_user_billingaddress') {
                    continue;
                }
            }
            $key = "{$attribute['TABLE_NAME']}.{$attribute['COLUMN_NAME']}";
            $all_attributes[$key] = $attribute['COLUMN_NAME'];
        }

        $requiredProperties = array('id', 'birthday', 'salutation');
        $filteredAttributes = $this->_config->getAccountCustomersProperties($account, $all_attributes, $requiredProperties);
        return $filteredAttributes;
    }

    private function getTransactionAttributes($account) {

        $all_attributes = array();
        $this->log->info('BxIndexLog: get all transaction attributes for account: ' . $account);
        $db = $this->db;
        $db_name = $db->getConfig()['dbname'];
        $tables = ['s_order', 's_order_details'];
        $select = $db->select()
            ->from(array('col' => 'information_schema.columns'), array('COLUMN_NAME', 'TABLE_NAME'))
            ->where('col.TABLE_SCHEMA = ?', $db_name)
            ->where("col.TABLE_NAME IN (?)", $tables);

        $attributes = $db->fetchAll($select);
        foreach ($attributes as $attribute) {
            if($attribute['COLUMN_NAME'] == 'orderID' || $attribute['COLUMN_NAME'] == 'id' || $attribute['COLUMN_NAME'] == 'ordernumber'){
                if($attribute['TABLE_NAME'] == 's_order_details'){
                    continue;
                }
            }
            $key = "{$attribute['TABLE_NAME']}.{$attribute['COLUMN_NAME']}";
            $all_attributes[$key] = $attribute['COLUMN_NAME'];
        }

        $requiredProperties = array('id','articleID','userID','ordertime','invoice_amount','currencyFactor','price');
        $filteredAttributes = $this->_config->getAccountTransactionsProperties($account, $all_attributes, $requiredProperties);

        return $filteredAttributes;
    }

    private function getProductAttributes($account) {

        $all_attributes = array();
        $exclude = array_merge($this->translationFields, array('articleID','id','active', 'articledetailsID'));
        $db = $this->db;
        $db_name = $db->getConfig()['dbname'];
        $tables = ['s_articles', 's_articles_details', 's_articles_attributes'];
        $select = $db->select()
            ->from(
                array('col' => 'information_schema.columns'),
                array('COLUMN_NAME', 'TABLE_NAME')
            )
            ->where('col.TABLE_SCHEMA = ?', $db_name)
            ->where("col.TABLE_NAME IN (?)", $tables);

        $attributes = $db->fetchAll($select);
        foreach ($attributes as $attribute) {

            if (in_array($attribute['COLUMN_NAME'], $exclude)) {
                if ($attribute['TABLE_NAME'] != 's_articles_details') {
                    continue;
                }
            }
            $key = "{$attribute['TABLE_NAME']}.{$attribute['COLUMN_NAME']}";
            $all_attributes[$key] = $attribute['COLUMN_NAME'];
        }

        $requiredProperties = array('id','articleID');
        $filteredAttributes = $this->_config->getAccountProductsProperties($account, $all_attributes, $requiredProperties);

        return $filteredAttributes;
    }

    private function exportCustomers($account, $files) {
        $this->log->debug("start collecting customers for account {$account}");
        $db = $this->db;
        $customer_attributes = $this->getCustomerAttributes($account);
        $customer_properties = array_flip($customer_attributes);
        $header = true;

        foreach ($this->_config->getAccountLanguages($account) as $shop_id => $language) {
            $data = array();
            $countMax = 1000000;
            $limit = 5000;
            $totalCount = 0;
            $page = 1;
            while ($countMax > $totalCount + $limit) {

                // get all customers
                $sql = $db->select()
                    ->from(
                        array('s_user'),
                        $customer_properties
                    )
                    ->joinLeft(
                        array('s_user_billingaddress'),
                        $this->qi('s_user_billingaddress.userID') . ' = ' . $this->qi('s_user.id'),
                        array()
                    )
                    ->where($this->qi('s_user.subshopID') . ' = ?', $shop_id)
                    ->limit($limit, ($page - 1) * $limit);

                $stmt = $db->query($sql);

                if ($stmt->rowCount()) {
                    while ($row = $stmt->fetch()) {
                        $data[] = $row;
                        $totalCount++;
                    }
                } else {
                    if ($totalCount == 0) {
                        return;
                    }
                    break;
                }
                if ($header && count($data) > 0) {
                    $data = array_merge(array(array_keys(end($data))), $data);
                    $header = false;
                }
                $files->savePartToCsv('customers.csv', $data);
                $this->log->info("BxIndexLog: Customer export - Current page: {$page}, data count: {$totalCount}");
                $page++;
            }
        }

        $customerSourceKey = $this->bxData->addMainCSVCustomerFile($files->getPath('customers.csv'), 'id');
        foreach ($customer_attributes as $attribute) {
            if ($attribute == 'id') continue;
            $this->bxData->addSourceStringField($customerSourceKey, $attribute, $attribute);
        }
        $this->log->info('BxIndexLog: Customer export finished for account: ' . $account);
    }

    private function exportTransactions($account, $files) {

        $db = $this->db;
        $transaction_attributes = $this->getTransactionAttributes($account);
        $transaction_properties = array_flip($transaction_attributes);

        $quoted2 = $db->quote(2);
        $oInvoiceAmount = $this->qi('s_order.invoice_amount');
        $oInvoiceShipping = $this->qi('s_order.invoice_shipping');
        $oCurrencyFactor = $this->qi('s_order.currencyFactor');
        $dPrice = $this->qi('s_order_details.price');
        $header = true;
        $transaction_properties = array_merge($transaction_properties,
            array(
                'total_order_value' => new Zend_Db_Expr(
                    "ROUND($oInvoiceAmount * $oCurrencyFactor, $quoted2)"),
                'shipping_costs' => new Zend_Db_Expr(
                    "ROUND($oInvoiceShipping * $oCurrencyFactor, $quoted2)"),
                'price' => new Zend_Db_Expr(
                    "ROUND($dPrice * $oCurrencyFactor, $quoted2)")
            )
        );

        $header = true;
        $data = array();
        $countMax = 1000000;
        $limit = 5000;
        $totalCount = 0;
        foreach ($this->_config->getAccountLanguages($account) as $shop_id => $language) {

            $page = 1;
            while ($countMax > $totalCount + $limit) {
                $sql = $db->select()
                    ->from(
                        array('s_order'),
                        $transaction_properties
                    )
                    ->joinLeft(
                        array('s_order_details'),
                        $this->qi('s_order_details.orderID') . ' = ' . $this->qi('s_order.id'),
                        array()
                    )
                    ->where($this->qi('s_order.subshopID') . ' = ?', $shop_id)
                    ->limit($limit, ($page - 1) * $limit);
                $stmt = $db->query($sql);

                if ($stmt->rowCount()) {

                    while ($row = $stmt->fetch()) {
                        // @note list price at the time of the order is not stored, only the final price
                        $row['discounted_price'] = $row['price'];
                        $data[] = $row;
                        $totalCount++;
                    }
                } else {
                    if ($totalCount == 0){
                        return;
                    }
                    break;
                }
                if ($header && count($data) > 0) {
                    $data = array_merge(array(array_keys(end($data))), $data);
                    $header = false;
                }
                $files->savePartToCsv('transactions.csv', $data);
                $this->log->info("BxIndexLog: Transaction export - Current page: {$page}, data count: {$totalCount}");
                $page++;
            }
        }
        $this->bxData->setCSVTransactionFile($files->getPath('transactions.csv'), 'id', 'articleID', 'userID', 'ordertime', 'total_order_value', 'price', 'discounted_price');
    }

    /**
     * @param $account
     * @return array
     */
    private function exportCategories($account) {

        $db = $this->db;
        $categories = array();
        $languages = $this->_config->getAccountLanguages($account);
        foreach ($languages as $store_id => $language) {
            $sql = $db->select()
                ->from(array('s' => 's_core_shops'), array('id', 'category_id'))
                ->where('s.id = ?', $store_id);
            $root_category_id = $db->fetchRow($sql)['category_id'];

            $select = $db->select()
                ->from(array('c' => 's_categories'), array('id', 'parent', 'description', 'path'))
                ->where('c.path IS NOT NULL')
                ->where('c.id <> ?', 1)
                ->where('c.path LIKE \'%|'.$root_category_id.'|%\'');

            $result = $db->fetchAll($select);

            foreach ($result as $r) {
                $value = $r['description'];
                $categories[$r['id']] = array('category_id' => $r['id'], 'parent_id' => $r['parent'], 'language' => $language);
                foreach ($languages as $language) {
                    $categories[$r['id']]['value_'.$language] = $value;
                }
            }
        }

        return $categories;
    }

    /**
     * @return string
     */
    protected function getLastDelta() {
        if (empty($this->deltaLast)) {
            $this->deltaLast = '1950-01-01 12:00:00';
            $db = $this->db;
            $sql = $db->select()
                      ->from('exports', array('export_date'))
                      ->limit(1);
            $stmt = $db->query($sql);
            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch();
                $this->deltaLast = $row['export_date'];
            }
        }
        return $this->deltaLast;
    }

    /**
     * wrapper to quote database identifiers
     *
     * @param  string $identifier
     * @return string
     */
    protected function qi($identifier) {
        return $this->db->quoteIdentifier($identifier);
    }

    private function updateExportTable() {
        $this->db->query('TRUNCATE `exports`');
        $this->db->query('INSERT INTO `exports` values(NOW())');
    }

}