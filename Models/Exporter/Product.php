<?php
namespace Boxalino\Models\Exporter;

use Boxalino\Models\Exporter\Item\Blog;
use Boxalino\Models\Exporter\Item\Brand;
use Boxalino\Models\Exporter\Item\Category;
use Boxalino\Models\Exporter\Item\Facet;
use Boxalino\Models\Exporter\Item\Images;
use Boxalino\Models\Exporter\Item\Price;
use Boxalino\Models\Exporter\Item\Stream;
use Boxalino\Models\Exporter\Item\Translation;
use Boxalino\Models\Exporter\Item\Url;
use Boxalino\Models\Exporter\Item\Votes;
use Boxalino\Models\Exporter\Item\Voucher;

class Product implements ExporterInterface
{

    CONST BOXALINO_EXPORT_PRODUCT_SHOP_CSV = "product-shop.csv";
    CONST BOXALINO_EXPORT_PRODUCTS_CSV = "products.csv";

    protected $config;
    protected $account;
    protected $files;
    protected $bxData;
    protected $log;
    protected $delta;
    protected $success;
    protected $db;

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

    public function __construct()
    {
        $this->db = Shopware()->Db();
        $this->log = Shopware()->PluginLogger();
    }

    public function export()
    {
        set_time_limit(7200);
        $this->log->info("BxIndexLog: Preparing products - main.");
        $this->exportProducts();
        $this->log->info("Main product after memory: " . memory_get_usage(true));

        $this->log->info("BxIndexLog: Finished products - main.");
        $this->exportExtra();
        $this->log->info("BxIndexLog: Finished P R O D U C T S.");
    }

    private function exportProducts()
    {
        $db = $this->db;
        $product_attributes = $this->getProductAttributes();
        $product_properties = array_flip($product_attributes);

        $countMax = 100000000;
        $limit = 1000000;
        $header = true;
        $data = array();

        $categoryShopIds = $this->config->getShopCategoryIds($this->account);
        $main_shop_id = $this->config->getAccountStoreId($this->account);
        $startforeach = microtime(true);

        foreach ($this->config->getAccountLanguages($this->account) as $shop_id => $language) {
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
                            $this->files->savePartToCsv(self::BOXALINO_EXPORT_PRODUCTS_CSV, $data);
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
                        $this->setSuccess(false);
                        return false;
                    }
                    break;
                }

                $this->files->savePartToCsv(self::BOXALINO_EXPORT_PRODUCTS_CSV, $data);
                $data = [];
                $page++;
                if($currentCount < $limit -1) {
                    break;
                }
            }
        }
        $end =  (microtime(true) - $startforeach) * 1000;
        $this->log->info("All shops for main product took: $end ms, memory: " . memory_get_usage(true));
        $mainSourceKey = $this->bxData->addMainCSVItemFile($this->files->getPath(self::BOXALINO_EXPORT_PRODUCTS_CSV), 'id');
        $this->bxData->addSourceStringField($mainSourceKey, 'bx_purchasable', 'purchasable');
        $this->bxData->addSourceStringField($mainSourceKey, 'immediate_delivery', 'immediate_delivery');
        $this->bxData->addSourceStringField($mainSourceKey, 'bx_type', 'id');

        $pc_field = $this->config->isVoucherExportEnabled($this->account) ?
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
        $this->files->savePartToCsv(self::BOXALINO_EXPORT_PRODUCT_SHOP_CSV, $data);
        $data = null;
        $sourceKey = $this->bxData->addCSVItemFile($this->files->getPath(self::BOXALINO_EXPORT_PRODUCT_SHOP_CSV), 'id');
        $this->bxData->addSourceStringField($sourceKey, 'shop_id', 'shop_id');
        $this->bxData->addFieldParameter($sourceKey,'shop_id', 'splitValues', '|');

        $this->setSuccess(true);
    }


    private function exportExtra()
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

        $exporter = new Brand();
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

        if (!$this->delta) //it means it is a full export
        {
            $exporter = new Blog();
            $this->_exportExtra("blog", $exporter);
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

    private function _exportExtra($step, $exporter)
    {
        $this->log->info("BxIndexLog: Preparing products - {$step}.");
        $exporter->setAccount($this->account)->setFiles($this->files)->setShopProductIds($this->shopProductIds);
        $exporter->export();
        $this->log->info("{$step}Exporter after memory: " . memory_get_usage(true));
        $this->log->info("BxIndexLog: Finished products - {$step}.");
    }

    /**
     * @param $account
     * @return mixed
     */
    private function getProductAttributes() {

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
        $filteredAttributes = $this->config->getAccountProductsProperties($this->account, $all_attributes, $requiredProperties);
        $filteredAttributes['s_articles.active'] = 'bx_parent_active';

        return $filteredAttributes;
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

    public function setSuccess($success)
    {
        $this->success = $success;
        return $this;
    }

    public function getSuccess()
    {
        return $this->success;
    }

    /**
     * @param mixed $config
     * @return Product
     */
    public function setConfig($config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @param mixed $account
     * @return Product
     */
    public function setAccount($account)
    {
        $this->account = $account;
        return $this;
    }

    /**
     * @param mixed $files
     * @return Product
     */
    public function setFiles($files)
    {
        $this->files = $files;
        return $this;
    }

    /**
     * @param mixed $bxData
     * @return Product
     */
    public function setBxData($bxData)
    {
        $this->bxData = $bxData;
        return $this;
    }

    /**
     * @param mixed $type
     * @return Product
     */
    public function setDelta($type)
    {
        $this->delta = $type;
        return $this;
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
}