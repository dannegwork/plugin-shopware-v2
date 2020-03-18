<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Component;

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
     * @var LoggerInterface
     */
    protected $logger;


    /**
     * @param Connection $connection
     */
    public function __construct(
        Connection $connection,
        LoggerInterface $logger
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
    public function getPropertiesByTableList(array $tables) : array
    {
        $database = $this->connection->getDatabase();
        $query = $this->connection->createQueryBuilder();
        $query->select(['COLUMN_NAME', 'TABLE_NAME'])
            ->from('information_schema.columns')
            ->andWhere('information_schema.columns.TABLE_SCHEMA = :database')
            ->andWhere('information_schema.columns.TABLE_NAME IN (:tables)')
            ->setParameter("database", $database, ParameterType::STRING)
            ->setParameter("tables", implode(",", $tables), ParameterType::STRING);

        return $query->execute()->fetchAll();
    }

}
