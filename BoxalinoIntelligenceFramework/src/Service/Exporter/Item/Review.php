<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Item;

use Boxalino\IntelligenceFramework\Service\Exporter\Component\Product;
use Doctrine\DBAL\ParameterType;
use Shopware\Core\Defaults;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class Review
 * Exports product votes (average)
 *
 * @package Boxalino\IntelligenceFramework\Service\Exporter\Item
 */
class Review extends ItemsAbstract
{

    CONST EXPORTER_COMPONENT_ITEM_NAME = "vote";
    CONST EXPORTER_COMPONENT_ITEM_MAIN_FILE = 'votes.csv';
    CONST EXPORTER_COMPONENT_ITEM_RELATION_FILE = 'product_votes.csv';

    public function export()
    {
        $this->logger->info("BxIndexLog: Preparing products - START REVIEWS EXPORT.");
        $totalCount = 0; $page = 1; $header = true; $success = true;
        while (Product::EXPORTER_LIMIT > $totalCount + Product::EXPORTER_STEP)
        {
            $query = $this->connection->createQueryBuilder();
            $query->select($this->getRequiredFields())
                ->from("product_review")
                ->andWhere('product_review.product_version_id = :live')
                ->andWhere('product_review.sales_channel_id = :channel')
                ->andWhere('product_review.status = 1')
                ->addGroupBy('product_review.product_id')
                ->setParameter("channel", Uuid::fromHexToBytes($this->getChannelId()), ParameterType::BINARY)
                ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
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
            $this->getLibrary()->addSourceNumberField($attributeSourceKey, $this->getPropertyName(), 'value');
        }

        $this->logger->info("BxIndexLog: Preparing products - END REVIEWS.");
    }

    /**
     * @return array
     */
    public function getRequiredFields(): array
    {
        return ['AVG(product_review.points) AS value', 'product_review.product_id AS product_id'];
    }
}
