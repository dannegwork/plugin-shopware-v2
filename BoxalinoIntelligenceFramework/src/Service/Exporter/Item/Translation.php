<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Item;

use Boxalino\IntelligenceFramework\Service\Exporter\Component\Product;
use Doctrine\DBAL\ParameterType;
use Shopware\Core\Defaults;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class Translation
 * Exports public translation fields
 * Only export the properties that have values
 *
 * @package Boxalino\IntelligenceFramework\Service\Exporter\Item
 */
class Translation extends ItemsAbstract
{

    public function export()
    {
        $this->logger->info("BxIndexLog: Preparing products - START TRANSLATIONS EXPORT.");
        $properties = $this->getPropertyNames();
        foreach($properties as $property)
        {
            $property = $property["COLUMN_NAME"];
            $this->logger->info("BxIndexLog: Preparing products - START $property EXPORT.");
            $totalCount = 0; $page = 1; $data=[]; $header = true;
            while (Product::EXPORTER_LIMIT > $totalCount + Product::EXPORTER_STEP)
            {
                $query = $this->getLocalizedPropertyQuery($property, $page);
                $count = $query->execute()->rowCount();
                $totalCount += $count;
                if ($totalCount == 0) {
                    if($page==1) {
                        $this->logger->info("BxIndexLog: PRODUCTS EXPORT: No data found for $property.");
                        $headers = $this->getItemRelationHeaderColumns();
                        $this->getFiles()->savePartToCsv($this->getItemRelationFileNameByProperty($property), $headers);
                    }
                    break;
                }
                $data = $query->execute()->fetchAll();
                if ($header) {
                    $header = false;
                    $data = array_merge(array(array_keys(end($data))), $data);
                }
                foreach(array_chunk($data, Product::EXPORTER_DATA_SAVE_STEP) as $dataSegment)
                {
                    $this->getFiles()->savePartToCsv($this->getItemRelationFileNameByProperty($property), $dataSegment);
                }

                $data = []; $page++;
                if($totalCount < Product::EXPORTER_STEP - 1) { break;}
            }

            $this->registerFilesByProperty($property);
            $this->logger->info("BxIndexLog: Preparing products - END $property.");
        }

        $this->logger->info("BxIndexLog: Preparing products - END TRANSLATIONS.");
    }

    public function getItemRelationHeaderColumns(array $additionalFields = []): array
    {
        return [array_merge($this->getLanguageHeaders(), ['product_id'])];
    }

    /**
     * @param $property
     * @param $page
     * @return QueryBuilder
     * @throws \Shopware\Core\Framework\Uuid\Exception\InvalidUuidException
     */
    protected function getLocalizedPropertyQuery($property, $page) : QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select($this->getRequiredFields())
            ->from("product")
            ->leftJoin('product', '( ' . $this->getLocalizedFieldsQuery($property)->__toString() . ') ',
                'translation', 'translation.product_id = product.id AND product.version_id = translation.product_version_id')
            ->andWhere('product.version_id = :live')
            ->andWhere($this->getLanguageHeaderConditional())
            ->addGroupBy('product.id')
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
            ->setFirstResult(($page - 1) * Product::EXPORTER_STEP)
            ->setMaxResults(Product::EXPORTER_STEP);

        return $query;
    }

    /**
     * Different localized fields have different scopes for definition in the export XML
     *
     * @param string $property
     * @return Translation
     * @throws \Exception
     */
    public function registerFilesByProperty(string $property) : self
    {
        $labelColumns = $this->getLanguageHeaders();
        $attributeSourceKey = $this->getLibrary()->addCSVItemFile($this->getFiles()->getPath($this->getItemRelationFileNameByProperty($property)), 'product_id');
        switch($property){
            case $this->getTitleProperty():
                $this->getLibrary()->addSourceTitleField($attributeSourceKey, $labelColumns);
                break;
            case $this->getDescriptionProperty():
                $this->getLibrary()->addSourceDescriptionField($attributeSourceKey, $labelColumns);
                break;
            default:
                $this->getLibrary()->addSourceLocalizedTextField($attributeSourceKey, $property, $labelColumns);
                break;
        }

        return $this;
    }

    /**
     * @param string $property
     * @return \Doctrine\DBAL\Query\QueryBuilder
     * @throws \Exception
     */
    protected function getLocalizedFieldsQuery(string $property) : QueryBuilder
    {
        return $this->getLocalizedFields('product_translation', 'product_id', 'product_id',
            'product_version_id', $property, ['product_translation.product_id', 'product_translation.product_version_id']
        );
    }

    /**
     * All translation fields from the product_translation table
     *
     * @return array
     * @throws \Exception
     */
    public function getRequiredFields(): array
    {
        return array_merge($this->getLanguageHeaderColumns(),['LOWER(HEX(product.id)) AS product_id']);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getLanguageHeaderColumns() : array
    {
        return preg_filter('/^/', 'translation.', $this->getLanguageHeaders());
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function getLanguageHeaderConditional() : string
    {
        $conditional = [];
        foreach ($this->getLanguageHeaderColumns() as $column)
        {
            $conditional[]= "$column IS NOT NULL ";
        }

        return implode(" OR " , $conditional);
    }

    /**
     * Get translated property names
     *
     * @return false|mixed
     */
    public function getPropertyNames() : array
    {
        return $this->getTableColumnsByType();
    }

    /**
     * @return string
     */
    public function getTitleProperty() : string
    {
        return 'name';
    }

    /**
     * @return string
     */
    public function getDescriptionProperty() : string
    {
        return 'description';
    }

    /**
     * Reading from table schema the fields which are of a string data type that contain localized values/properties
     *
     * @param array $columns
     * @return array
     */
    protected function getTableColumnsByType(array $columns = ['COLUMN_NAME']) : array
    {
        $dataType = ['char','varchar','blob', 'tinyblob', 'mediumblob', 'longblob', 'enum', 'text', 'mediumtext', 'tinytext', 'longtext',  'varchar'];
        $database = $this->connection->getDatabase();
        $dataType = "'" . implode ( "', '", $dataType ) . "'";
        $query = $this->connection->createQueryBuilder();
        $query->select($columns)
            ->from('information_schema.columns')
            ->andWhere('information_schema.columns.TABLE_SCHEMA = ' . $this->connection->quote($database))
            ->andWhere("information_schema.columns.DATA_TYPE IN ($dataType)")
            ->andWhere('information_schema.columns.TABLE_NAME = "product_translation"');

        return $query->execute()->fetchAll();
    }

    public function setFilesDefinitions(){}

    public function getItemRelationQuery(int $page = 1): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        return $query;
    }

}
