<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Item;

use Boxalino\IntelligenceFramework\Service\Exporter\Component\Product;
use Boxalino\IntelligenceFramework\Service\Exporter\Util\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Media\DataAbstractionLayer\MediaRepositoryDecorator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
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

    /**
     * @var MediaRepository
     */
    protected $mediaRepository;

    /**
     * @var Context
     */
    protected $context;

    /**
     * Media constructor.
     * @param Connection $connection
     * @param LoggerInterface $logger
     * @param Configuration $exporterConfigurator
     * @param UrlGeneratorInterface $generator
     * @param MediaRepositoryDecorator $mediaRepository
     */
    public function __construct(
        Connection $connection,
        LoggerInterface $logger,
        Configuration $exporterConfigurator,
        UrlGeneratorInterface $generator,
        EntityRepositoryInterface $mediaRepository
    ){
        $this->mediaRepository = $mediaRepository;
        $this->mediaUrlGenerator = $generator;
        $this->context = Context::createDefaultContext();
        parent::__construct($connection, $logger, $exporterConfigurator);
    }

    public function export()
    {
        $this->logger->info("BxIndexLog: Preparing products - START MEDIA EXPORT.");
        $totalCount = 0; $page = 1; $header = true; $success = true;
        while (Product::EXPORTER_LIMIT > $totalCount + Product::EXPORTER_STEP)
        {
            $query = $this->connection->createQueryBuilder();
            $query->select($this->getRequiredFields())
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
                    /** @var MediaEntity $media */
                    $media = $this->mediaRepository->search(new Criteria([$image]), $this->context)->get($image);
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
            $this->getLibrary()->addFieldParameter($attributeSourceKey, $this->getPropertyName(), 'splitValues', '|');
        }

        $this->logger->info("BxIndexLog: Preparing products - END MEDIA.");
    }

    /**
     * @return array
     */
    public function getRequiredFields(): array
    {
        return ["GROUP_CONCAT(LOWER(HEX(product_media.media_id)) ORDER BY product_media.position SEPARATOR '|') AS value", "LOWER(HEX(product_media.product_id)) AS product_id"];
    }
}
