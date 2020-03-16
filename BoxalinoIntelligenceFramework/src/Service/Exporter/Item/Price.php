<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Item;

use Shopware\Core\Checkout\Cart\Rule\CartAmountRule;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService
use Boxalino\IntelligenceFramework\Service\Exporter\Component\Product;
use Boxalino\IntelligenceFramework\Service\Exporter\Util\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Defaults;

/**
 * Class Price
 * @package Boxalino\IntelligenceFramework\Service\Exporter\Item
 */
class Price extends ItemsAbstract
{

    CONST EXPORTER_COMPONENT_ITEM_NAME = "price";
    CONST EXPORTER_COMPONENT_ITEM_MAIN_FILE = 'prices.csv';
    CONST EXPORTER_COMPONENT_ITEM_RELATION_FILE = 'product_price.csv';

    /**
     * @var SalesChannelContextService
     */
    protected $salesChannelContextService;

    /**
     * @var CartAmountRule
     */
    protected $cartAmmountRule;

    public function __construct(
        CartAmountRule $cartAmountRule,
        SalesChannelContextService $salesChannelContextService,
        Connection $connection,
        LoggerInterface $logger,
        Configuration $exporterConfigurator
    ){
        $this->cartAmmountRule = $cartAmountRule;
        $this->salesChannelContextService = $salesChannelContextService;
        parent::__construct($connection, $logger, $exporterConfigurator);
    }

    public function export()
    {
        $this->logger->info("BxIndexLog: Preparing products - START PRICE EXPORT.");
        $totalCount = 0; $page = 1; $header = true; $success = true;
        while (Product::EXPORTER_LIMIT > $totalCount + Product::EXPORTER_STEP)
        {
            $query = $this->connection->createQueryBuilder();
            $query->select($this->getRequiredFields())
                ->from("product_price")
                ->leftJoin("product_price", 'rule_condition', 'rule_condition', 'product_price.rule_id = rule_condition.rule_id AND rule_condition.type = :priceRuleType')
                ->andWhere('product_price.quantity_start = 1')
                ->andWhere('product_price.product_version_id = :live')
                ->andWhere('product_price.version_id = :live')
                ->addGroupBy('product_price.product_id')
                ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
                ->setParameter('priceRuleType', $this->cartAmmountRule->getName(), ParameterType::STRING)
                ->setFirstResult(($page - 1) * Product::EXPORTER_STEP)
                ->setMaxResults(Product::EXPORTER_STEP);

            $count = $query->execute()->rowCount();
            $totalCount += $count;
            if ($totalCount == 0) {
                if($page==1){
                    $success = false;
                }
                break;
            }
            $data = $query->execute()->fetchAll();
            if (count($data) > 0 && $header) {
                $header = false;
                $data = array_merge(array(array_keys(end($data))), $data);
            }
            foreach(array_chunk($data, Product::EXPORTER_DATA_SAVE_STEP) as $dataSegment)
            {
                $this->getFiles()->savePartToCsv($this->getItemRelationFile(), $dataSegment);
            }

            $data = []; $page++;
            if($totalCount < Product::EXPORTER_STEP - 1) { break;}
        }

        if($success)
        {
            $attributeSourceKey = $this->getLibrary()->addCSVItemFile($this->getFiles()->getPath($this->getItemRelationFile()), 'product_id');
            $this->getLibrary()->addSourceDiscountedPriceField($attributeSourceKey, 'price');
            $this->getLibrary()->addSourceListPriceField($attributeSourceKey, 'price');
            $this->getLibrary()->addSourceNumberField($attributeSourceKey, 'bx_grouped_price', 'price');
            $this->getLibrary()->addFieldParameter($attributeSourceKey, 'bx_grouped_price', 'multiValued', 'false');
        }

        $this->logger->info("BxIndexLog: Preparing products - END PRICE.");
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
        $salesChannelContext = $this->salesChannelContextService->get($this->getChannelId(), "boxalinoexporttoken",
            $this->config->getChannelDefaultLanguageId($this->getAccount()));
        if ($salesChannelContext->getTaxState() === CartPrice::TAX_STATE_GROSS) {
            return [
                'FORMAT(JSON_EXTRACT(product_price.price->>\'$.*.gross\', \'$[0]\'), 2) AS price',
                'product_price.product_id'
            ];
        }

        return [
            'FORMAT(JSON_EXTRACT(product_price.price->>\'$.*.net\', \'$[0]\'), 2) AS price',
            'product_price.product_id'
        ];
    }

}
