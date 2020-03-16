<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Item;

use Boxalino\IntelligenceFramework\Service\Exporter\Component\Product;
use Doctrine\DBAL\ParameterType;
use Shopware\Core\Defaults;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Framework\Uuid\Uuid;

class Manufacturer extends ItemsAbstract
{

    CONST EXPORTER_COMPONENT_ITEM_NAME = "brand";
    CONST EXPORTER_COMPONENT_ITEM_MAIN_FILE = 'brand.csv';
    CONST EXPORTER_COMPONENT_ITEM_RELATION_FILE = 'product_brand.csv';

    public function export()
    {
        $this->logger->info("BxIndexLog: Preparing products - START MANUFACTURER EXPORT.");
        $fields = array_merge($this->getLanguageHeaders(), ['manufacturer.product_manufacturer_id']);

        $query = $this->connection->createQueryBuilder();
        $query->select($fields)
            ->from('( '. $this->getLocalizedFieldsQuery()->__toString().')', 'manufacturer')
            ->andWhere('manufacturer.product_manufacturer_version_id = :live')
            ->addGroupBy('manufacturer.product_manufacturer_id')
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY);

        $count = $query->execute()->rowCount();
        if ($count == 0) {
            return;
        }
        $data = $query->execute()->fetchAll();
        if (count($data) > 0) {
            $data = array_merge(array(array_keys(end($data))), $data);
        }
        $this->getFiles()->savePartToCsv($this->getItemMainFile(), $dataSegment);

        $this->exportItemRelation();
        $this->logger->info("BxIndexLog: Preparing products - END MANUFACTURER.");
    }

    public function exportItemRelation()
    {
        $this->logger->info("BxIndexLog: Preparing products - START MANUFACTURER RELATIONS EXPORT.");
        $totalCount = 0; $page = 1; $header = true; $success = true;
        $rootCategoryId = $this->config->getChannelRootCategoryId($this->getAccount());

        while (Product::EXPORTER_LIMIT > $totalCount + Product::EXPORTER_STEP)
        {
            $query = $this->connection->createQueryBuilder();
            $query->select($this->getRequiredFields())
                ->from('product', 'p')
                ->andWhere('p.version_id = :live')
                ->andWhere('p.product_manufacturer_version_id = :live')
                ->andWhere("JSON_SEARCH(p.category_tree, 'one', :channelRootCategoryId) IS NOT NULL")
                ->addGroupBy('p.product_id')
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
            $optionSourceKey = $this->getLibrary()->addResourceFile($this->getFiles()->getPath($this->getItemMainFile()),
                'product_manufacturer_id', $this->getLanguageHeaders());
            $attributeSourceKey = $this->getLibrary()->addCSVItemFile($this->getFiles()->getPath($this->getItemRelationFile()), 'product_id');
            $this->getLibrary()->addSourceLocalizedTextField($attributeSourceKey, $this->getPropertyName(),'product_manufacturer_id', $optionSourceKey);
        }
    }

    /**
     * @return QueryBuilder
     * @throws \Shopware\Core\Framework\Uuid\Exception\InvalidUuidException
     */
    public function getLocalizedFieldsQuery()
    {
        return $this->getLocalizedFields("product_manufacturer_translation", 'product_manufacturer_id',
            'product_manufacturer_id', 'product_manufacturer_version_id', 'name',
            ['product_manufacturer_translation.product_manufacturer_id', 'product_manufacturer_translation.product_manufacturer_version_id']
        );
    }

    /**
     * @return array
     */
    public function getRequiredFields(): array
    {
        return ['p.id AS product_id', 'p.product_manufacturer_id'];
    }
}
