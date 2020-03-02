<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter;

use Doctrine\DBAL\Connection;

class ExporterScheduler
{

    CONST BOXALINO_EXPORTER_TYPE_DELTA = "delta";
    CONST BOXALINO_EXPORTER_TYPE_FULL = "full";

    CONST BOXALINO_EXPORTER_STATUS_SUCCESS = "success";
    CONST BOXALINO_EXPORTER_STATUS_FAIL = "fail";
    CONST BOXALINO_EXPORTER_STATUS_PROCESSING = "processing";

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
     * @param string $account
     * @return string
     */
    public function getLastExportByAccount(string $account)
    {
        $latestRun = date("Y-m-d H:i:s", strtotime("-30 minutes"));
        $query = $this->connection->createQueryBuilder();
        $query->select(['export_date'])
            ->from("boxalino_exports")
            ->andWhere("account = :account")
            ->andWhere("status = :status")
            ->orderBy("export_date", "DESC")
            ->setMaxResults(1)
            ->setParameter("account", $account)
            ->setParameter("status", self::BOXALINO_EXPORTER_STATUS_SUCCESS);
        $latestRecord = $query->execute()->fetchColumn();
        if($latestRecord['export_date'])
        {
            $latestRun = $latestRecord['export_date'];
        }

        return $latestRun;
    }

    /**
     * @param string $type
     * @param string $account
     * @return string
     */
    public function getLastExportByTypeAccount(string $type, string $account)
    {
        $query = $this->connection->createQueryBuilder();
        $query->select(['export_date'])
            ->from("boxalino_exports")
            ->andWhere("account = :account")
            ->andWhere("status = :status")
            ->andWhere("type = :type")
            ->orderBy("export_date", "DESC")
            ->setMaxResults(1)
            ->setParameter("account", $account)
            ->setParameter("type", $type)
            ->setParameter("status", self::BOXALINO_EXPORTER_STATUS_SUCCESS);
        $latestRecord = $query->execute()->fetchColumn();

        return $latestRecord['export_date'];
    }

    /**
     * @param string $type
     * @return string
     */
    public function getLastExportByType(string $type)
    {
        $query = $this->connection->createQueryBuilder();
        $query->select(['export_date'])
            ->from("boxalino_exports")
            ->andWhere("account = :account")
            ->andWhere("type = :type")
            ->orderBy("export_date", "DESC")
            ->setMaxResults(1)
            ->setParameter("type", $type);
        $latestRecord = $query->execute()->fetchColumn();

        return $latestRecord['export_date'];
    }

    /**
     * The export table is truncated
     * @param string $type
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function clearExportTable(string $type, string $account) : bool
    {
        if(is_null($type))
        {
            $this->connection->delete("boxalino_exports", ["account"=>$account]);
            return true;
        }

        $this->connection->delete("boxalino_exports", ["account"=>$account, "type" => $type]);
        return true;
    }


    /**
     * The export table is displayed
     * @return []
     */
    public function viewExportTable() : array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select()
            ->from("boxalino_exports");

        return $query->execute()->fetchAll();
    }


    /**
     * 1. Check if there is any active running process with status PROCESSING
     * 1.1 If there is none - the full export can start regardless; if it is a delta export - it is allowed to be run at least 30min after a full one
     * 2. When there are processes with "PROCESSING" state:
     * 2.1 if the time difference is less than 15 min - stop store export
     * 2.2 if it is an older process which got stuck - allow the process to start if it does not block a prior full export on the account
     *
     * @param string $type
     * @param string $account
     * @return bool
     */
    public function canStartExport(string $type, string $account) : bool
    {
        $allowedHour = date("Y-m-d H:i:s", strtotime("-30min"));
        $query = $this->connection->createQueryBuilder();
        $query->select(['export_date', 'account'])
            ->from('boxalino_exports')
            ->andWhere('account <> :account')
            ->andWhere('status = :status')
            ->setParameter('account', $account)
            ->setParameter('status', self::BOXALINO_EXPORTER_STATUS_PROCESSING);

        $processes = $query->execute()->fetchAll();
        if(empty($processes))
        {
            if($type == self::BOXALINO_EXPORTER_TYPE_FULL)
            {
                return true;
            }

            $latestFull = $this->getLatestFullExportPerAccount($account);
            if($latestFull['export_date'] === min($allowedHour, $latestFull['export_date']))
            {
                return true;
            }

            return false;
        }

        foreach($processes as $process)
        {
            if($process['export_date'] === min(date("Y-m-d H:i:s", strtotime("-15min")), $process['export_date']))
            {
                continue;
            }

            return false;
        }

        if($type == self::BOXALINO_EXPORTER_TYPE_FULL)
        {
            return true;
        }

        $latestRunOnAccount = $this->getLatestFullExportPerAccount($account);
        if($latestRunOnAccount['export_date'] == min($allowedHour, $latestRunOnAccount['export_date']))
        {
            return true;
        }

        return false;
    }

    /**
     * @param string $account
     * @return mixed
     */
    public function getLatestFullExportPerAccount(string $account)
    {
        $latestRunOnAccount = $this->connection->createQueryBuilder();
        $latestRunOnAccount->select(['export_date'])
            ->from('boxalino_exports')
            ->andWhere('account = :account')
            ->andWhere('type = :type')
            ->setParameter('account', $account)
            ->setParameter('type', self::BOXALINO_EXPORTER_TYPE_FULL);

        return $latestRunOnAccount->execute()->fetchColumn($latestRunOnAccount);
    }

    /**
     * @param string $date
     * @param string $type
     * @param string $status
     * @param string $account
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function updateScheduler(string $date, string $type, string $status, string $account)
    {
        $dataBind = [
            $account,
            $type,
            $date,
            $status
        ];

        $query='INSERT INTO boxalino_exports (account, type, export_date, status) VALUES (?, ?, ?, ?) '.
            'ON DUPLICATE KEY UPDATE '
            . $this->connection->quoteInto("export_date = ?", $date) . ', '
            . $this->connection->quoteInto("status = ?", $status) . ';';

        return $this->connection->executeUpdate(
            $query,
            $dataBind
        );
    }

}