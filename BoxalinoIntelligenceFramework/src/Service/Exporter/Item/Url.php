<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Item;

use Boxalino\IntelligenceFramework\Service\Exporter\Component\Product;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class Url
 * @TODO add storefront URL based on host/shop domain
 * @package Boxalino\IntelligenceFramework\Service\Exporter\Item
 */
class Url extends ItemsAbstract
{
    CONST EXPORTER_COMPONENT_ITEM_NAME = "seo_url";
    CONST EXPORTER_COMPONENT_ITEM_MAIN_FILE = 'product_seo_url.csv';


    public function export()
    {
        $this->logger->info("BxIndexLog: Preparing products - START URL EXPORT.");
        $totalCount = 0; $page = 1; $data=[]; $header = true; $success = true;
        while (Product::EXPORTER_LIMIT > $totalCount + Product::EXPORTER_STEP) {
            $query = $this->connection->createQueryBuilder();
            $query->select($this->getRequiredFields())
                ->from('product')
                ->leftJoin('product', '( ' . $this->getLocalizedFieldsQuery()->__toString() . ') ',
                    'seo_url', 'seo_url.foreign_key = product.id')
                ->andWhere('product.version_id = :live')
                ->andWhere('seo_url.sales_channel_id = :channel')
                ->addGroupBy('product.id')
                ->setParameter("channel", Uuid::fromHexToBytes($this->getChannelId()))
                ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION))
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

            foreach (array_chunk($data, Product::EXPORTER_DATA_SAVE_STEP) as $dataSegment) {
                $this->getFiles()->savePartToCsv($this->getItemMainFile(), $dataSegment);
                $dataSegment = [];
            }

            $data = [];
            $page++;
            if ($totalCount < Product::EXPORTER_STEP - 1) {
                break;
            }
        }

        if($success)
        {
            $sourceKey = $this->getLibrary()->addCSVItemFile($this->getFiles()->getPath($this->getItemMainFile()), 'product_id');
            $this->getLibrary()->addSourceLocalizedTextField($sourceKey, 'seo_url', $this->getLanguageHeaders());
        }
    }

    /**
     * Prepare seo url joins
     * @return \Doctrine\DBAL\Query\QueryBuilder
     * @throws \Exception
     */
    protected function getLocalizedFieldsQuery() : QueryBuilder
    {
        $languages = $this->config->getAccountLanguages($this->getAccount());
        $defaultLanguage = $this->config->getChannelDefaultLanguageId($this->getAccount());
        $alias = []; $innerConditions = []; $leftConditions = []; $mainTable = 'seo_url';
        $groupByFields = ['seo_url.foreign_key', 'seo_url.sales_channel_id'];
        $selectFields = ['seo_url.sales_channel_id', 'seo_url.foreign_key'];
        foreach($languages as $languageId=>$languageCode)
        {
            $alias[$languageCode] = "seo_url_" . $languageCode;
            $selectFields[] = "IF(IS_NULL($alias.seo_path_info), $mainTable.seo_path_info, $alias.seo_path_info) as value_$languageCode";
            $innerConditions[$languageCode] = [
                "$mainTable.id = $alias.id",
                "$mainTable.foreign_key = $alias.foreign_key",
                "$mainTable.language_id = $defaultLanguage"
            ];

            $leftConditions[$languageCode] = [
                "$mainTable.id = $alias.id",
                "$mainTable.foreign_key = $alias.foreign_key",
                "$mainTable.language_id = $languageId"
            ];
        }

        $query = $this->connection->createQueryBuilder();
        $query->select($selectFields)
            ->from($mainTable);

        foreach($languages as $languageId=>$languageCode)
        {
            $query->innerJoin($mainTable, $mainTable, $alias[$languageCode], implode(" AND ", $innerConditions[$languageCode]))
                ->leftJoin($mainTable, $mainTable, $alias[$languageCode], implode(" AND ", $leftConditions[$languageCode]));
        }

        $query->groupBy($groupByFields);
        return $query;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getRequiredFields(): array
    {
        $translationFields = preg_filter('/^/', 'seo_url.', $this->getLanguageHeaders());
        return array_merge($translationFields, ['LOWER(HEX(product.id)) AS product_id']
        );
    }
}
