<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter;

use Boxalino\IntelligenceFramework\Service\Exporter\Component\Customer;
use Boxalino\IntelligenceFramework\Service\Exporter\Component\Order;
use Boxalino\IntelligenceFramework\Service\Exporter\Component\Product;
use Boxalino\IntelligenceFramework\Service\Exporter\Util\Configuration;
use Boxalino\IntelligenceFramework\Service\Exporter\Util\FileHandler;
use Boxalino\IntelligenceFramework\Service\Exporter\Util\ContentLibrary;
use Doctrine\DBAL\Connection;
use \Psr\Log\LoggerInterface;


abstract class ExporterManager
{

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Boxalino\Intelligence\Helper\BxIndexConfig : containing the access to the configuration of each store to export
     */
    protected $config = null;

    /**
     * @var []
     */
    protected $deltaIds = [];

    /**
     * @var \Magento\Indexer\Model\Indexer
     */
    protected $indexerModel;

    /**
     * @var ProcessManagerResource
     */
    protected $processResource;

    /**
     * @var null
     */
    protected $latestRun = null;

    /**
     * @var \Boxalino\Intelligence\Model\Exporter\Service
     */
    protected $exporterService;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;


    public function __construct(
        Connection $connection,
        Order $transactionExporter,
        Customer $customerExporter,
        Product $productExporter,
        LoggerInterface $logger,
        Configuration $exporterConfigurator,
        ContentLibrary $library,
        FileHandler $bxFiles
    ) {
        $this->transactionExporter = $transactionExporter;
        $this->customerExporter = $customerExporter;
        $this->productExporter = $productExporter;

        $this->config = $exporterConfigurator;
        $this->connection = $connection;
        $this->logger = $logger;
        $this->library = $library;
        $this->bxFiles = $bxFiles;
    }


    public function run()
    {
        $accounts = $this->getAccounts();
        if(empty($accounts))
        {
            $this->logger->info("BxIndexLog: no active configurations found on either of the stores. Process cancelled.");
            return false;
        }

        $errorMessages = [];
        $latestRun = $this->getLatestRun();
        $this->logger->info("BxIndexLog: starting Boxalino {$this->getType()} exporter process. Latest update at {$latestRun} (UTC)  / {$this->getStoreTime($latestRun)} (store time)");
        $exporterHasRun = false;
        foreach($accounts as $account)
        {
            try{
                if($this->exportAllowedByAccount($account))
                {
                    $exporterHasRun = true;
                    $this->exporterService
                        ->setAccount($account)
                        ->setDeltaIds($this->getIds())
                        ->setIndexerType($this->getType())
                        ->setIndexerId($this->getIndexerId())
                        ->setExportFull($this->getExportFull())
                        ->setTimeoutForExporter($this->getTimeout($account))
                        ->export();
                }
            } catch (\Exception $exception) {
                $errorMessages[] = $exception->getMessage();
                continue;
            }
        }

        if(!$exporterHasRun)
        {
            return false;
        }

        if(empty($errorMessages) && $exporterHasRun)
        {
            return true;
        }

        throw new \Exception("BxIndexLog: Boxalino Exporter failed with messages: " . implode(",", $errorMessages));
    }


    public function processCanRun()
    {
        if(($this->getType() == ExporterDelta::EXPORTER_TYPE) &&  $this->indexerModel->load(BxExporter::INDEXER_ID)->isWorking())
        {
            $this->logger->info("BxIndexLog: Delta exporter will not run. Full exporter process must finish first.");
            return false;
        }

        return true;
    }

    public function exportAllowedByAccount($account)
    {
        if($this->exportDeniedOnAccount($account))
        {
            $this->logger->info("BxIndexLog: The {$this->getType()} export is denied permission to run. Check your exporter configurations.");
            return false;
        }

        return true;
    }

    public function getAccounts()
    {
        return $this->config->getAccounts();
    }

    /**
     * Get indexer latest updated at
     *
     * @param $id
     * @return string
     */
    public function getLatestUpdatedAt($id)
    {
        return $this->processResource->getLatestUpdatedAtByIndexerId($id);
    }

    /**
     * @param $indexerId
     * @param $date
     */
    public function updateProcessRunDate($date)
    {
        $this->processResource->updateIndexerUpdatedAt($this->getIndexerId(), $date);
    }

    public function getCurrentStoreTime($format = 'Y-m-d H:i:s')
    {
        return $this->timezone->date()->format($format);
    }

    public function getStoreTime($date)
    {
        return $this->timezone->formatDate($date, 1, true);
    }

    public function getUtcTime($time=null)
    {
        if(is_null($time)){
            return $this->timezone->convertConfigTimeToUtc($this->getCurrentStoreTime());
        }

        return $this->timezone->convertConfigTimeToUtc($time);
    }

    abstract function getTimeout($account);
    abstract function getLatestRun();
    abstract function getIds();
    abstract function exportDeniedOnAccount($account);
    abstract function getType() : string;
    abstract function getIndexerId() : string;
    abstract function getExportFull();

}