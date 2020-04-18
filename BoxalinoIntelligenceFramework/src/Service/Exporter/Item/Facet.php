<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Item;

use Boxalino\IntelligenceFramework\Service\Exporter\Component\Product;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class Facet
 * check src/Core/Content/Property/PropertyGroupDefinition.php for other property types and definitions
 * @package Boxalino\IntelligenceFramework\Service\Exporter\Item
 */
class Facet extends ItemsAbstract
{
    /**
     * @var string
     */
    protected $property;

    /**
     * @var string
     */
    protected $propertyId;


    public function export()
    {
        $this->logger->info("BxIndexLog: Preparing products - START FACETS EXPORT.");
        $properties = $this->getPropertyNames();
        foreach($properties as $property)
        {
            $this->property = $property['name']; $this->propertyId = $property['property_group_id'];
            $this->logger->info("BxIndexLog: Preparing products - START $this->property EXPORT.");
            $query = $this->getLocalizedPropertyQuery();
            $count = $query->execute()->rowCount();
            if ($count == 0) {
                $this->logger->info("BxIndexLog: PRODUCTS EXPORT: No options found for $this->property.");
                $headers = $this->getMainHeaderColumns();
                $this->getFiles()->savePartToCsv($this->getItemMainFile(), $headers);
            } else {
                $data = $query->execute()->fetchAll();
                $data = array_merge(array(array_keys(end($data))), $data);
                $this->getFiles()->savePartToCsv($this->getItemMainFile(), $data);
            }

            $this->exportItemRelation();
            $this->logger->info("BxIndexLog: Preparing products - END $this->property.");
        }

        $this->logger->info("BxIndexLog: Preparing products - END FACES.");
    }

    /**
     * @throws \Exception
     */
    public function setFilesDefinitions()
    {
        $optionSourceKey = $this->getLibrary()->addResourceFile($this->getFiles()->getPath($this->getItemMainFile()),
            $this->getPropertyIdField(), $this->getLanguageHeaders());
        $attributeSourceKey = $this->getLibrary()->addCSVItemFile($this->getFiles()->getPath($this->getItemRelationFile()), 'product_id');
        $this->getLibrary()->addSourceLocalizedTextField($attributeSourceKey, $this->getPropertyName(), $this->getPropertyIdField(), $optionSourceKey);
    }

    /**
     * @param int $page
     * @return QueryBuilder
     * @throws \Shopware\Core\Framework\Uuid\Exception\InvalidUuidException
     */
    public function getItemRelationQuery(int $page = 1): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select([
            "LOWER(HEX(product_option.product_id)) AS product_id",
            "LOWER(HEX(product_option.property_group_option_id)) AS {$this->getPropertyIdField()}"])
            ->from("product_option")
            ->leftJoin("product_option", "property_group_option", "property_group_option",
                "product_option.property_group_option_id = property_group_option.id")
            ->where("property_group_option.property_group_id = :propertyId")
            ->setParameter("propertyId", Uuid::fromHexToBytes($this->propertyId), ParameterType::STRING)
            ->setFirstResult(($page - 1) * Product::EXPORTER_STEP)
            ->setMaxResults(Product::EXPORTER_STEP);

        return $query;
    }

    /**
     * Accessing store-view level translation for each facet option
     *
     * @return QueryBuilder
     * @throws \Shopware\Core\Framework\Uuid\Exception\InvalidUuidException
     */
    protected function getLocalizedPropertyQuery() : QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select($this->getRequiredFields())
            ->from("property_group_option")
            ->leftJoin('property_group_option', '( ' . $this->getLocalizedFieldsQuery()->__toString() . ') ',
                'translation', 'translation.property_group_option_id = property_group_option.id')
            ->andWhere('property_group_option.property_group_id = :propertyGroupId')
            ->andWhere($this->getLanguageHeaderConditional())
            ->addGroupBy('property_group_option.id')
            ->setParameter('propertyGroupId', Uuid::fromHexToBytes($this->propertyId), ParameterType::BINARY);

        return $query;
    }

    /**
     * @param string $property
     * @return \Doctrine\DBAL\Query\QueryBuilder
     * @throws \Exception
     */
    protected function getLocalizedFieldsQuery() : QueryBuilder
    {
        return $this->getLocalizedFields('property_group_option_translation',
            'property_group_option_id', 'property_group_option_id',
            'property_group_option_id', "name",
            ['property_group_option_translation.property_group_option_id']
        );
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
     * All translation fields from the product_group_option* table
     *
     * @return array
     * @throws \Exception
     */
    public function getRequiredFields(): array
    {
        return array_merge($this->getLanguageHeaderColumns(),
            ["LOWER(HEX(property_group_option.id)) AS {$this->getPropertyIdField()}"]
        );
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getMainHeaderColumns() : array
    {
        return [array_merge($this->getLanguageHeaders(), [$this->getPropertyIdField()])];
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
     */
    public function getPropertyName() : string
    {
        return $this->property;
    }

    /**
     * @return string
     */
    public function getItemMainFile() : string
    {
        return "$this->property.csv";
    }

    /**
     * @return string
     */
    public function getItemRelationFile() : string
    {
        return "products_$this->property.csv";
    }

    /**
     * Get existing facets names&codes
     *
     * @return false|mixed
     */
    public function getPropertyNames() : array
    {
        $query = $this->connection->createQueryBuilder()
            ->select(["LOWER(HEX(property_group_id)) AS property_group_id", "name"])
            ->from("property_group_translation")
            ->where("language_id = :languageId")
            ->setParameter("languageId", Uuid::fromHexToBytes($this->getChannelDefaultLanguage()), ParameterType::STRING);

        return $query->execute()->fetchAll(FetchMode::ASSOCIATIVE);
    }

}
