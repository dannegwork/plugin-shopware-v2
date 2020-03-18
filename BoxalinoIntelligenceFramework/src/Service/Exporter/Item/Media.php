<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Item;

use Boxalino\IntelligenceFramework\Service\Exporter\Component\Product;
use Boxalino\IntelligenceFramework\Service\Exporter\Util\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Defaults;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class Media
 * @package Boxalino\IntelligenceFramework\Service\Exporter\Item
 */
class Media extends ItemsAbstract
{
    CONST EXPORTER_COMPONENT_ITEM_NAME = "image";
    CONST EXPORTER_COMPONENT_ITEM_MAIN_FILE = 'media.csv';
    CONST EXPORTER_COMPONENT_ITEM_RELATION_FILE = 'product_media.csv';

    /**
     * @var UrlGeneratorInterface
     */
    protected $mediaUrlGenerator;

    public function __construct(
        Connection $connection,
        LoggerInterface $logger,
        Configuration $exporterConfigurator,
        UrlGeneratorInterface $generator
    ){
        $this->mediaUrlGenerator = $generator;
        parent::__construct($connection, $logger, $exporterConfigurator);
    }

    public function export()
    {
        $this->logger->info("BxIndexLog: Preparing products - START MEDIA EXPORT.");
        $totalCount = 0; $page = 1; $header = true; $success = true;
        while (Product::EXPORTER_LIMIT > $totalCount + Product::EXPORTER_STEP)
        {
            $query = $this->connection->createQueryBuilder();
            $query->select(["GROUP_CONCAT(LOWER(HEX(product_media.id)) ORDER BY product_media.position SEPARATOR '|') AS value", "product_media.product_id"])
                ->from("product_media")
                ->andWhere('product_media.product_version_id = :live')
                ->andWhere('product_media.version_id = :live')
                ->addGroupBy('product_media.product_id')
                ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY);

            $count = $query->execute()->rowCount();
            $totalCount += $count;
            if ($totalCount == 0) {
                if($page==1) {
                    $success = false;
                }
                break;
            }
            $results = $this->processExport($query);
            foreach($results as $row)
            {
                if($header) {
                    $data[] = array_keys($row);
                    $header = false;
                }
                $images = explode('|', $row['value']);
                foreach ($images as $index => $image)
                {
                    $media = new MediaEntity();
                    $media->setId($image);
                    $images[$index] = $this->mediaUrlGenerator->getAbsoluteMediaUrl($media);
                }
                $row['value'] = implode('|', $images);
                $data[] = $row;
                if(count($data) > Product::EXPORTER_DATA_SAVE_STEP)
                {
                    $this->getFiles()->savePartToCsv($this->getItemRelationFile(), $data);
                    $data = [];
                }
            }

            $this->getFiles()->savePartToCsv($this->getItemRelationFile(), $data);
            $data = []; $page++;
            if($totalCount < Product::EXPORTER_STEP - 1) { break;}
        }

        if($success)
        {
            $attributeSourceKey = $this->getLibrary()->addCSVItemFile($this->getFiles()->getPath($this->getItemRelationFile()), 'product_id');
            $this->getLibrary()->addSourceStringField($attributeSourceKey, $this->getPropertyName(), 'value');
            $this->getLibrary()->addFieldParameter($attributeSourceKey,$this->getPropertyName(), 'splitValues', '|');
        }

        $this->logger->info("BxIndexLog: Preparing products - END MEDIA.");
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
     * @return array
     */
    public function getRequiredFields(): array
    {
        return ['product_media.product_id', 'media.value'];
    }
}
