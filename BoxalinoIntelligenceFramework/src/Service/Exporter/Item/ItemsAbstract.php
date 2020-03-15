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
    CONST EXPORTER_COMPONENT_ITEM_NAME = "";
    CONST EXPORTER_COMPONENT_ITEM_MAIN_FILE = '';
    CONST EXPORTER_COMPONENT_ITEM_RELATION_FILE = '';

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
     * @return array
     * @throws \Exception
     */
    public function getLanguageHeaders() : array
    {
        return preg_filter('/^/', 'value_',$this->config->getAccountLanguages($this->getAccount()));
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
     * @param array $selectFields
     * @return \Doctrine\DBAL\Query\QueryBuilder
     * @throws \Shopware\Core\Framework\Uuid\Exception\InvalidUuidException
     * @throws \Exception
     */
    public function getLocalizedFields(string $mainTable, string $mainTableIdField, string $idField,
                                       string $versionIdField, string $localizedFieldName, array $groupByFields) : QueryBuilder
    {
        $languages = $this->config->getAccountLanguages($this->getAccount());
        $defaultLanguage = $this->config->getChannelDefaultLanguageId($this->getAccount());
        $alias = []; $innerConditions = []; $leftConditions = []; $selectFields = $groupByFields;
        foreach($languages as $languageId=>$languageCode)
        {
            $alias[$languageCode] = $mainTable . "_" . $languageCode;
            $selectFields[] = "IF(IS_NULL($alias.$localizedFieldName), $mainTable.$localizedFieldName, $alias.$localizedFieldName) as value_$languageCode";
            $innerConditions[$languageCode] = [
                "$mainTable.$mainTableIdField = $alias.$idField",
                "$mainTable.$versionIdField =  $alias.$versionIdField",
                "$mainTable.language_id = $defaultLanguage"
            ];

            $leftConditions[$languageCode] = [
                "$mainTable.$mainTableIdField = $alias.$idField",
                "$mainTable.$versionIdField = $alias.$versionIdField",
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
        return self::EXPORTER_COMPONENT_ITEM_NAME;
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
