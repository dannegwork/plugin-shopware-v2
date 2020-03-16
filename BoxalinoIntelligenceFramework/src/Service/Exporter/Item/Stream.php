<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Item;

use Boxalino\IntelligenceFramework\Service\Exporter\Component\Product;
use Doctrine\DBAL\ParameterType;

/**
 * Class Stream
 * aka "Dynamic Product Groups"
 *
 * @package Boxalino\IntelligenceFramework\Service\Exporter\Item
 */
class Stream extends ItemsAbstract
{
    CONST EXPORTER_COMPONENT_ITEM_NAME = "stream";
    CONST EXPORTER_COMPONENT_ITEM_MAIN_FILE = 'stream.csv';
    CONST EXPORTER_COMPONENT_ITEM_RELATION_FILE = 'product_stream.csv';

    public function export()
    {
        $this->logger->info("BxIndexLog: Preparing products - START STREAM EXPORT.");
        $this->logger->info("BxIndexLog: Preparing products - END STREAM.");
    }

    public function getRequiredFields(): array
    {
        return [];
    }
}
