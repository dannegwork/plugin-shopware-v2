<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Item;

use Shopware\Core\Defaults;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class Category
 * @package Boxalino\IntelligenceFramework\Service\Exporter\Item
 */
class Category extends ItemsAbstract
{

    CONST EXPORTER_COMPONENT_ITEM_MAIN_FILE = 'categories.csv';
    CONST EXPORTER_COMPONENT_ITEM_RELATION_FILE = 'product_categories.csv';

    public function export()
    {
        $this->exportMain();
        $this->exportRelation();
    }
    /**
     * Export item categories
     * REQUIRED FIELDS are: id, parent_id and translation per language
     * ADITIONAL DETAILS exported are: tags
     */
    public function exportMain()
    {
        $this->logger->info("BxIndexLog: Preparing products - START CATEGORIES EXPORT.");
        $channelRootCategory = $this->config->getChannelRootCategoryId($this->getAccount());
        $query = $this->connection->createQueryBuilder();
        $query->select($this->getRequiredFields())
            ->from("category")
            ->leftJoin('category', '( ' . $this->getTranslation()->__toString() . ') ',
                'category_translations', 'category_translations.category_id = category.id AND category.version_id = category_translations.category_version_id')
            ->leftJoin('category', '( ' . $this->getTags()->__toString() . ' )', 'category_tags',
                'category_tags.category_id = category.id AND category_tags.category_version_id = category.version_id')
            ->andWhere('category.path LIKE "|:rootCategoryId|"')
            ->andWhere('category.version_id = :live')
            ->setParameter('rootCategoryId', $channelRootCategory)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION));

        $data = $query->execute()->fetchAll();
        if (count($data) > 0)
        {
            $data = array_merge(array(array_keys(end($data))), $data);
        }
        $this->getFiles()->savePartToCsv($this->getItemMainFile(), $data);
        $this->getLibrary()->addCategoryFile($this->getFiles()->getPath($this->getItemMainFile()), 'id', 'parent_id', $this->getLanguageHeaders());
        $this->logger->info("BxIndexLog: Preparing products - END CATEGORIES.");
    }


    /**
     * Accessing category name translation (name)
     * If there is no translation available, the default one is used
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     * @throws \Shopware\Core\Framework\Uuid\Exception\InvalidUuidException
     */
    public function getTranslation() : QueryBuilder
    {
        $languages = $this->config->getAccountLanguages($this->getAccount());
        $defaultLanguage = $this->config->getChannelDefaultLanguageId($this->getAccount());
        $mainTable = 'category_translation';
        $alias = []; $innerConditions = []; $leftConditions = [];
        $selectFields = [
            "$mainTable.category_id",
            "$mainTable.category_version_id"
        ];
        foreach($languages as $languageId=>$languageCode)
        {
            $alias[$languageCode] = "category_translation_" . $languageCode;
            $selectFields[] = "IF(IS_NULL($alias.name), $mainTable.name, $alias.name) as value_$languageCode";
            $innerConditions[$languageCode] = [
                "$mainTable.category_id = $alias.category_id",
                "$mainTable.version_id = " .  Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                "$mainTable.language_id = $defaultLanguage"
            ];

            $leftConditions[$languageCode] = [
                "$mainTable.category_id = $alias.category_id",
                "$mainTable.version_id = " .  Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                "$mainTable.language_id = $languageId"
            ];
        }

        $query = $this->connection->createQueryBuilder();
        $query->select($selectFields)
            ->from('category_translation');

        foreach($languages as $languageId=>$languageCode)
        {
            $query->innerJoin($mainTable, $alias[$languageCode], $alias[$languageCode], implode(" AND ", $innerConditions[$languageCode]))
                ->leftJoin($mainTable, $alias[$languageCode], $alias[$languageCode], implode(" AND ", $leftConditions[$languageCode]));
        }

        $query->groupBy(["$mainTable.category_id", "$mainTable.category_version_id"]);
        return $query;
    }

    /**
     * Returning category tags joined via comma
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     * @throws \Shopware\Core\Framework\Uuid\Exception\InvalidUuidException
     */
    public function getTags() : QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select([
            "category_tag.category_id",
            "category_tag.category_version_id",
            "GROUP_CONCAT(tag.name SEPARATOR ',') as tags"
        ])
            ->from('category_tag')
            ->leftJoin('category_tag', 'tag', 'tag', 'category_tag.tag_id = tag.id')
            ->groupBy(["category_tag.category_id", "category_tag.category_version_id"]);

        return $query;
    }

    /**
     * @TODO implement incremental save to file for data
     * @throws \Shopware\Core\Framework\Uuid\Exception\InvalidUuidException
     */
    public function exportRelation()
    {
        $this->logger->info("BxIndexLog: Preparing products - START CATEGORIES RELATION EXPORT.");
        $channelRootCategory = $this->config->getChannelRootCategoryId($this->getAccount());
        $query = $this->connection->createQueryBuilder();
        $query->select(['category.id AS category_id', 'product_category_tree.product_id AS product_id'])
            ->from('category')
            ->rightJoin('category', 'product_category_tree', 'product_category_tree',
                'product_category_tree.category_id = category.id AND product_category_tree.category_version_id = category.version_id'
            )
            ->andWhere('category.path LIKE "|:rootCategoryId|"')
            ->andWhere('category.version_id = :live')
            ->andWhere('product_category_tree.product_version_id = :live')
            ->setParameter('rootCategoryId', $channelRootCategory)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION));

        if ($this->getExportedProductIds()) {
            $query->andWhere("product_category_tree.product_id IN (:productIds)")
                ->setParameter("productIds", implode(",", $this->getExportedProductIds()));
        }

        $data = $query->execute()->fetchAll();
        if (count($data) > 0) {
            $data = array_merge(array(array_keys(end($data))), $data);
            $this->getFiles()->savePartToCsv($this->getItemRelationFile(), $data);
            $productToCategoriesSourceKey = $this->getLibrary()->addCSVItemFile($this->getFiles()->getPath($this->getItemRelationFile()), 'product_id');
            $this->getLibrary()->setCategoryField($productToCategoriesSourceKey, 'category_id');
        }

        $this->logger->info("BxIndexLog: Preparing products - END CATEGORY RELATION EXPORT.");
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getRequiredFields(): array
    {
        $translationFields = preg_filter('/^/', 'category_translations.', $this->getLanguageHeaders());
        return array_merge($translationFields,
            [
                'LOWER(HEX(category.id)) AS id', 'category.auto_increment', 'LOWER(HEX(category.parent_id)) as parent_id',
                'LOWER(HEX(category.cms_page_id)) AS cms_page_id',
                'category.level', 'category.active', 'category.child_count', 'category.visible', 'category.type',
                'category.display_nested_products', 'category.created_at', 'category.updated_at',
                'category_tags.tags'
            ]
        );
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getLanguageHeaders() : array
    {
        return preg_filter('/^/', 'value_',$this->config->getAccountLanguages($this->getAccount()));
    }

    /**
     * @return string
     */
    public function getItemMainFile()
    {
        return self::EXPORTER_COMPONENT_ITEM_MAIN_FILE;
    }

    /**
     * @return string
     */
    public function getItemRelationFile()
    {
        return self::EXPORTER_COMPONENT_ITEM_RELATION_FILE;
    }

}
