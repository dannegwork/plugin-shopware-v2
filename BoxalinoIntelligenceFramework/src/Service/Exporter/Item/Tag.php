<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Item;

use Boxalino\IntelligenceFramework\Service\Exporter\Component\Product;
use Doctrine\DBAL\ParameterType;
use Shopware\Core\Defaults;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Framework\Uuid\Uuid;

class Tag extends ItemsAbstract
{
    CONST EXPORTER_COMPONENT_ITEM_NAME = "tag";
    CONST EXPORTER_COMPONENT_ITEM_MAIN_FILE = 'tag.csv';
    CONST EXPORTER_COMPONENT_ITEM_RELATION_FILE = 'product_tag.csv';

    public function export()
    {
        $this->logger->info("BxIndexLog: Preparing products - START TAG EXPORT.");
        $totalCount = 0; $page = 1; $header = true; $success = true;
        $query = $this->connection->createQueryBuilder();
        $query->select(["id AS tag_id", "name AS value"])
            ->from("tag");

        $count = $query->execute()->rowCount();
        $totalCount += $count;
        if ($totalCount == 0) {
            if($page==1) {
                $success = false;
            }
        }
        $data = $query->execute()->fetchAll();
        if (count($data) > 0 && $header) {
            $data = array_merge(array(array_keys(end($data))), $data);
        }
        $this->getFiles()->savePartToCsv($this->getItemRelationFile(), $data);

        if($success)
        {
            $this->exportItemRelation();
        }

        $this->logger->info("BxIndexLog: Preparing products - END TAG.");
    }

    public function exportItemRelation()
    {
        $this->logger->info("BxIndexLog: Preparing products - START TAG RELATIONS EXPORT.");
        $totalCount = 0; $page = 1; $header = true; $success = true;
        $rootCategoryId = $this->config->getChannelRootCategoryId($this->getAccount());

        while (Product::EXPORTER_LIMIT > $totalCount + Product::EXPORTER_STEP)
        {
            $query = $this->connection->createQueryBuilder();
            $query->select($this->getRequiredFields())
                ->from('product_tag')
                ->joinLeft('product_tag', 'product', 'product', 'product_tag.product_id = product.id AND product_tag.product_version_id=product.version_id')
                ->andWhere('p.version_id = :live')
                ->andWhere("JSON_SEARCH(p.category_tree, 'one', :channelRootCategoryId) IS NOT NULL")
                ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
                ->setParameter('channelRootCategoryId', $rootCategoryId, ParameterType::STRING)
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
        return ['product_tag.product_id', 'product_tag.tag_id'];
    }
}
