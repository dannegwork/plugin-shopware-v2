<?php
namespace Boxalino\Components\Exporter;

use Boxalino\Boxalino;
use Boxalino\Helper\BxFiles;
use Boxalino\Helper\BxIndexConfig;
use com\boxalino\bxclient\v1\BxClient;
use com\boxalino\bxclient\v1\BxData;
use Shopware\Components\DependencyInjection\Bridge\Plugins;

class DataExporter {

    protected $request;
    protected $manager;

    protected $propertyDescriptions = array();

    protected $dirPath;
    protected $plugin;
    protected $db;
    protected $log;
    protected $delta;
    protected $deltaLast;
    protected $fileHandle;

    protected $deltaIds = array();
    protected $_config;
    protected $bxData;
    protected $_attributes = array();
    protected $shopProductIds = array();
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
        $this->_config = new BxIndexConfig();
        $this->db = Shopware()->Db();
        $this->log = Shopware()->PluginLogger();

        $this->plugin = new Boxalino();
        $libPath = $this->plugin->getPath() . '/lib';
        require_once($libPath . '/BxClient.php');
        BxClient::LOAD_CLASSES($libPath);
    }


    /**
     * run the exporter
     *
     * iterates over all shops and exports them according to their settings
     *
     * @return array
     */
    public function run()
    {
        set_time_limit(7200);
        $data = array();
        $type = $this->delta ? 'delta' : 'full';
        try {
            $this->log->info("BxIndexLog: Start of boxalino {$type} data sync.");

            $this->log->info("BxIndexLog: Exporting for accounts: " . implode(', ', $this->_config->getAccounts()));
            foreach ($this->_config->getAccounts() as $account) {

                $this->log->info("BxIndexLog: Exporting store ID : {$this->_config->getAccountStoreId($account)}");
                $this->log->info("BxIndexLog: Initialize files on account: {$account}");
                $files = new BxFiles($this->dirPath, $account, $type);

                $bxClient = new BxClient($account, $this->_config->getAccountPassword($account), "");
                $this->bxData = new BxData($bxClient, $this->_config->getAccountLanguages($account), $this->_config->isAccountDev($account), $this->delta);
                $this->log->info("BxIndexLog: verify credentials for account: " . $account);
                try {
                    $this->bxData->verifyCredentials();
                } catch (\Throwable $e){
                    $this->log->error("BxIndexLog: verifyCredentials failed with exception: {$e->getMessage()}");
                    continue;
                }

                $this->log->info('BxIndexLog: Preparing the attributes and category data for each language of the account: ' . $account);

                $this->log->info("BxIndexLog: Preparing products.");
                $exportProducts = $this->exportProducts($account, $files);
                $this->shopProductIds = null;
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
                        } catch (\Throwable $e) {
                            $value = @json_decode($e->getMessage(), true);
                            if (isset($value['error_type_number']) && $value['error_type_number'] == 3) {
                                $this->log->info('BxIndexLog: Try to push the XML file a second time, error 3 happens always at the very first time but not after: ' . $account);
                                $this->bxData->pushDataSpecifications();
                            } else {
                                $this->log->info("BxIndexLog: pushDataSpecifications failed with exception: " . $e->getMessage());
                            }
                        }

                        $this->log->info('BxIndexLog: Publish the configuration changes from the owner for account: ' . $account);
                        $publish = $this->_config->publishConfigurationChanges($account);
                        $changes = $this->bxData->publishChanges($publish);
                        $data['token'] = $changes['token'];
                        if (sizeof($changes['changes']) > 0 && !$publish) {
                            $this->log->info("BxIndexLog: changes in configuration detected but not published as publish configuration automatically option has not been activated for account: " . $account);
                        }
                        $this->log->info('BxIndexLog: NORMAL - stop waiting for Data Intelligence processing for account: ' . $account);

                    }
                    $this->log->info('BxIndexLog: pushing to DI for account: ' . $account);
                    try {
                        $this->bxData->pushData();
                    } catch (\Throwable $e){
                        $this->log->info("BxIndexLog: pushData failed with exception for : " . $e->getMessage());
                    }
                    $this->log->info('BxIndexLog: Finished account: ' . $account);
                }
            }
        } catch(\Throwable $e) {
            error_log("BxIndexLog: failed with exception: " .$e->getMessage());
            $this->log->info("BxIndexLog: failed with exception: " . $e->getMessage());
        }
        $this->log->info("BxIndexLog: End of boxalino $type data sync ");
        $this->updateExportTable();
        return var_export($data, true);
    }

    /**
     * @param $account
     * @param $files
     * @return bool
     */
    private function exportProducts($account, $files)
    {
        $this->log->info("BxIndexLog: Preparing products - main.");
        $export_products = $this->exportMainProducts($account, $files);
        $this->log->info("Main product after memory: " . memory_get_usage(true));

        $this->log->info("BxIndexLog: Finished products - main.");
        if ($export_products) {
            $this->log->info("BxIndexLog: Preparing products - categories.");
            $this->exportItemCategories($account, $files);
            $this->log->info("exportItemCategories after memory: " . memory_get_usage(true));
            $this->log->info("BxIndexLog: Finished products - categories.");
            $this->log->info("BxIndexLog: Preparing products - translations.");
            $this->exportItemTranslationFields($account, $files);
            $this->log->info("exportItemTranslationFields after memory: " . memory_get_usage(true));
            $this->log->info("BxIndexLog: Finished products - translations.");
            $this->log->info("BxIndexLog: Preparing products - brands.");
            $this->exportItemBrands($files);
            $this->log->info("exportItemBrands after memory: " . memory_get_usage(true));
            $this->log->info("BxIndexLog: Finished products - brands.");
            $this->log->info("BxIndexLog: Preparing products - facets.");
            $this->exportItemFacets($account, $files);
            $this->log->info("exportItemFacets after memory: " . memory_get_usage(true));
            $this->log->info("BxIndexLog: Finished products - facets.");
            $this->log->info("BxIndexLog: Preparing products - price.");
            $this->exportItemPrices($account, $files);
            $this->log->info("exportItemPrices after memory: " . memory_get_usage(true));
            $this->log->info("BxIndexLog: Finished products - price.");
            if ($this->_config->exportProductImages($account)) {
                $this->log->info("BxIndexLog: Preparing products - image.");
                $this->exportItemImages($account, $files);
                $this->log->info("exportItemImages after memory: " . memory_get_usage(true));
                $this->log->info("BxIndexLog: Finished products - image.");
            }
            if ($this->_config->exportProductUrl($account)) {
                $this->log->info("BxIndexLog: Preparing products - url.");
                $this->exportItemUrls($account, $files);
                $this->log->info("exportItemUrls after memory: " . memory_get_usage(true));
                $this->log->info("BxIndexLog: Finished products - url.");
            }
            if(!$this->delta) {
                $this->log->info("BxIndexLog: Preparing products - blogs.");
                $this->exportItemBlogs($account, $files);
                $this->log->info("exportItemBlogs after memory: " . memory_get_usage(true));
                $this->log->info("BxIndexLog: Finished products - blogs.");
            }
            $this->log->info("BxIndexLog: Preparing products - votes.");
            $this->exportItemVotes($files);
            $this->log->info("exportItemVotes after memory: " . memory_get_usage(true));
            $this->log->info("BxIndexLog: Finished products - votes.");
            $this->log->info("BxIndexLog: Preparing products - product streams.");
            $this->exportProductStreams($files);
            $this->log->info("exportProductStreams after memory: " . memory_get_usage(true));
            $this->log->info("BxIndexLog: Finished products - product streams.");
            if ($this->_config->isVoucherExportEnabled($account)) {
                $this->log->info("BxIndexLog: Preparing products - voucher.");
                $this->log->info("BxIndexLog: Preparing vouchers.");
                $this->exportVouchers($account, $files);
                $this->log->info("exportVouchers after memory: " . memory_get_usage(true));
                $this->log->info("BxIndexLog: Finished products - voucher.");
            }
        }
        return $export_products;
    }

    /**
     * @param $files
     */
    private function exportProductStreams($files) {

        $db = $this->db;
        $data = array();
        $header = true;
        $count = 0;
        $sql = $db->select()
            ->from(array('a' => 's_articles'), array())
            ->join(
                array('d' => 's_articles_details'),
                $this->qi('d.articleID') . ' = ' . $this->qi('a.id') . ' AND ' .
                $this->qi('d.kind') . ' <> ' . $db->quote(3),
                array('id')
            )
            ->join(
                array('s_s' => 's_product_streams_selection'),
                $this->qi('s_s.article_id') . ' = ' . $this->qi('a.id'),
                array('stream_id')
            );
        if ($this->delta) {
            $sql->where('a.id IN(?)', $this->deltaIds);
        }
        $stmt = $db->query($sql);
        while ($row = $stmt->fetch()) {
            if (!isset($this->shopProductIds[$row['id']])) {
                continue;
            }
            if ($header) {
                $data[] = array_keys($row);
                $header = false;
            }

            $data[] = $row;
            if(sizeof($data) > 1000) {
                $files->savepartToCsv('product_stream.csv', $data);
                $data = [];
            }

        }
        $files->savepartToCsv('product_stream.csv', $data);
        $attributeSourceKey = $this->bxData->addCSVItemFile($files->getPath('product_stream.csv'), 'id');
        $this->bxData->addSourceStringField($attributeSourceKey, "stream_id", "stream_id");
    }

    /**
     * @param $account
     * @param $files
     */
    private function exportItemVotes($files) {

        $db = $this->db;
        $data = array();
        $header = true;
        $sql = $db->select()
            ->from(array('a' => 's_articles_vote'),
                array('average' => new \Zend_Db_Expr("SUM(a.points) / COUNT(a.id)"), 'articleID'))
            ->where('a.active = 1')
            ->group('a.articleID');
        if ($this->delta) {
            $sql->where('a.articleID IN(?)', $this->deltaIds);
        }
        $stmt = $db->query($sql);
        while ($row = $stmt->fetch()) {
            if ($header) {
                $data[] = array_keys($row);
                $header = false;
            }
            if(sizeof($data) > 1000) {
                $files->savepartToCsv('vote.csv', $data);
                $data = [];
            }
            $data[] = $row;
        }
        if($header) {
            $data[] = array('average', 'articleID');
        }
        $files->savepartToCsv('vote.csv', $data);
        $referenceKey = $this->bxData->addResourceFile($files->getPath('vote.csv'), 'articleID', ['average']);

        $data = array();
        $header = true;
        $sql = $db->select()
            ->from(array('a' => 's_articles'), array())
            ->join(
                array('d' => 's_articles_details'),
                $this->qi('d.articleID') . ' = ' . $this->qi('a.id') . ' AND ' .
                $this->qi('d.kind') . ' <> ' . $db->quote(3),
                array('id', 'articleID')
            );
        if ($this->delta) {
            $sql->where('a.id IN(?)', $this->deltaIds);
        }
        $stmt = $db->query($sql);
        if ($stmt->rowCount()) {
            while ($row = $stmt->fetch()) {
                if(!isset($this->shopProductIds[$row['id']])) {
                    continue;
                }
                if ($header) {
                    $data[] = array_keys($row);
                    $header = false;
                }
                $data[$row['id']] = array('id' => $row['id'], 'articleID' => $row['articleID']);
                if(sizeof($data) > 1000) {
                    $files->savepartToCsv('product_vote.csv', $data);
                    $data = array();
                }
            }
        }
        $files->savepartToCsv('product_vote.csv', $data);
        $attributeSourceKey = $this->bxData->addCSVItemFile($files->getPath('product_vote.csv'), 'id');
        $this->bxData->addSourceNumberField($attributeSourceKey, "vote", "articleID", $referenceKey);
    }

    /**
     * @param $account
     * @param $files
     */
    private function exportItemPrices($account, $files) {

        $customer_group_key = $this->_config->getCustomerGroupKey($account);
        $customer_group_id = $this->_config->getCustomerGroupId($account);
        $header = true;
        $db = $this->db;
        $sql = $db->select()
            ->from(array('a' => 's_articles'),array('pricegroupActive', 'laststock')
            )
            ->join(
                array('d' => 's_articles_details'),
                $this->qi('d.articleID') . ' = ' . $this->qi('a.id') . ' AND ' .
                $this->qi('d.kind') . ' <> ' . $db->quote(3),
                array('d.id', 'd.articleID', 'd.instock', 'd.active')
            )
            ->joinLeft(array('a_p' => 's_articles_prices'), 'a_p.articledetailsID = d.id', array('price', 'pseudoprice'))
            ->joinLeft(array('c_c' => 's_core_customergroups'), 'c_c.groupkey = a_p.pricegroup',array())
            ->joinLeft(array('c_t' => 's_core_tax'), 'c_t.id = a.taxID', array('tax'))
            ->joinLeft(
                array('p_d' => 's_core_pricegroups_discounts'),
                'p_d.groupID = a.pricegroupID AND p_d.customergroupID = ' . $customer_group_id ,
                array('pg_discounts' => 'discount')
            )
            ->where('a_p.pricegroup = ?', $customer_group_key)
            ->where('a_p.from = ?', 1);
        if ($this->delta) {
            $sql->where('a.id IN(?)', $this->deltaIds);
        }

        $grouped_price = array();
        $data = array();
        $stmt = $db->query($sql);
        while ($row = $stmt->fetch()) {
            if(!isset($this->shopProductIds[$row['id']])){
                continue;
            }
            $taxFactor = ((floatval($row['tax']) + 100.0) /100);
            if ($row['pseudoprice'] == 0) $row['pseudoprice'] = $row['price'];
            $pseudo = floatval($row['pseudoprice']) * $taxFactor;
            $discount = floatval($row['price']) * $taxFactor;
            if (!is_null($row['pg_discounts']) && $row['pricegroupActive'] == 1) {
                $discount = $discount - ($discount * ((floatval($row['pg_discounts'])) /100));
            }
            $price = $pseudo > $discount ? $pseudo : $discount;
            if($header) {
                $data[] = ["id", "price", "discounted", "articleID", "grouped_price"];
                $header = false;
            }
            $data[$row['id']] = array("id" => $row['id'], "price" => number_format($price,2, '.', ''), "discounted" => number_format($discount,2, '.', ''), "articleID" => $row['articleID']);

            if ($row['active'] == 1) {
                if(isset($grouped_price[$row['articleID']]) && ($grouped_price[$row['articleID']] < number_format($discount,2, '.', ''))) {
                    continue;
                }
                $grouped_price[$row['articleID']] = number_format($discount,2, '.', '');
            }
        }

        foreach ($data as $index => $d) {
            if($index == 0) continue;
            $articleID = $d['articleID'];
            if(isset($grouped_price[$articleID])){
                $data[$index]['grouped_price'] = $grouped_price[$articleID];
                continue;
            }
            $data[$index]['grouped_price'] = $data[$index]['discounted'];
        }

        $grouped_price = null;
        $files->savepartToCsv('product_price.csv', $data);
        $sourceKey = $this->bxData->addCSVItemFile($files->getPath('product_price.csv'), 'id');
        $this->bxData->addSourceDiscountedPriceField($sourceKey, 'discounted');
        $this->bxData->addSourceListPriceField($sourceKey, 'price');
        $this->bxData->addSourceNumberField($sourceKey, 'bx_grouped_price', 'grouped_price');
        $this->bxData->addFieldParameter($sourceKey,'bx_grouped_price', 'multiValued', 'false');
    }

    /**
     * @param $account
     * @param $files
     */
    private function exportItemFacets($account, $files) {

        $db = $this->db;
        $mapped_option_values = array();
        $option_values = array();
        $languages = $this->_config->getAccountLanguages($account);
        $log = true;
//        Exclude options from shop which are not used
//        $sql = $db->select()->from(array('f_o' => 's_filter_options'))
//            ->join(array('f_v' => 's_filter_values'), 'f_v.optionID = f_o.id', array())
//            ->join(array('f_a' => 's_filter_articles'), 'f_v.id = f_a.valueID', array())
//            ->join(array('a_d' => 's_articles_details'), 'a_d.articleID = f_a.articleID', array('product_id'=>'a_d.id'));
//        $start = microtime(true);
//        $stmt = $db->query($sql);
//        $facets = [];
//        while($row = $stmt->fetch()){
//            if($log) {
//                $end = (microtime(true) -$start) * 1000;
//                $this->log->info("Facets option exclude query time: " . $end . "ms, memory: " . memory_get_usage(true));
//                $log = false;
//            }
//            if(isset($this->shopProductIds[$row['product_id']])){
//                if(!isset($facets[$row['id']])){
//                    $facets[$row['id']] = array('id'=> $row['id']);
//                }
//            }
//        }
//        $end = (microtime(true) -$start) * 1000;
//        $this->log->info("Facets option exclude data process time: " . $end . "ms, memory: " . memory_get_usage(true));
        $sql = $db->select()->from(array('f_o' => 's_filter_options'));
        $facets = $db->fetchAll($sql);
        foreach ($facets as $facet) {

            $log = true;
            $facet_id = $facet['id'];
            $facet_name = "option_{$facet_id}";

            $data = array();
            $localized_columns = array();
            $foreachstart = microtime(true);
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
                $start = microtime(true);
                $stmt = $db->query($sql);
                while ($facet_value = $stmt->fetch()) {
                    if($log){
                        $end = (microtime(true) - $start) * 1000;
                        $this->log->info("Facets option ($facet_name) time for query with {$language}: $end ms, memory: " . memory_get_usage(true));
                        $log = false;
                    }
                    $value = trim(reset(unserialize($facet_value['objectdata'])));
                    $value = $value == '' ? trim($facet_value['value']) : $value;
                    if (isset($option_values[$facet_value['id']])) {
                        $option_values[$facet_value['id']]["value_{$language}"] = $value;
                        $mapped_option_values[$facet_value['id']]["value_{$language}"] = "{$value}_bx_{$facet_value['id']}";
                        continue;
                    }
                    $option_values[$facet_value['id']] = array("{$facet_name}_id" => $facet_value['id'], "value_{$language}" => $value);
                    $mapped_option_values[$facet_value['id']] = array("{$facet_name}_id" => $facet_value['id'], "value_{$language}" => "{$value}_bx_{$facet_value['id']}");
                }
                $end = (microtime(true) - $start) * 1000;
                $this->log->info("Facets option ($facet_name) time for data processing with {$language}: $end ms, memory: " . memory_get_usage(true));
            }
            $option_values = array_merge(array(array_keys(end($option_values))), $option_values);
            $files->savepartToCsv("{$facet_name}.csv", $option_values);

            $mapped_option_values = array_merge(array(array_keys(end($mapped_option_values))), $mapped_option_values);
            $files->savepartToCsv("{$facet_name}_bx_mapped.csv", $mapped_option_values);

            $optionSourceKey = $this->bxData->addResourceFile($files->getPath("{$facet_name}.csv"), "{$facet_name}_id", $localized_columns);
            $optionMappedSourceKey = $this->bxData->addResourceFile($files->getPath("{$facet_name}_bx_mapped.csv"), "{$facet_name}_id", $localized_columns);

            $foreachstartend = (microtime(true) - $foreachstart) * 1000;
            $this->log->info("Facets option (" . $facet_name.") time for filter values with translation: " . $foreachstartend . "ms, memory: " . memory_get_usage(true));

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
                );
            if ($this->delta) {
                $sql->where('a.id IN(?)', $this->deltaIds);
            }
            $log = true;
            $start = microtime(true);
            $stmt = $db->query($sql);

            $header = true;
            while ($row = $stmt->fetch()) {
                if($log) {
                    $end = (microtime(true) -$start) * 1000;
                    $this->log->info("Facets option ($facet_name) query time for products: " . $end . "ms, memory: " . memory_get_usage(true));
                    $log = false;
                }
                if($header) {
                    $data[] = array_keys($row);
                    $header = false;
                }
                if(isset($this->shopProductIds[$row['id']])){
                    $data[] = $row;
                }
            }

            $second_reference = $data;
            $files->savepartToCsv("product_{$facet_name}.csv", $data);
            $attributeSourceKey = $this->bxData->addCSVItemFile($files->getPath("product_{$facet_name}.csv"), 'id');
            $this->bxData->addSourceLocalizedTextField($attributeSourceKey, "optionID_{$facet_id}", "{$facet_name}_id", $optionSourceKey);
            $this->bxData->addSourceStringField($attributeSourceKey, "optionID_{$facet_id}_id", "{$facet_name}_id");

            $files->savepartToCsv("product_{$facet_name}_mapped.csv", $second_reference);
            $secondAttributeSourceKey = $this->bxData->addCSVItemFile($files->getPath("product_{$facet_name}_mapped.csv"), 'id');
            $this->bxData->addSourceLocalizedTextField($secondAttributeSourceKey, "optionID_mapped_{$facet_id}", "{$facet_name}_id", $optionMappedSourceKey);
            $this->bxData->addSourceStringField($secondAttributeSourceKey, "optionID_{$facet_id}_id_mapped", "{$facet_name}_id");
            $end = (microtime(true) - $start) * 1000;
            $this->log->info("Facets option ($facet_name) data processing time for products: " . $end . "ms, memory: " . memory_get_usage(true));
        }
    }

    /**
     * @param $id
     * @return mixed
     */
    protected function getShopCategoryIdsQuery($id) {

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

    protected function getShopCategoryIds($id) {
        $shopCat = array();
        $db = $this->db;
        $sql = $db->select()
            ->from('s_core_shops', array('id', 'category_id'))
            ->where($this->qi('id') . ' = ?', $id)
            ->orWhere($this->qi('main_id') . ' = ?', $id);
        $stmt = $db->query($sql);
        if($stmt->rowCount()) {
            while($row = $stmt->fetch()) {
                $shopCat[$row['id']] = $row['category_id'];
            }
        }
        return $shopCat;
    }
    /**
     * @param $account
     * @param $files
     */
    private function exportItemBlogs($account, $files){

        $db = $this->db;
        $headers = array('id', 'title', 'author_id', 'active', 'short_description', 'description', 'views',
            'display_date', 'category_id', 'template', 'meta_keywords', 'meta_description', 'meta_title',
            'assigned_articles', 'tags', 'media_id', 'shop_id', 'media_url');
        $id = $this->_config->getAccountStoreId($account);
        $shopCategories = $this->getShopCategoryIds($id);
        $data = array();
        $media_service = Shopware()->Container()->get('shopware_media.media_service');
        $sql = $db->select()
            ->from(array('b' => 's_blog'),
                array('id' => new \Zend_Db_Expr("CONCAT('blog_', b.id)"),
                    'b.title','b.author_id','b.active',
                    'b.short_description','b.description','b.views',
                    'b.display_date','b.category_id','b.template',
                    'b.meta_keywords','b.meta_keywords','b.meta_description','b.meta_title',
                    'assigned_articles' => new \Zend_Db_Expr("GROUP_CONCAT(bas.article_id)"),
                    'tags' => new \Zend_Db_Expr("GROUP_CONCAT(bt.name)"),
                    'media_id' => 'bm.media_id',
                    'media_path' => 'm.path'
                )
            )
            ->joinLeft(array('bas' => 's_blog_assigned_articles'), 'bas.blog_id = b.id',array())
            ->joinLeft(array('bt' => 's_blog_tags'), 'bt.blog_id = b.id',array())
            ->joinLeft(array('bm' => 's_blog_media'), 'bm.blog_id = b.id AND bm.preview = 1',array())
            ->joinLeft(array('m' => 's_media'), 'bm.media_id = m.id',array())
            ->join(
                array('c' => 's_categories'),
                $this->qi('c.id') . ' = ' . $this->qi('b.category_id') .
                $this->getShopCategoryIdsQuery($id),
                array('path')
            )
            ->group('b.id');
        $stmt = $db->query($sql);
        while ($row = $stmt->fetch()) {
            $blog_shop_id = $id;
            foreach ($shopCategories as $shop_id => $cat_id) {
                if(strpos($row['path'], $cat_id) !== false) {
                    $blog_shop_id = $shop_id;
                    break;
                }
            }
            unset($row['path']);
            $row['shop_id'] = $blog_shop_id;
            $row['media_url'] = $row['media_path'] ? $media_service->getUrl($row['media_path']) : null;
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

    /**
     * @param $account
     * @param $files
     */
    private function exportItemUrls($account, $files) {

        $db = $this->db;
        $main_shopId = $this->_config->getAccountStoreId($account);
        $repository = Shopware()->Container()->get('models')->getRepository('Shopware\Models\Shop\Shop');
        $shop = $repository->getActiveById($main_shopId);
        $defaultPath = 'http://'. $shop->getHost() . $shop->getBasePath() . '/';
        $languages = $this->_config->getAccountLanguages($account);
        $lang_header = array();
        $lang_productPath = array();
        $data = array();
        foreach ($languages as $shopId => $language) {
            $lang_header[$language] = "value_$language";
            $shop = $repository->getActiveById($shopId);
            $productPath = 'http://' . $shop->getHost() . $shop->getBasePath()  . $shop->getBaseUrl() . '/' ;
            $lang_productPath[$language] = $productPath;
            $shop = null;

            $sql = $db->select()
                ->from(array('r_u' => 's_core_rewrite_urls'),
                    array('subshopID', 'path', 'org_path', 'main',
                        new \Zend_Db_Expr("SUBSTR(org_path, LOCATE('sArticle=', org_path) + CHAR_LENGTH('sArticle=')) as articleID")
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
        $sql = $db->select()
            ->from(array('a' => 's_articles'), array())
            ->join(
                array('d' => 's_articles_details'),
                $this->qi('d.articleID') . ' = ' . $this->qi('a.id') . ' AND ' .
                $this->qi('d.kind') . ' <> ' . $db->quote(3),
                array('id', 'articleID')
            );
        if ($this->delta) {
            $sql->where('a.id IN(?)', $this->deltaIds);
        }
        $stmt = $db->query($sql);
        if ($stmt->rowCount()) {
            while ($row = $stmt->fetch()) {
                if(!isset($data[$row['articleID']])){
                    $articleID = $row['articleID'];
                    $item = ["articleID" => $articleID, "subshopID" => null];
                    foreach ($lang_productPath as $language => $path) {
                        $item["value_{$language}"] = "{$path}detail/index/sArticle/{$articleID}";
                    }
                    $data[$row['articleID']] = $item;
                }
            }
        }
        if (count($data) > 0) {
            $data = array_merge(array(array_merge(array('articleID', 'subshopID'), $lang_header)), $data);
        } else {
            $data = (array(array_merge(array('articleID', 'subshopID'), $lang_header)));
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
            );
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

    /**
     * @param $account
     * @param $files
     */
    private function exportItemImages($account, $files) {

        $db = $this->db;
        $data = array();
        $pipe = $db->quote('|');
        $fieldMain = $this->qi('s_articles_img.main');
        $imagePath = $this->qi('s_media.path');
        $fieldPosition = $this->qi('s_articles_img.position');
        $header = true;
        $mediaService = Shopware()->Container()->get('shopware_media.media_service');
        $inner_select = $db->select()
            ->from('s_articles_img',
                new \Zend_Db_Expr("GROUP_CONCAT(
                CONCAT($imagePath)
                ORDER BY $fieldMain, $fieldPosition
                SEPARATOR $pipe)")
            )
            ->join(array('s_media'), 's_media.id = s_articles_img.media_id', array())
            ->where('s_articles_img.articleID = a.id');

        $sql = $db->select()
            ->from(array('a' => 's_articles'), array('images' => new \Zend_Db_Expr("($inner_select)")))
            ->join(
                array('d' => 's_articles_details'),
                $this->qi('d.articleID') . ' = ' . $this->qi('a.id') . ' AND ' .
                $this->qi('d.kind') . ' <> ' . $db->quote(3),
                array('id')
            );
        if ($this->delta) {
            $sql->where('a.id IN(?)', $this->deltaIds);
        }
        $stmt = $db->query($sql);
        while ($row = $stmt->fetch()) {
            if(!isset($this->shopProductIds[$row['id']])){
                continue;
            }
            if($header) {
                $data[] = array_keys($row);
                $header = false;
            }
            $images = explode('|', $row['images']);
            foreach ($images as $index => $image) {
                $images[$index] = $mediaService->getUrl($image);
            }
            $row['images'] = implode('|', $images);
            $data[] = $row;
        }
        $files->savepartToCsv('product_image_url.csv', $data);
        $sourceKey = $this->bxData->addCSVItemFile($files->getPath('product_image_url.csv'), 'id');
        $this->bxData->addSourceStringField($sourceKey, 'image', 'images');
        $this->bxData->addFieldParameter($sourceKey,'image', 'splitValues', '|');
    }

    /**
     * @param $files
     */
    private function exportItemBrands($files) {

        $db = $this->db;
        $data = array();
        $header = true;
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
            );
        if ($this->delta) {
            $sql->where('a.id IN(?)', $this->deltaIds);
        }
        $stmt = $db->query($sql);
        while ($row = $stmt->fetch()) {
            if(!isset($this->shopProductIds[$row['id']])) {
                continue;
            }
            if($header) {
                $data[] = array_keys($row);
                $header = false;
            }
            $row['brand'] = trim($row['brand']);
            $data[] = $row;
        }
        $files->savepartToCsv('product_brands.csv', $data);
        $attributeSourceKey = $this->bxData->addCSVItemFile($files->getPath('product_brands.csv'), 'id');
        $this->bxData->addSourceStringField($attributeSourceKey, "brand", "brand");
    }

    /**
     * @param $name
     * @return mixed
     */
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

    /**
     * @param $account
     * @param $files
     */
    private function exportItemTranslationFields($account, $files) {
        $db = $this->db;
        $data = array();
        $selectFields = array();
        $attributeValueHeader = array();
        $translationJoins = array();
        $select = $db->select();
        foreach ($this->_config->getAccountLanguages($account) as $shop_id => $language) {
            $select->joinLeft(array("t_{$language}" => "s_articles_translations"), "t_{$language}.articleID = sa.id AND t_{$language}.languageID = {$shop_id}", array());
            $translationJoins[$shop_id] = "t_{$language}";
            foreach ($this->translationFields as $field) {
                if(!isset($attributeValueHeader[$field])){
                    $attributeValueHeader[$field] = array();
                }
                $column = "{$field}_{$language}";
                $attributeValueHeader[$field][$language] = $column;
                $mainTableRef = strpos($field, 'attr') !== false ? 'b.' . $field : 'sa.' . $field;
                $translationRef = 't_' . $language . '.' . $field;
                $selectFields[$column] = new \Zend_Db_Expr("CASE WHEN {$translationRef} IS NULL OR CHAR_LENGTH({$translationRef}) < 1 THEN {$mainTableRef} ELSE {$translationRef} END");
            }
        }
        $selectFields[] = 'a.id';
        $header = true;
        $countMax = 2000000;
        $limit = 1000000;
        $doneCases = array();
        $log = true;
        $totalCount = 0;
        $start = microtime(true);
        $page = 1;
        $select->from(array('sa' => 's_articles'), $selectFields)
            ->join(array('a' => 's_articles_details'), 'a.articleID = sa.id', array())
            ->joinLeft(array('b' => 's_articles_attributes'), 'a.id = b.articledetailsID', array())
            ->order('sa.id');

        while($countMax > $totalCount + $limit) {
            $sql = clone $select;
            $sql->limit($limit, ($page - 1) * $limit);

            if ($this->delta) {
                $sql->where('a.articleID IN(?)', $this->deltaIds);
            }

            $currentCount = 0;
            $this->log->info("Translation query: " . $db->quote($sql));
            $stmt = $db->query($sql);
            if($stmt->rowCount()) {
                while ($row = $stmt->fetch()) {
                    $currentCount++;
                    if($currentCount%10000 == 0 || $log) {
                        $end = (microtime(true) - $start) * 1000;
                        $this->log->info("Translation process at count: {$currentCount}, took: {$end} ms, memory: " . memory_get_usage(true));
                        $log = false;
                    }
                    if(!isset($this->shopProductIds[$row['id']])) {
                        continue;
                    }
                    if(isset($doneCases[$row['id']])){
                        continue;
                    }
                    if($header) {
                        $data[] = array_keys($row);
                        $header = false;
                    }
                    $data[] = $row;
                    $doneCases[$row['id']] = true;
                    $totalCount++;
                    if(sizeof($data) > 1000) {
                        $files->savePartToCsv('product_translations.csv', $data);
                        $data = [];
                    }
                }
            } else {
                break;
            }
            if($currentCount < $limit-1) {
                break;
            }
            $files->savepartToCsv('product_translations.csv', $data);
            $page++;
        }

        $files->savepartToCsv('product_translations.csv', $data);
        $doneCases = null;
        $attributeSourceKey = $this->bxData->addCSVItemFile($files->getPath('product_translations.csv'), 'id');
        $end = (microtime(true) - $start) * 1000;
        $this->log->info("Translation process finished and took: {$end} ms, memory: " . memory_get_usage(true));
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

    /**
     * @param $account
     * @param $files
     */
    private function exportItemCategories($account, $files) {

        $db = $this->db;
        $categories = array();
        $header = true;
        $languages = $this->_config->getAccountLanguages($account);
        $select = $db->select()->from(array('c' => 's_categories'), array('id', 'parent', 'description', 'path'));
        $stmt = $db->query($select);
        $this->log->info("BxIndexLog: Preparing products - start categories.");
        if($stmt->rowCount()) {
            while($r = $stmt->fetch()){
                $value = $r['description'];
                $category = array('category_id' => $r['id'], 'parent_id' => $r['parent']);
                foreach ($languages as $language) {
                    $category['value_' . $language] = $value;
                    if($header) {
                        $language_headers[$language] = "value_$language";
                    }
                }
                if($header) {
                    $categories[] = array_keys($category);
                    $header = false;
                }
                $categories[$r['id']] = $category;
            }
        }
        $this->log->info("BxIndexLog: Preparing products - end categories.");
        $files->savePartToCsv('categories.csv', $categories);
        $categories = null;
        $this->bxData->addCategoryFile($files->getPath('categories.csv'), 'category_id', 'parent_id', $language_headers);
        $language_headers = null;
        $data = array();
        $doneCases = array();
        $header = true;
        $categoryShopIds = $this->_config->getShopCategoryIds($account);

        $this->log->info("BxIndexLog: Preparing products - start product categories.");
        foreach ($languages as $shop_id => $language) {
            $category_id = $categoryShopIds[$shop_id];
            $sql = $db->select()
                ->from(array('ac' => 's_articles_categories_ro'), array())
                ->join(
                    array('d' => 's_articles_details'),
                    $this->qi('d.articleID') . ' = ' . $this->qi('ac.articleID') . ' AND ' .
                    $this->qi('d.kind') . ' <> ' . $db->quote(3),
                    array('d.id', 'ac.categoryID')
                )
                ->joinLeft(array('c' => 's_categories'), 'ac.categoryID = c.id', array())
                ->where('c.path LIKE \'%|' . $category_id . '|%\'');
            if ($this->delta) {
                $sql->where('d.articleID IN(?)', $this->deltaIds);
            }
            $stmt = $db->query($sql);
            if($stmt->rowCount()) {
                while ($row = $stmt->fetch()) {
                    $key = $row['id'] . '_' . $row['categoryID'];
                    if(isset($doneCases[$key])) {
                        continue;
                    }
                    $doneCases[$key] = true;
                    if($header) {
                        $data[] = array_keys($row);
                        $header = false;
                    }
                    $data[] = $row;
                    if(sizeof($data) > 10000) {
                        $files->savePartToCsv('product_categories.csv', $data);
                        $data = [];
                    }
                }
                if(sizeof($data)>0) {
                    $files->savePartToCsv('product_categories.csv', $data);
                }
                continue;
            } else {
                break;
            }
        }

        $this->log->info("BxIndexLog: Preparing products - end product categories.");
        $doneCases = null;
        $productToCategoriesSourceKey = $this->bxData->addCSVItemFile($files->getPath('product_categories.csv'), 'id');
        $this->bxData->setCategoryField($productToCategoriesSourceKey, 'categoryID');
    }

    /**
     * @param $account
     * @param $files
     * @return bool
     */
    private function exportMainProducts($account, $files) {

        $db = $this->db;
        $product_attributes = $this->getProductAttributes($account);
        $product_properties = array_flip($product_attributes);

        $countMax = 100000000;
        $limit = 1000000;
        $header = true;
        $data = array();
        $categoryShopIds = $this->_config->getShopCategoryIds($account);
        $main_shop_id = $this->_config->getAccountStoreId($account);
        $startforeach = microtime(true);
        foreach ($this->_config->getAccountLanguages($account) as $shop_id => $language) {
            $logCount = 0;
            $log = true;
            $totalCount = 0;
            $page = 1;
            $category_id = $categoryShopIds[$shop_id];
            while ($countMax > $totalCount + $limit) {
                $sql = $db->select()
                    ->from(array('s_articles'), $product_properties)
                    ->join(array('s_articles_details'), 's_articles_details.articleID = s_articles.id', array())
                    ->join(array('s_articles_attributes'), 's_articles_attributes.articledetailsID = s_articles_details.id', array())
                    ->join(array('s_articles_categories'), 's_articles_categories.articleID = s_articles_details.articleID', array())
                    ->joinLeft(array('s_articles_prices'), 's_articles_prices.articledetailsID = s_articles_details.id', array('price'))
                    ->joinLeft(array('s_categories'), 's_categories.id = s_articles_categories.categoryID', array())
                    ->where('s_articles.mode = ?', 0)
                    ->where('s_categories.path LIKE \'%|' . $category_id . '|%\'')
                    ->limit($limit, ($page - 1) * $limit)
                    ->group('s_articles_details.id')
                    ->order('s_articles.id');
                if ($this->delta) {
                    $sql->where('s_articles.changetime > ?', $this->getLastDelta());
                }
                $start = microtime(true);
                $stmt = $db->query($sql);
                $currentCount = 0;
                if ($stmt->rowCount()) {
                    while ($row = $stmt->fetch()) {
                        $currentCount++;
                        if($log) {
                            $end = (microtime(true) - $start) * 1000;
                            $this->log->info("Main product query (shop:$shop_id) took: $end ms, memory: " . memory_get_usage(true));
                            $log = false;
                        }
                        if (is_null($row['price'])) {
                            continue;
                        }
                        if(isset($this->shopProductIds[$row['id']])) {
                            $this->shopProductIds[$row['id']] .= "|$shop_id";
                            continue;
                        }
                        $this->shopProductIds[$row['id']] = $shop_id;
                        unset($row['price']);
                        $row['purchasable'] = ($row['laststock'] == 1 && $row['instock'] == 0) ? 0 : 1;
                        $row['immediate_delivery'] = ($row['instock'] >= $row['minpurchase']) ? 1 : 0;
                        if ($this->delta && !isset($this->deltaIds[$row['articleID']])) {
                            $this->deltaIds[$row['articleID']] = $row['articleID'];
                        }
                        $row['group_id'] = $row['articleID'];
                        if($header) {
                            $main_properties = array_keys($row);
                            $data[] = $main_properties;
                            $header = false;
                        }
                        $data[] = $row;
                        $totalCount++;
                        if(sizeof($data)  > 1000){
                            $files->savePartToCsv('products.csv', $data);
                            $data = [];
                        }
                    }
                    if($logCount++%5 == 0) {
                        $end = (microtime(true) - $start) * 1000;
                        $this->log->info("Main product data process (shop:$shop_id) took: $end ms, memory: " . memory_get_usage(true) . ", totalCount: $totalCount");
                        $log = true;
                    }
                } else {
                    if ($totalCount == 0 && $main_shop_id == $shop_id) {
                        return false;
                    }
                    break;
                }

                $files->savePartToCsv('products.csv', $data);
                $data = [];
                $page++;
                if($currentCount < $limit -1) {
                    break;
                }
            }
        }
        $end =  (microtime(true) - $startforeach) * 1000;
        $this->log->info("All shops for main product took: $end ms, memory: " . memory_get_usage(true));
        $mainSourceKey = $this->bxData->addMainCSVItemFile($files->getPath('products.csv'), 'id');
        $this->bxData->addSourceStringField($mainSourceKey, 'bx_purchasable', 'purchasable');
        $this->bxData->addSourceStringField($mainSourceKey, 'immediate_delivery', 'immediate_delivery');
        $this->bxData->addSourceStringField($mainSourceKey, 'bx_type', 'id');
        $pc_field = $this->_config->isVoucherExportEnabled($account) ?
            'CASE WHEN group_id IS NULL THEN CASE WHEN %%LEFTJOINfield_products_voucher_id%% IS NULL THEN "blog" ELSE "voucher" END ELSE "product" END AS final_value' :
            'CASE WHEN group_id IS NULL THEN "blog" ELSE "product" END AS final_value';
        $this->bxData->addFieldParameter($mainSourceKey, 'bx_type', 'pc_fields', $pc_field);
        $this->bxData->addFieldParameter($mainSourceKey, 'bx_type', 'multiValued', 'false');

        foreach ($main_properties as $property) {

            if ($property == 'id') {
                continue;
            }
            if ($property == 'sales') {
                $this->bxData->addSourceNumberField($mainSourceKey, $property, $property);
                $this->bxData->addFieldParameter($mainSourceKey, $property, 'multiValued', 'false');
                continue;
            }
            $this->bxData->addSourceStringField($mainSourceKey, $property, $property);
            if ($property == 'group_id' || $property == 'releasedate' || $property == 'datum' || $property == 'changetime') {
                $this->bxData->addFieldParameter($mainSourceKey, $property, 'multiValued', 'false');
            }
        }

        $data[] = ["id", "shop_id"];
        foreach ($this->shopProductIds as $id => $shopIds) {
            $data[] = [$id, $shopIds];
            $this->shopProductIds[$id] = true;
        }
        $files->savePartToCsv('product_shop.csv', $data);
        $data = null;
        $sourceKey = $this->bxData->addCSVItemFile($files->getPath('product_shop.csv'), 'id');
        $this->bxData->addSourceStringField($sourceKey, 'shop_id', 'shop_id');
        $this->bxData->addFieldParameter($sourceKey,'shop_id', 'splitValues', '|');
        return true;
    }

    /**
     * @param $account
     * @return mixed
     */
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

    /**
     * @param $account
     * @return mixed
     */
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

    /**
     * @param $account
     * @return mixed
     */
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
        $filteredAttributes['s_articles.active'] = 'bx_parent_active';
        return $filteredAttributes;
    }

    /**
     * @param $account
     * @param $files
     */
    private function exportCustomers($account, $files) {
        $this->log->debug("start collecting customers for account {$account}");
        $db = $this->db;
        $customer_attributes = $this->getCustomerAttributes($account);
        $customer_properties = array_flip($customer_attributes);
        $header = true;
        $firstShop = true;
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
                    if ($totalCount == 0 && $firstShop) {
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
            $firstShop = false;
        }

        $customerSourceKey = $this->bxData->addMainCSVCustomerFile($files->getPath('customers.csv'), 'id');
        foreach ($customer_attributes as $attribute) {
            if ($attribute == 'id') continue;
            $this->bxData->addSourceStringField($customerSourceKey, $attribute, $attribute);
        }
        $this->log->info('BxIndexLog: Customer export finished for account: ' . $account);
    }

    /**
     * @param $account
     * @param $files
     */
    private function exportTransactions($account, $files) {

        $db = $this->db;
        $transaction_attributes = $this->getTransactionAttributes($account);
        $transaction_properties = array_flip($transaction_attributes);

        $quoted2 = $db->quote(2);
        $oInvoiceAmount = $this->qi('s_order.invoice_amount');
        $oInvoiceShipping = $this->qi('s_order.invoice_shipping');
        $oCurrencyFactor = $this->qi('s_order.currencyFactor');
        $dPrice = $this->qi('s_order_details.price');
        $transaction_properties = array_merge($transaction_properties,
            array(
                'total_order_value' => new \Zend_Db_Expr(
                    "ROUND($oInvoiceAmount * $oCurrencyFactor, $quoted2)"),
                'shipping_costs' => new \Zend_Db_Expr(
                    "ROUND($oInvoiceShipping * $oCurrencyFactor, $quoted2)"),
                'price' => new \Zend_Db_Expr(
                    "ROUND($dPrice * $oCurrencyFactor, $quoted2)")
            )
        );

        $header = true;
        $data = array();
        $countMax = 10000000;
        $limit = 5000;
        $totalCount = 0;
        $date = date("Y-m-d H:i:s", strtotime("-1 month"));
        $mode = $this->_config->getTransactionMode($account);
        $firstShop = true;
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
                    ->joinLeft(
                        array('a_d' => 's_articles_details'),
                        $this->qi('a_d.ordernumber') . ' = ' . $this->qi('s_order_details.articleordernumber'),
                        array('articledetailsID' => 'id')
                    )
                    ->where($this->qi('s_order.subshopID') . ' = ?', $shop_id)
                    ->limit($limit, ($page - 1) * $limit)
                    ->order('s_order.ordertime DESC');
                if ($mode == 1) {
                    $sql->where('s_order.ordertime >= ?', $date);
                }
                $stmt = $db->query($sql);

                if ($stmt->rowCount()) {

                    while ($row = $stmt->fetch()) {
                        // @note list price at the time of the order is not stored, only the final price
                        $row['discounted_price'] = $row['price'];
                        $data[] = $row;
                        $totalCount++;
                    }
                } else {
                    if ($totalCount == 0 && $firstShop){
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
            $firstShop = false;
        }
        $this->bxData->setCSVTransactionFile($files->getPath('transactions.csv'), 'id', 'articledetailsID', 'userID', 'ordertime', 'total_order_value', 'price', 'discounted_price');
    }


    /**
     * @return string
     */
    protected function getLastDelta() {
        if (empty($this->deltaLast)) {
            $this->deltaLast = date("Y-m-d H:i:s", strtotime("-30 minutes"));
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

    /**
     *
     */
    private function updateExportTable() {
        $this->db->query('TRUNCATE `exports`');
        $this->db->query('INSERT INTO `exports` values(NOW())');
    }

    /**
     * @param $account
     * @param $files
     */
    private function exportVouchers($account, $files){

        $db = Shopware()->Db();
        $languages = $this->_config->getAccountLanguages($account);
        $header = true;
        $data = array();
        $doneCases = array();
        $headers = array();
        foreach ($languages as $shop_id => $language) {
            $sql = $db->select()->from(array('v' => 's_emarketing_vouchers'),
                array('v.*',
                    'used_codes' => new \Zend_Db_Expr("IF( modus = '0',
                (SELECT count(*) FROM s_order_details as d WHERE articleordernumber =v.ordercode AND d.ordernumber!='0'),
                (SELECT count(*) FROM s_emarketing_voucher_codes WHERE voucherID =v.id AND cashed=1))")))
                ->where('v.subshopID IS NULL OR v.subshopID = ?', $shop_id);
            $stmt = $db->query($sql);
            if($stmt->rowCount()) {
                while($row = $stmt->fetch()){
                    if($header) {
                        $headers = array_keys($row);
                        $data[] = $headers;
                        $header = false;
                    }
                    if(isset($doneCases[$row['id']])) continue;
                    $doneCases[$row['id']] = true;
                    $row['id'] = 'voucher_' . $row['id'];
                    $data[] = $row;
                }
            }
            if(sizeof($data)) {
                $files->savePartToCsv('voucher.csv', $data);
            }
        }

        if($header) {
            $data = ['id','description','vouchercode','numberofunits','value','minimumcharge','shippingfree',
                'bindtosupplier','valid_from','valid_to','ordercode','modus','percental','numorder','customergroup',
                'restrictarticles','strict','subshopID','taxconfig','customer_stream_ids','used_codes'];
            $files->savePartToCsv('voucher.csv', $data);
        }
        $attributeSourceKey = $this->bxData->addCSVItemFile($files->getPath('voucher.csv'), 'id');
        $this->bxData->addSourceParameter($attributeSourceKey, 'additional_item_source', 'true');
        foreach ($headers as $header){
            $this->bxData->addSourceStringField($attributeSourceKey, 'voucher_'.$header, $header);
        }
        $data = array();
        $header = true;
        $sql = $db->select()->from(array('v_c' => 's_emarketing_voucher_codes'));
        $stmt = $db->query($sql);
        if($stmt->rowCount()) {
            while($row = $stmt->fetch()){
                if(isset($doneCases[$row['voucherID']])){
                    if($header){
                        $data[] = array_keys($row);
                        $header = false;
                    }
                    $row['voucherID'] = 'voucher_' . $row['voucherID'];
                    $data[] = $row;
                }
            }
        }
        $doneCases = array();
        $files->savePartToCsv('voucher_codes.csv', $data);
        $this->bxData->addCSVItemFile($files->getPath('voucher_codes.csv'), 'id');
    }
}