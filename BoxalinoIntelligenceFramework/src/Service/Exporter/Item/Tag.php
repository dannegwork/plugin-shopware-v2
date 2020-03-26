<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Item;

use Boxalino\IntelligenceFramework\Service\Exporter\Component\Product;
use Doctrine\DBAL\ParameterType;
use Shopware\Core\Defaults;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class Tag
 * @package Boxalino\IntelligenceFramework\Service\Exporter\Item
 */
class Tag extends ItemsAbstract
{
    CONST EXPORTER_COMPONENT_ITEM_NAME = "tag";
    CONST EXPORTER_COMPONENT_ITEM_MAIN_FILE = 'tag.csv';
    CONST EXPORTER_COMPONENT_ITEM_RELATION_FILE = 'product_tag.csv';

    public function export()
    {
        $this->logger->info("BxIndexLog: Preparing products - START TAG EXPORT.");
        $query = $this->connection->createQueryBuilder();
        $query->select(["LOWER(HEX(id)) AS tag_id", "name AS value"])
            ->from("tag");

        $data = $query->execute()->fetchAll();
        if (count($data) > 0)
        {
            $data = array_merge(array(array_keys(end($data))), $data);
            $this->getFiles()->savePartToCsv($this->getItemMainFile(), $data);
            $this->exportItemRelation();
        }

        $this->logger->info("BxIndexLog: Preparing products - END TAG.");
    }

    public function exportItemRelation()
    {
        $this->logger->info("BxIndexLog: Preparing products - START TAG RELATIONS EXPORT.");
        $totalCount = 0; $page = 1; $header = true; $success = true;
        while (Product::EXPORTER_LIMIT > $totalCount + Product::EXPORTER_STEP)
        {
            $query = $this->connection->createQueryBuilder();
            $query->select($this->getRequiredFields())
                ->from('product_tag')
                ->leftJoin('product_tag', 'product', 'product', 'product_tag.product_id = product.id AND product_tag.product_version_id=product.version_id')
                ->andWhere('product.version_id = :live')
                ->andWhere("JSON_SEARCH(product.category_tree, 'one', :channelRootCategoryId) IS NOT NULL")
                ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
                ->setParameter('channelRootCategoryId', $this->getRootCategoryId(), ParameterType::STRING)
                ->setFirstResult(($page - 1) * Product::EXPORTER_STEP)
                ->setMaxResults(Product::EXPORTER_STEP);

            $count = $query->execute()->rowCount();
            $totalCount += $count;
            if ($totalCount == 0) {
                if($page==1) {
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
            $optionSourceKey = $this->getLibrary()->addResourceFile($this->getFiles()->getPath($this->getItemMainFile()),'tag_id', ['value']);
            $attributeSourceKey = $this->getLibrary()->addCSVItemFile($this->getFiles()->getPath($this->getItemRelationFile()), 'product_id');
            $this->getLibrary()->addSourceStringField($attributeSourceKey, $this->getPropertyName(), 'tag_id', $optionSourceKey);
        }
    }

    public function getRequiredFields(): array
    {
        return ['LOWER(HEX(product_tag.product_id)) AS product_id', 'LOWER(HEX(product_tag.tag_id)) AS tag_id'];
    }
}
