<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Item;

use Boxalino\IntelligenceFramework\Service\Exporter\Component\Product;
use Doctrine\DBAL\ParameterType;
use Shopware\Core\Defaults;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Framework\Uuid\Uuid;

class Visibility extends ItemsAbstract
{

    CONST EXPORTER_COMPONENT_ITEM_NAME = "visibility";
    CONST EXPORTER_COMPONENT_ITEM_MAIN_FILE = 'visibility.csv';
    CONST EXPORTER_COMPONENT_ITEM_RELATION_FILE = 'product_visibility.csv';

    public function export()
    {
        $this->logger->info("BxIndexLog: Preparing products - START VISIBILITY EXPORT.");
        $this->exportItemRelation();
        $this->logger->info("BxIndexLog: Preparing products - END VISIBILITY.");
    }

    public function getItemRelationQuery(int $page = 1): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select($this->getRequiredFields())
            ->from("product_visibility")
            ->andWhere('product_visibility.product_version_id = :live')
            ->andWhere('product_visibility.sales_channel_id = :channel')
            ->addGroupBy('product_visibility.product_id')
            ->setParameter("channel", Uuid::fromHexToBytes($this->getChannelId()), ParameterType::BINARY)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
            ->setFirstResult(($page - 1) * Product::EXPORTER_STEP)
            ->setMaxResults(Product::EXPORTER_STEP);

        return $query;
    }

    public function setFilesDefinitions()
    {
        $attributeSourceKey = $this->getLibrary()->addCSVItemFile($this->getFiles()->getPath($this->getItemRelationFile()), 'product_id');
        $this->getLibrary()->addSourceNumberField($attributeSourceKey, $this->getPropertyName(), $this->getPropertyIdField());
    }

    public function getRequiredFields(): array
    {
        return ["visibility as {$this->getPropertyIdField()}", 'LOWER(HEX(product_id)) AS product_id'];
    }

}
