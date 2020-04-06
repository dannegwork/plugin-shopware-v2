<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Item;

use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Checkout\Cart\Rule\CartAmountRule;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
#use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\Profiling\Checkout\SalesChannelContextServiceProfiler;
use Boxalino\IntelligenceFramework\Service\Exporter\Component\Product;
use Boxalino\IntelligenceFramework\Service\Exporter\Util\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Defaults;

/**
 * Class Price
 * @TODO Shopware\Core\System\SalesChannel\Context\SalesChannelContextService must be used for the context service; not the decorator
 *
 * @package Boxalino\IntelligenceFramework\Service\Exporter\Item
 */
class Price extends ItemsAbstract
{

    CONST EXPORTER_COMPONENT_ITEM_NAME = "price";
    CONST EXPORTER_COMPONENT_ITEM_MAIN_FILE = 'prices.csv';
    CONST EXPORTER_COMPONENT_ITEM_RELATION_FILE = 'product_price.csv';

    /**
     * @var SalesChannelContextServiceProfiler
     */
    protected $salesChannelContextService;

    /**
     * @var CartAmountRule
     */
    protected $cartAmmountRule;

    public function __construct(
        Connection $connection,
        LoggerInterface $boxalinoLogger,
        Configuration $exporterConfigurator,
        CartAmountRule $cartAmountRule,
        SalesChannelContextServiceProfiler $salesChannelContextService
    ){
        $this->cartAmmountRule = $cartAmountRule;
        $this->salesChannelContextService = $salesChannelContextService;
        parent::__construct($connection, $boxalinoLogger, $exporterConfigurator);
    }

    public function export()
    {
        $this->logger->info("BxIndexLog: Preparing products - START PRICE EXPORT.");
        $this->exportItemRelation();
        $this->logger->info("BxIndexLog: Preparing products - END PRICE.");
    }

    public function getItemRelationHeaderColumns(array $additionalFields = []): array
    {
        return [["product_id", "price"]];
    }

    public function setFilesDefinitions()
    {
        $attributeSourceKey = $this->getLibrary()->addCSVItemFile($this->getFiles()->getPath($this->getItemRelationFile()), 'product_id');
        $this->getLibrary()->addSourceDiscountedPriceField($attributeSourceKey, 'price');
        $this->getLibrary()->addSourceListPriceField($attributeSourceKey, 'price');
        $this->getLibrary()->addSourceNumberField($attributeSourceKey, 'bx_grouped_price', 'price');
        $this->getLibrary()->addFieldParameter($attributeSourceKey, 'bx_grouped_price', 'multiValued', 'false');
    }

    public function getItemRelationQuery(int $page = 1): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select($this->getRequiredFields())
            ->from("product_price")
            ->leftJoin("product_price", 'rule_condition', 'rule_condition',
                'product_price.rule_id = rule_condition.rule_id AND rule_condition.type = :priceRuleType')
            ->andWhere('product_price.quantity_start = 1')
            ->andWhere('product_price.product_version_id = :live')
            ->andWhere('product_price.version_id = :live')
            ->addGroupBy('product_price.product_id')
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
            ->setParameter('priceRuleType', $this->cartAmmountRule->getName(), ParameterType::STRING)
            ->setFirstResult(($page - 1) * Product::EXPORTER_STEP)
            ->setMaxResults(Product::EXPORTER_STEP);

        return $query;
    }


    /**
     * Depending on the channel configuration, the gross or net price is the one displayed to the user
     * @duplicate logic from the src/Core/Content/Product/SalesChannel/Price/ProductPriceDefinitionBuilder.php :: getPriceForTaxState()
     *
     * @return array
     * @throws \Exception
     */
    public function getRequiredFields(): array
    {
        $salesChannelContext = $this->salesChannelContextService->get(
            $this->getChannelId(),
            "boxalinoexporttoken",
            $this->getChannelDefaultLanguage()
        );

        if ($salesChannelContext->getTaxState() === CartPrice::TAX_STATE_GROSS) {
            $this->logger->info("BxIndexLog: PRICE EXPORT TYPE: " . CartPrice::TAX_STATE_GROSS);
            return [
                'FORMAT(JSON_EXTRACT(product_price.price->>\'$.*.gross\', \'$[0]\'), 2) AS price',
                'LOWER(HEX(product_price.product_id)) AS product_id'
            ];
        }

        $this->logger->info("BxIndexLog: PRICE EXPORT TYPE: " . CartPrice::TAX_STATE_NET);
        return [
            'FORMAT(JSON_EXTRACT(product_price.price->>\'$.*.net\', \'$[0]\'), 2) AS price',
            'LOWER(HEX(product_price.product_id)) AS product_id'
        ];
    }

}
