<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Item;

use Boxalino\IntelligenceFramework\Service\Exporter\Component\ExporterComponentAbstract;
use Boxalino\IntelligenceFramework\Service\Exporter\ExporterInterface;
use Boxalino\IntelligenceFramework\Service\Exporter\Util\Configuration;
use Boxalino\IntelligenceFramework\Service\Exporter\Util\ContentLibrary;
use Boxalino\IntelligenceFramework\Service\Exporter\Util\FileHandler;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use http\Exception\BadQueryStringException;
use Psr\Log\LoggerInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

abstract class ItemsAbstract implements ExporterInterface
{

    /**
     * @var FileHandler
     */
    protected $files;

    /**
     * @var string
     */
    protected $account;

    /**
     * @var array
     */
    protected $exportedProductIds = [];

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @var ContentLibrary
     */
    protected $library;

    public function __construct(
        Connection $connection,
        LoggerInterface $logger,
        Configuration $exporterConfigurator
    ){
        $this->connection = $connection;
        $this->logger = $logger;
        $this->config = $exporterConfigurator;
    }


    abstract public function export();
    abstract public function getRequiredFields() : array;


    /**
     * @param $property
     * @return string
     */
    public function getFileNameByProperty($property)
    {
        return "product_$property.csv";
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getRootCategoryId() : string
    {
        return $this->config->getChannelRootCategoryId($this->getAccount());
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getLanguageHeaders() : array
    {
        $languages = $this->config->getAccountLanguages($this->getAccount());
        $fields = preg_filter('/^/', 'value_', array_values($languages));

        return array_combine($languages, $fields);
    }

    /**
     * JOIN logic to access diverse localized fields/items values
     * If there is no translation available, the default one is used
     *
     * @param string $mainTable
     * @param string $mainTableIdField
     * @param string $idField
     * @param string $versionIdField
     * @param string $localizedFieldName
     * @param array $groupByFields
     * @return \Doctrine\DBAL\Query\QueryBuilder
     * @throws \Exception
     */
    public function getLocalizedFields(string $mainTable, string $mainTableIdField, string $idField,
                                       string $versionIdField, string $localizedFieldName, array $groupByFields
    ) : QueryBuilder {
        $languages = $this->config->getAccountLanguages($this->getAccount());
        $defaultLanguage = $this->config->getChannelDefaultLanguageId($this->getAccount());
        $alias = []; $innerConditions = []; $leftConditions = []; $selectFields = array_merge($groupByFields, []);
        $inner='inner'; $left='left';
        foreach($languages as $languageId=>$languageCode)
        {
            $t1 = $mainTable . "_" . $languageCode . "_" . $left;
            $t2 = $mainTable . "_" . $languageCode . "_" . $inner;
            $alias[$languageCode][$left] = $t1;
            $alias[$languageCode][$inner] = $t2;
            $selectFields[] = "IF(ANY_VALUE($t1.$localizedFieldName) IS NULL, ANY_VALUE($t2.$localizedFieldName), ANY_VALUE($t1.$localizedFieldName)) as value_$languageCode";
            $innerConditions[$languageCode] = [
                "$mainTable.$mainTableIdField = $t2.$idField",
                "$mainTable.$versionIdField = $t2.$versionIdField",
                "LOWER(HEX($t2.language_id)) = '$defaultLanguage'"
            ];

            $leftConditions[$languageCode] = [
                "$mainTable.$mainTableIdField = $t1.$idField",
                "$mainTable.$versionIdField = $t1.$versionIdField",
                "LOWER(HEX($t1.language_id)) = '$languageId'"
            ];
        }

        $query = $this->connection->createQueryBuilder();
        $query->select($selectFields)
            ->from($mainTable);

        foreach($languages as $languageCode)
        {
            $query->innerJoin($mainTable, $mainTable, $alias[$languageCode][$inner], implode(" AND ", $innerConditions[$languageCode]))
                ->leftJoin($mainTable, $mainTable, $alias[$languageCode][$left], implode(" AND ", $leftConditions[$languageCode]));
        }

        $query->groupBy($groupByFields);
        return $query;
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
     * @return string
     */
    public function getItemMainFile()
    {
        $callingClass = get_called_class();
        return $callingClass::EXPORTER_COMPONENT_ITEM_MAIN_FILE;
    }

    /**
     * @return string
     */
    public function getItemRelationFile()
    {
        $callingClass = get_called_class();
        return $callingClass::EXPORTER_COMPONENT_ITEM_RELATION_FILE;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getChannelId() : string
    {
        return $this->config->getAccountChannelId($this->getAccount());
    }

    /**
     * @return string
     */
    public function getPropertyName() : string
    {
        $callingClass = get_called_class();
        return $callingClass::EXPORTER_COMPONENT_ITEM_NAME;
    }
    /**
     * @return string
     */
    public function getAccount() : string
    {
        return $this->account;
    }

    /**
     * @param string $account
     * @return ItemsAbstract
     */
    public function setAccount(string $account)  : ItemsAbstract
    {
        $this->account = $account;
        return $this;
    }

    /**
     * @return FileHandler
     */
    public function getFiles() : FileHandler
    {
        return $this->files;
    }

    /**
     * @param FileHandler $files
     * @return ItemsAbstract
     */
    public function setFiles(FileHandler $files) : ItemsAbstract
    {
        $this->files = $files;
        return $this;
    }

    /**
     * @param ContentLibrary $library
     * @return ExporterComponentAbstract
     */
    public function setLibrary(ContentLibrary $library) : ItemsAbstract
    {
        $this->library = $library;
        return $this;
    }

    /**
     * @return ContentLibrary
     */
    public function getLibrary() : ContentLibrary
    {
        return $this->library;
    }

    /**
     * @return array
     */
    public function getExportedProductIds() : array
    {
        return $this->exportedProductIds;
    }

    /**
     * @param array $ids
     * @return ItemsAbstract
     */
    public function setExportedProductIds(array $ids) : ItemsAbstract
    {
        $this->exportedProductIds = $ids;
        return $this;
    }

}
