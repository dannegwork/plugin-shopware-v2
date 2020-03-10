<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Component;

use Boxalino\IntelligenceFramework\Service\Exporter\ExporterScheduler;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Psr\Log\LoggerInterface;

class ComponentResource
{

    /**
     * @var Connection
     */
    protected $connection;


    /**
     * @var Psr\Log\LoggerInterface
     */
    protected $logger;


    /**
     * @param Connection $connection
     */
    public function __construct(
        Connection $connection,
        Psr\Log\LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->logger = $logger;
    }


    /**
     * @param string $table
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getColumnsByTableName(string $table) : array
    {
        return $this->connection->fetchColumn("DESCRIBE {$table}");
    }

    /**
     * @param string $table
     * @return array
     */
    public function getTableContent(string $table) : array
    {
        return $this->connection->fetchAll("SELECT * FROM {$table}");
    }

    /**
     * @param array $tables
     * @return mixed[]
     */
    public function getPropertiesByTableList(array $tables)
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

}