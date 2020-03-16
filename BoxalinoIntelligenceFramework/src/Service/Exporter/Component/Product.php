<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Component;

use Boxalino\IntelligenceFramework\Service\Exporter\ExporterScheduler;
use Boxalino\IntelligenceFramework\Service\Exporter\Item\Manufacturer;
use Boxalino\IntelligenceFramework\Service\Exporter\Item\Category;
use Boxalino\IntelligenceFramework\Service\Exporter\Item\Facet;
use Boxalino\IntelligenceFramework\Service\Exporter\Item\Media;
use Boxalino\IntelligenceFramework\Service\Exporter\Item\Price;
use Boxalino\IntelligenceFramework\Service\Exporter\Item\Stream;
use Boxalino\IntelligenceFramework\Service\Exporter\Item\Translation;
use Boxalino\IntelligenceFramework\Service\Exporter\Item\Url;
use Boxalino\IntelligenceFramework\Service\Exporter\Item\Review;
use Boxalino\IntelligenceFramework\Service\Exporter\Item\Visibility;
use Boxalino\IntelligenceFramework\Service\Exporter\Util\Configuration;
use Boxalino\IntelligenceFramework\Service\Exporter\Item\Tag;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Psr\Log\LoggerInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class Product
 * Product component exporting logic
 *
 * @package Boxalino\IntelligenceFramework\Service\Exporter\Component
 */
class Product extends ExporterComponentAbstract
{

    CONST EXPORTER_LIMIT = 10000000;
    CONST EXPORTER_STEP = 10000;
    CONST EXPORTER_DATA_SAVE_STEP = 1000;

    CONST EXPORTER_COMPONENT_MAIN_FILE = "products.csv";
    CONST EXPORTER_COMPONENT_TYPE = "products";
    CONST EXPORTER_COMPONENT_ID_FIELD = "id";

    CONST BOXALINO_EXPORT_PRODUCT_SHOP_CSV = "product-shop.csv";

    protected $lastExport;
    protected $exportedProductIds = [];
    protected $deltaIds = [];

    /**
     * @var bool
     */
    protected $successOnComponentExport = false;

    /**
     * @var Category
     */
    protected $categoryExporter;

    /**
     * @var Facet
     */
    protected $facetExporter;

    /**
     * @var Media
     */
    protected $imagesExporter;

    /**
     * @var Manufacturer
     */
    protected $manufacturerExporter;

    /**
     * @var Price
     */
    protected $priceExporter;

    /**
     * @var Stream
     */
    protected $streamExporter;

    /**
     * @var Url
     */
    protected $urlExporter;

    /**
     * @var Review
     */
    protected $reviewsExporter;

    /**
     * @var Translation
     */
    protected $translationExporter;

    /**
     * @var Tag
     */
    protected $tagExporter;

    /**
     * @var Visibility
     */
    protected $visibilityExporter;

    /**
     * @deprecated
     */
    protected $extraSteps = [];

    public function __construct(
        ComponentResource $resource,
        Connection $connection,
        LoggerInterface $logger,
        Configuration $exporterConfigurator,
        Category $categoryExporter,
        Facet $facetExporter,
        Media $imagesExporter,
        Manufacturer $manufacturerExporter,
        Price $priceExporter,
        Stream $streamExporter,
        Url $urlExporter,
        Review $reviewsExporter,
        Translation $translationExporter,
        Tag $tagExporter,
        Visibility $visibilityExporter
    ){
        $this->categoryExporter = $categoryExporter;
        $this->facetExporter = $facetExporter;
        $this->imagesExporter = $imagesExporter;
        $this->manufacturerExporter = $manufacturerExporter;
        $this->priceExporter = $priceExporter;
        $this->streamExporter = $streamExporter;
        $this->urlExporter = $urlExporter;
        $this->reviewsExporter = $reviewsExporter;
        $this->translationExporter = $translationExporter;
        $this->tagExporter = $tagExporter;
        $this->visibilityExporter = $visibilityExporter;

        parent::__construct($resource, $connection, $logger, $exporterConfigurator);
    }


    public function exportComponent()
    {
        /** defaults */
        $header = true; $data = []; $totalCount = 0; $page = 1; $exportFields=[]; $startExport = microtime(true);
        $this->logger->info("BxIndexLog: Preparing products - MAIN.");
        $properties = $this->getFields();
        $rootCategoryId = $this->config->getChannelRootCategoryId($this->getAccount());
        $defaultLanguageId = $this->config->getChannelDefaultLanguageId($this->getAccount());
        $channelId = $this->config->getAccountChannelId($this->getAccount());

        while (self::EXPORTER_LIMIT > $totalCount + self::EXPORTER_STEP)
        {
            $query = $this->connection->createQueryBuilder();
            $query->select($properties)
                ->from('product', 'p')
                ->leftJoin('p', 'tax', 'tax', 'tax.id = product.tax_id')
                ->leftJoin('p', 'delivery_time_translation', 'delivery_time_translation',
                    'p.delivery_time_id = delivery_time_translation.delivery_time_id AND delivery_time_translation.language_id = :defaultLanguage')
                ->leftJoin('p', 'unit_translation', 'unit_translation', 'unit_translation.unit_id = p.unit_id AND unit_translation.language_id = :defaultLanguage')
                ->leftJoin('p', 'currency', 'currency', "JSON_UNQUOTE(JSON_EXTRACT(p.price->>'$.*.currencyId', '$[0]')) = LOWER(HEX(currency.id))")
                ->andWhere('p.version_id = :version')
                ->andWhere("JSON_SEARCH(p.category_tree, 'one', :channelRootCategoryId) IS NOT NULL")
                ->andWhere('p.price IS NOT NULL') #REMOVE PRODUCTS WHICH HAVE NO PRICE SET
                ->addGroupBy('p.id')
                ->orderBy('p.id', 'DESC')
                ->setParameter('version', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
                ->setParameter('channelRootCategoryId', $rootCategoryId, ParameterType::STRING)
                ->setParameter('defaultLanguage', Uuid::fromHexToBytes($defaultLanguageId), ParameterType::BINARY)
                ->setFirstResult(($page - 1) * self::EXPORTER_STEP)
                ->setMaxResults(self::EXPORTER_STEP);

            if ($this->getIsDelta()) {
                $query->andWhere('p.updated_at > :lastExport')
                    ->setParameter('lastExport', $this->getLastExport());
            }
            $count = $query->execute()->rowCount();
            $totalCount+=$count;
            if($totalCount == 0)
            {
                break; #return false;
            }
            $results = $this->processExport($query);
            foreach($results as $row)
            {
                if ($this->getIsDelta() && !isset($this->deltaIds[$row['id']])) {
                    $this->deltaIds[$row['id']] = $row['id'];
                }

                $this->exportedProductIds[$row['id']] = $channelId;
                $row['purchasable'] = $this->getProductPurchasableValue($row);
                $row['immediate_delivery'] = $this->getProductImmediateDeliveryValue($row);
                $row['group_id'] = $this->getProductGroupValue($row);
                if($header) {
                    $exportFields = array_keys($row);
                    $data[] = $exportFields;
                    $header = false;
                }
                $data[] = $row;
                if(count($data) > self::EXPORTER_DATA_SAVE_STEP)
                {
                    $this->getFiles()->savePartToCsv($this->getComponentMainFile(), $data);
                    $data = [];
                }
            }

            $this->getFiles()->savePartToCsv($this->getComponentMainFile(), $data);
            $data = []; $page++;
            if($totalCount < self::EXPORTER_STEP - 1) { break;}
        }

        $endExport =  (microtime(true) - $startExport) * 1000;
        $this->logger->info("BxIndexLog: MAIN PRODUCT DATA EXPORT TOOK: $endExport ms, memory: " . memory_get_usage(true));
        if($page==0)
        {
            $this->logger->info("BxIndexLog: NO PRODUCTS WERE FOUND FOR THE EXPORT.");
            $this->setSuccessOnComponentExport(false);
            return $this;
        }

        $this->defineOtherProperties($exportFields);

        $this->logger->info("BxIndexLog: -- Main product after memory: " . memory_get_usage(true));
        $this->logger->info("BxIndexLog: Finished products - main.");

        $this->setSuccessOnComponentExport(true);
        $this->exportItems();
    }

    /**
     * Export other product elements and properties (categories, translations, etc)
     *
     * @return $this
     * @throws \Exception
     */
    public function exportItems() : Product
    {
        if (!$this->getSuccessOnComponentExport())
        {
            return $this;
        }

        $this->_exportExtra("categories", $this->categoryExporter);
        $this->_exportExtra("translations", $this->translationExporter);
        $this->_exportExtra("manufacturers", $this->manufacturerExporter);
        $this->_exportExtra("facets", $this->facetExporter);
        $this->_exportExtra("prices", $this->priceExporter);
        $this->_exportExtra("reviews", $this->reviewsExporter);
        $this->_exportExtra("productStreams", $this->streamExporter);

        if ($this->config->exportProductImages($this->getAccount()))
        {
            $this->_exportExtra("media", $this->imagesExporter);
        }

        if ($this->config->exportProductUrl($this->getAccount()))
        {
            $this->_exportExtra("urls", $this->urlExporter);
        }

        return $this;
    }


    /**
     * Contains the logic for exporting individual items describing the product component
     * (categories, translations, prices, reviews, etc..)
     * @param $step
     * @param $exporter
     */
    protected function _exportExtra($step, $exporter)
    {
        $this->logger->info("BxIndexLog: Preparing products - {$step}.");
        $exporter->setAccount($this->getAccount())->setFiles($this->getFiles())->setExportedProductIds($this->exportedProductIds);
        $exporter->export();
        $this->logger->info("BxIndexLog: {$step} exporter after memory: " . memory_get_usage(true));
        $this->logger->info("BxIndexLog: Finished products - {$step}.");
    }

    /**
     * @param array $properties
     * @return Product
     * @throws \Exception
     */
    public function defineOtherProperties(array $properties) : Product
    {
        $mainSourceKey = $this->getLibrary()->addMainCSVItemFile($this->getFiles()->getPath($this->getComponentMainFile()), $this->getComponentIdField());
        $this->getLibrary()->addSourceStringField($mainSourceKey, 'bx_purchasable', 'purchasable');
        $this->getLibrary()->addSourceStringField($mainSourceKey, 'immediate_delivery', 'immediate_delivery');
        $this->getLibrary()->addSourceStringField($mainSourceKey, 'bx_type', $this->getComponentIdField());
        $this->getLibrary()->addFieldParameter($mainSourceKey, 'bx_type', 'pc_fields', '"product"  AS final_value');
        $this->getLibrary()->addFieldParameter($mainSourceKey, 'bx_type', 'multiValued', 'false');

        foreach ($properties as $property)
        {
            if ($property == $this->getComponentIdField()) {
                continue;
            }

            if ($property == 'sales') {
                $this->getLibrary()->addSourceNumberField($mainSourceKey, $property, $property);
                $this->getLibrary()->addFieldParameter($mainSourceKey, $property, 'multiValued', 'false');
                continue;
            }

            $this->getLibrary()->addSourceStringField($mainSourceKey, $property, $property);
            if ($property == 'parent_id' || $property == 'release_date' || $property == 'created_at' || $property == 'updated_at' || $property == 'product_number') {
                $this->getLibrary()->addFieldParameter($mainSourceKey, $property, 'multiValued', 'false');
            }
        }

        return $this;
    }

    /**
     * @param $query
     * @return \Generator
     */
    public function processExport(QueryBuilder $query)
    {
        foreach($query->execute()->fetchAll() as $row)
        {
            yield $row;
        }
    }

    /**
     * @return string
     */
    public function getProductChannelMainFile() : string
    {
        return self::BOXALINO_EXPORT_PRODUCT_SHOP_CSV;
    }

    /**
     * Getting a list of product attributes and the table it comes from
     * To be used in the general SQL select
     *
     * @return array
     * @throws \Exception
     */
    public function getFields() : array
    {
        return $this->config->getAccountProductsProperties($this->getAccount(), $this->getRequiredProperties(), []);
    }

    /**
     * @return array
     */
    public function getRequiredProperties(): array
    {
        return [
            'LOWER(HEX(p.id)) AS id', 'p.auto_increment', 'p.product_number', 'p.active', 'LOWER(HEX(p.parent_id)) AS parent_id',
            'LOWER(HEX(p.tax_id)) AS tax_id', 'LOWER(HEX(p.product_manufacturer_id)) AS product_manufacturer_id',
            'LOWER(HEX(p.delivery_time_id)) AS delivery_time_id', 'LOWER(HEX(p.product_media_id)) AS product_media_id',
            'LOWER(HEX(p.cover)) AS cover', 'LOWER(HEX(p.unit_id)) AS unit_id', 'p.category_tree', 'p.option_ids',
            'p.property_ids', 'p.price AS price_json', 'p.manufacturer_number', 'p.ean',
            'p.stock', 'p.available_stock', 'p.available', 'p.restock_time', 'p.is_closeout', 'p.purchase_steps',
            'p.max_purchase', 'p.min_purchase', 'p.purchase_unit', 'p.reference_unit', 'p.shipping_free', 'p.purchase_price',
            'p.mark_as_topseller', 'p.weight', 'p.height', 'p.length', 'p.release_date', 'p.whitelist_ids', 'p.blacklist_ids',
            'p.tag_ids', 'p.variant_restrictions', 'p.configurator_group_config', 'p.created_at', 'p.updated_at',
            'p.rating_average', 'p.display_group', 'p.child_count',
            'JSON_EXTRACT(p.price->>\'$.*.gross\', \'$[0]\') AS price_gross', 'currency.iso_code AS currency', 'currency.factor AS currency_factor',
            'tax.tax_rate', 'delivery_time_translation.name AS delivery_time_name',
            'unit_translation.name AS unit_name', 'unit_translation.short_code AS unit_short_code'
        ];
    }

    /**
     * @return string
     */
    protected function getLastExport()
    {
        if (empty($this->lastExport))
        {
            $this->lastExport = date("Y-m-d H:i:s", strtotime("-1 day"));
            $query = $this->connection->createQueryBuilder();
            $query->select(['export_date'])
                ->from('boxalino_export')
                ->andWhere('account = :account')
                ->andWhere('status = :status')
                ->orderBy('created_at', 'DESC')
                ->setMaxResults(1)
                ->setParameter('account', $this->getAccount(), ParameterType::STRING)
                ->setParameter('status', ExporterScheduler::BOXALINO_EXPORTER_STATUS_SUCCESS);
            $latestExport = $query->execute();
            if($latestExport['export_date'])
            {
                $this->lastExport = $latestExport['export_date'];
            }
        }

        return $this->lastExport;
    }

    /**
     * @param bool $value
     * @return Product
     */
    public function setSuccessOnComponentExport(bool $value) : Product
    {
        $this->successOnComponentExport = $value;
        return $this;
    }

    /**
     * @return bool
     */
    public function getSuccessOnComponentExport()
    {
        return $this->successOnComponentExport;
    }

    /**
     * Product purchasable logic depending on the default filter
     *
     * @param $row
     * @return int
     */
    public function getProductPurchasableValue($row)
    {
        if($row['is_closeout'] == 1 && $row['stock'] == 0)
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
        if($row['available_stock'] >= $row['min_purchase'])
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
        return $row['id'];
    }

}
