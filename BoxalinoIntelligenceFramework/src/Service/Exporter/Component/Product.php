<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Component;

use Boxalino\IntelligenceFramework\Service\Exporter\Item\Manufacturer;
use Boxalino\IntelligenceFramework\Service\Exporter\Item\Category;
use Boxalino\IntelligenceFramework\Service\Exporter\Item\Facet;
use Boxalino\IntelligenceFramework\Service\Exporter\Item\Images;
use Boxalino\IntelligenceFramework\Service\Exporter\Item\Price;
use Boxalino\IntelligenceFramework\Service\Exporter\Item\Stream;
use Boxalino\IntelligenceFramework\Service\Exporter\Item\Translation;
use Boxalino\IntelligenceFramework\Service\Exporter\Item\Url;
use Boxalino\IntelligenceFramework\Service\Exporter\Item\Votes;
use Boxalino\IntelligenceFramework\Service\Exporter\Item\Voucher;


class Product extends ExporterComponentAbstract
{

    CONST EXPORTER_COMPONENT_MAIN_FILE = "products.csv";
    const EXPORTER_COMPONENT_TYPE = "products";

    CONST BOXALINO_EXPORT_PRODUCT_SHOP_CSV = "product-shop.csv";
    CONST BOXALINO_EXPORT_PRODUCTS_CSV = "products.csv";

    protected $deltaLast;
    protected $shopProductIds = array();
    protected $deltaIds = array();

    /**
     * @var array
     * @TODO read fields from export configuration table
     */
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
     * @deprecated
     */
    protected $extraSteps = array();


    /**
     * @return mixed
     */
    public function export()
    {
        set_time_limit(7200);
        $account = $this->getAccount();
        $this->log->info("BxIndexLog: Preparing products - main.");
        $export_products = $this->exportMainProducts();
        $this->log->info("BxIndexLog: -- Main product after memory: " . memory_get_usage(true));

        $this->log->info("BxIndexLog: Finished products - main.");
        if ($export_products) {
            $this->log->info("BxIndexLog: Preparing products - categories.");
            $this->exportItemCategories();
            $this->log->info("BxIndexLog: -- exportItemCategories after memory: " . memory_get_usage(true));
            $this->log->info("BxIndexLog: Finished products - categories.");
            $this->log->info("BxIndexLog: Preparing products - translations.");
            $this->exportItemTranslationFields();
            $this->log->info("BxIndexLog: -- exportItemTranslationFields after memory: " . memory_get_usage(true));
            $this->log->info("BxIndexLog: Finished products - translations.");
            $this->log->info("BxIndexLog: Preparing products - brands.");
            $this->exportItemBrands();
            $this->log->info("BxIndexLog: -- exportItemBrands after memory: " . memory_get_usage(true));
            $this->log->info("BxIndexLog: Finished products - brands.");
            $this->log->info("BxIndexLog: Preparing products - facets.");
            $this->exportItemFacets();
            $this->log->info("BxIndexLog: -- exportItemFacets after memory: " . memory_get_usage(true));
            $this->log->info("BxIndexLog: Finished products - facets.");
            $this->log->info("BxIndexLog: Preparing products - price.");
            $this->exportItemPrices();
            $this->log->info("BxIndexLog: -- exportItemPrices after memory: " . memory_get_usage(true));
            $this->log->info("BxIndexLog: Finished products - price.");
            if ($this->config->exportProductImages($account)) {
                $this->log->info("BxIndexLog: Preparing products - image.");
                $this->exportItemImages();
                $this->log->info("BxIndexLog: -- exportItemImages after memory: " . memory_get_usage(true));
                $this->logger->info("BxIndexLog: Finished products - image.");
            }
            if ($this->config->exportProductUrl($account)) {
                $this->logger->info("BxIndexLog: Preparing products - url.");
                $this->exportItemUrls();
                $this->logger->info("exportItemUrls after memory: " . memory_get_usage(true));
                $this->logger->info("BxIndexLog: Finished products - url.");
            }
            if(!$this->delta) {
                $this->logger->info("BxIndexLog: Preparing products - blogs.");
                $this->exportItemBlogs();
                $this->logger->info("BxIndexLog: -- exportItemBlogs after memory: " . memory_get_usage(true));
                $this->logger->info("BxIndexLog: Finished products - blogs.");
            }
            $this->logger->info("BxIndexLog: Preparing products - votes.");
            $this->exportItemVotes();
            $this->logger->info("BxIndexLog: -- exportItemVotes after memory: " . memory_get_usage(true));
            $this->logger->info("BxIndexLog: Finished products - votes.");
            $this->logger->info("BxIndexLog: Preparing products - product streams.");
            $this->exportProductStreams();
            $this->logger->info("BxIndexLog: -- exportProductStreams after memory: " . memory_get_usage(true));
            $this->logger->info("BxIndexLog: Finished products - product streams.");
            if ($this->config->isVoucherExportEnabled($account)) {
                $this->logger->info("BxIndexLog: Preparing products - voucher.");
                $this->logger->info("BxIndexLog: Preparing vouchers.");
                $this->exportVouchers();
                $this->logger->info("BxIndexLog: -- exportVouchers after memory: " . memory_get_usage(true));
                $this->logger->info("BxIndexLog: Finished products - voucher.");
            }

            $this->logger->info("BxIndexLog: Products - exporting additional tables for account: {$account}");
            $this->exportExtraTables('products', $this->config->getAccountExtraTablesByEntityType($account,'products'));
        }

        return $export_products;
    }


    /**
     * Export products as they are in an unified view
     * Create products.csv
     *
     * @return bool
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Db_Statement_Exception
     */
    public function exportMainProducts()
    {
        $db = $this->db;
        $account = $this->getAccount();
        $files = $this->getFiles();
        $product_attributes = $this->getProductAttributes($account);
        $product_properties = array_flip($product_attributes);

        $countMax = 100000000;
        $limit = 1000000;
        $header = true;
        $data = array();
        $categoryShopIds = $this->config->getShopCategoryIds($account);
        $main_shop_id = $this->config->getAccountStoreId($account);
        $startforeach = microtime(true);
        foreach ($this->config->getAccountLanguages($account) as $shop_id => $language)
        {
            $logCount = 0;
            $log = true;
            $totalCount = 0;
            $page = 1;
            $category_id = $categoryShopIds[$shop_id];
            while ($countMax > $totalCount + $limit)
            {
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
                            $this->logger->info("BxIndexLog: -- Main product query (shop:$shop_id) took: $end ms, memory: " . memory_get_usage(true));
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
                        $row['purchasable'] = $this->getProductPurchasableValue($row);
                        $row['immediate_delivery'] = $this->getProductImmediateDeliveryValue($row);
                        if ($this->delta && !isset($this->deltaIds[$row['articleID']])) {
                            $this->deltaIds[$row['articleID']] = $row['articleID'];
                        }
                        $row['group_id'] = $this->getProductGroupValue($row);
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
                        $this->logger->info("Main product data process (shop:$shop_id) took: $end ms, memory: " . memory_get_usage(true) . ", totalCount: $totalCount");
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
        $this->logger->info("All shops for main product took: $end ms, memory: " . memory_get_usage(true));
        $mainSourceKey = $this->getLibrary()->addMainCSVItemFile($files->getPath('products.csv'), 'id');
        $this->getLibrary()->addSourceStringField($mainSourceKey, 'bx_purchasable', 'purchasable');
        $this->getLibrary()->addSourceStringField($mainSourceKey, 'immediate_delivery', 'immediate_delivery');
        $this->getLibrary()->addSourceStringField($mainSourceKey, 'bx_type', 'id');
        $pc_field = $this->config->isVoucherExportEnabled($account) ?
            'CASE WHEN group_id IS NULL THEN CASE WHEN %%LEFTJOINfield_products_voucher_id%% IS NULL THEN "blog" ELSE "voucher" END ELSE "product" END AS final_value' :
            'CASE WHEN group_id IS NULL THEN "blog" ELSE "product" END AS final_value';
        $this->getLibrary()->addFieldParameter($mainSourceKey, 'bx_type', 'pc_fields', $pc_field);
        $this->getLibrary()->addFieldParameter($mainSourceKey, 'bx_type', 'multiValued', 'false');

        foreach ($main_properties as $property)
        {
            if ($property == 'id') {
                continue;
            }
            if ($property == 'sales') {
                $this->getLibrary()->addSourceNumberField($mainSourceKey, $property, $property);
                $this->getLibrary()->addFieldParameter($mainSourceKey, $property, 'multiValued', 'false');
                continue;
            }
            $this->getLibrary()->addSourceStringField($mainSourceKey, $property, $property);
            if ($property == 'group_id' || $property == 'releasedate' || $property == 'datum' || $property == 'changetime') {
                $this->getLibrary()->addFieldParameter($mainSourceKey, $property, 'multiValued', 'false');
            }
        }

        $data[] = ["id", "shop_id"];
        foreach ($this->shopProductIds as $id => $shopIds) {
            $data[] = [$id, $shopIds];
            $this->shopProductIds[$id] = true;
        }
        $this->files->savePartToCsv('product_shop.csv', $data);
        $data = null;
        $sourceKey = $this->getLibrary()->addCSVItemFile($this->files->getPath('product_shop.csv'), 'id');
        $this->getLibrary()->addSourceStringField($sourceKey, 'shop_id', 'shop_id');
        $this->getLibrary()->addFieldParameter($sourceKey,'shop_id', 'splitValues', '|');

        return true;
    }

    /**
     * Getting a list of product attributes and the table it comes from
     * To be used in the general SQL select
     *
     * @return array
     */
    public function getProductAttributes()
    {
        $account = $this->getAccount();
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


    protected function exportExtra()
    {
        if (!$this->getSuccess())
        {
            return $this;
        }
        
        $exporter = new Category();
        $this->_exportExtra("categories", $exporter);
        unset($exporter);

        $exporter = new Translation();
        $this->_exportExtra("translations", $exporter);
        unset($exporter);

        $exporter = new Manufacturer();
        $this->_exportExtra("brands", $exporter);
        unset($exporter);

        $exporter = new Facet();
        $this->_exportExtra("facets", $exporter);
        unset($exporter);

        $exporter = new Price();
        $this->_exportExtra("prices", $exporter);
        unset($exporter);

        // @TODO check config the new way
        if ($this->config->exportProductImages($this->account))
        {
            $exporter = new Images();
            $this->_exportExtra("images", $exporter);
            unset($exporter);
        }

        // @TODO check config the new way
        if ($this->config->exportProductUrl($this->account))
        {
            $exporter = new Url();
            $this->_exportExtra("urls", $exporter);
            unset($exporter);
        }

        $exporter = new Votes();
        $this->_exportExtra("votes", $exporter);
        unset($exporter);

        $exporter = new Stream();
        $this->_exportExtra("productStreams", $exporter);
        unset($exporter);

        // @TODO check config the new way
        if ($this->config->isVoucherExportEnabled($this->account)) {
            $exporter = new Voucher();
            $this->_exportExtra("vouchers", $exporter);
            unset($exporter);
        }

        return true;
    }

    protected function _exportExtra($step, $exporter)
    {
        $this->logger->info("BxIndexLog: Preparing products - {$step}.");
        $exporter->setAccount($this->account)->setFiles($this->files)->setShopProductIds($this->shopProductIds);
        $exporter->export();
        $this->logger->info("{$step}Exporter after memory: " . memory_get_usage(true));
        $this->logger->info("BxIndexLog: Finished products - {$step}.");
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
     * @deprecated
     * @param $step
     * @param $exporter
     * @return $this
     */
    public function addExtraStep($step, $exporter)
    {
        if(isset($this->extraSteps[$step]))
        {
            $this->extraSteps[$step] = $exporter;
        }

        return $this;
    }

    /**
     * Product purchasable logic depending on the default filter
     *
     * @param $row
     * @return int
     */
    public function getProductPurchasableValue($row)
    {
        if($row['laststock'] == 1 && $row['instock'] == 0)
        {
            return 0;
        }

        return 1;
    }

    /**
     * Product immediate delivery logic as per default facet handler logic
     *
     * @see Shopware\Bundle\SearchBundleDBAL\FacetHandler\ImmediateDeliveryFacetHandler
     * @param $row
     * @return int
     */
    public function getProductImmediateDeliveryValue($row)
    {
        if($row['instock'] >= $row['minpurchase'])
        {
            return 1;
        }

        return 0;
    }

    /**
     * Group product value per solr logic
     *
     * @param $row
     * @return mixed
     */
    public function getProductGroupValue($row)
    {
        return $row['articleID'];
    }
}