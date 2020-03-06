<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Component;


use Boxalino\IntelligenceFramework\Service\Exporter\Util\Configuration;
use Boxalino\IntelligenceFramework\Service\Exporter\Util\ContentLibrary;
use Boxalino\IntelligenceFramework\Service\Exporter\Util\FileHandler;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use \Psr\Log\LoggerInterface;

abstract class ExporterComponentAbstract implements ExporterComponentInterface
{
    CONST EXPORTER_COMPONENT_ID_FIELD = "";
    CONST EXPORTER_COMPONENT_MAIN_FILE = "";
    const EXPORTER_COMPONENT_TYPE = "";

    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @var string
     */
    protected $account;

    /**
     * @var FileHandler
     */
    protected $files;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $success;

    /**
     * @var ContentLibrary
     */
    protected $library;

    /**
     * @var bool
     */
    protected $delta = false;

    /**
     * @var Connection
     */
    protected $connection;


    /**
     * ExporterComponentAbstract constructor.
     *
     * @param Connection $connection
     * @param LoggerInterface $logger
     * @param Configuration $exporterConfigurator
     */
    public function __construct(
        Connection $connection,
        LoggerInterface $logger,
        Configuration $exporterConfigurator
    ){
        $this->connection = $connection;
        $this->config = $exporterConfigurator;
        $this->logger = $logger;
    }

    abstract function exportComponent();
    abstract function getRequiredProperties() : array;
    abstract function getFields() : array;


    /**
     * Common logic for component export
     */
    public function export()
    {
        set_time_limit(7200);

        try {
            $this->exportComponent();

            $this->logger->info("BxIndexLog: {$this->getComponent()} - exporting additional tables for account: {$this->getAccount()}");
            $this->exportExtraTables();
        } catch (\Exception $exc)
        {
            $this->logger->error("BxIndexLog: {$this->getComponent()} export failed: " . $exc->getMessage());
        }
    }

    /**
     * Exporting additional tables that are related to entities
     * No logic on the connection is defined
     * To be added in the ETL
     *
     * @return $this
     * @throws \Exception
     */
    public function exportExtraTables()
    {
        $component = $this->getComponent();
        $files = $this->getFiles();
        $tables = $this->config->getAccountExtraTablesByComponent($this->getAccount(), $component);
        if(empty($tables))
        {
            $this->logger->info("BxIndexLog: {$component} no additional tables have been found.");
            return $this;
        }

        foreach($tables as $table)
        {
            $this->logger->info("BxIndexLog:  Extra table - {$table}.");
            try{
                $columns = $this->getColumnsByTableName($table);
                $tableContent = $this->getTableContent($table);
                if(!is_array($tableContent))
                {
                    throw new \Exception("Extra table {$table} content empty.");
                }
                $dataToSave = array_merge(array(array_keys(end($tableContent))), $tableContent);

                $fileName = "extra_". $table . ".csv";
                $files->savePartToCsv($fileName, $dataToSave);

                $this->getLibrary()->addExtraTableToEntity($files->getPath($fileName), $component, reset($columns), $columns);
                $this->logger->info("BxIndexLog:  {$component} - additional table {$table} exported.");
            } catch (\Exception $exception)
            {
                $this->logger->info("BxIndexLog: {$component} additional table export error:". $exception->getMessage());
                continue;
            }
        }

        return $this;
    }

    /**
     * @param string $table
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getColumnsByTableName(string $table) : array
    {
        $columns = $this->connection->fetchColumn("DESCRIBE {$table}");
        if(empty($columns))
        {
            throw new \Exception("BxIndexLog: {$table} does not exist.");
        }

        return $columns;
    }

    /**
     * @param string $table
     * @return array
     */
    protected function getTableContent(string $table) : array
    {
        try {
            return $this->connection->fetchAll("SELECT * FROM {$table}");
        } catch(\Exception $exc)
        {
            $this->logger->warning("BxIndexLog: {$table} - additional table error: ". $exc->getMessage());
            return [];
        }
    }

    protected function getPropertiesByTableList(array $tables)
    {
        $query = $this->connection->createQueryBuilder();
        $query->select(['COLUMN_NAME', 'TABLE_NAME'])
            ->from('information_schema.columns')
            ->andWhere('information_schema.columns.TABLE_SCHEMA = :database')
            ->andWhere('information_schema.columns.TABLE_NAME IN (:tables)')
            ->setParameter("database", $this->connection->getDatabase(), ParameterType::STRING)
            ->setParameter("tables", implode(",", $tables), ParameterType::STRING);

        return $query->execute()->fetchAll();
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
     * @return ExporterComponentAbstract
     */
    public function setAccount(string $account) : ExporterComponentAbstract
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
     * @return ExporterComponentAbstract
     */
    public function setFiles(FileHandler $files) : ExporterComponentAbstract
    {
        $this->files = $files;
        return $this;
    }

    /**
     * @param ContentLibrary $library
     * @return ExporterComponentAbstract
     */
    public function setLibrary(ContentLibrary $library) : ExporterComponentAbstract
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
     * @param bool $value
     * @return ExporterComponentAbstract
     */
    public function setIsDelta($value) : ExporterComponentAbstract
    {
        $this->delta = $value;
        return $this;
    }

    /**
     * Component name
     * Matches the entity name required by the library (customers, products, transactions)
     *
     * @return string
     */
    public function getComponent()
    {
        return self::EXPORTER_COMPONENT_TYPE;
    }

    /**
     * @return string
     */
    public function getComponentMainFile()
    {
        return self::EXPORTER_COMPONENT_MAIN_FILE;
    }

    public function getComponentIdField()
    {
        return self::EXPORTER_COMPONENT_ID_FIELD;
    }

}