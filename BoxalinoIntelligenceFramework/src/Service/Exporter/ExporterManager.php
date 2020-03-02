<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter;

use Boxalino\IntelligenceFramework\Service\Exporter\Util\Configuration;
use Boxalino\IntelligenceFramework\Service\Exporter\Exporter;
use Boxalino\IntelligenceFramework\Service\Exporter\ExporterScheduler;
use \Psr\Log\LoggerInterface;


abstract class ExporterManager
{

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var Configuration containing the access to the configuration of each store to export
     */
    protected $config = null;

    /**
     * @var []
     */
    protected $deltaIds = [];

    /**
     * @var ExporterScheduler
     */
    protected $scheduler;

    /**
     * @var null
     */
    protected $latestRun = null;

    /**
     * @var Exporter
     */
    protected $exporterService;


    public function __construct(
        LoggerInterface $logger,
        Configuration $exporterConfigurator,
        ExporterScheduler $scheduler,
        Exporter $exporterService
    ) {
        $this->config = $exporterConfigurator;
        $this->logger = $logger;
        $this->scheduler = $scheduler;
        $this->exporterService = $exporterService;
    }


    /**
     * @TODO add date display by UTC and store view for latest runs/etc
     *
     * @return bool
     * @throws \Exception
     */
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
        $this->logger->info("BxIndexLog: starting Boxalino {$this->getType()} exporter process. Latest update at {$latestRun} (UTC)");
        $exporterHasRun = false;
        foreach($accounts as $account)
        {
            try{
               # if($this->exportAllowedByAccount($account))
                #{
                    $exporterHasRun = true;
                    $this->exporterService
                        ->setDirPath("/media/danneg/Boxalino/")
                        ->setAccount($account)
                        ->setType($this->getType())
                        ->setExporterId($this->getExporterId())
                        ->setExportFull($this->getExportFull())
                        ->setTimeoutForExporter($this->getTimeout($account))
                        ->export();
                #}
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


    public function exportAllowedByAccount(string $account) : bool
    {
        if($this->scheduler->canStartExport($this->getType(), $account))
        {
            return true;
        }

        $this->logger->info("BxIndexLog: The {$this->getType()} export is denied permission to run on account {$account}. Check your exporter configurations.");
        return false;
    }

    /**
     * @return array
     */
    public function getAccounts() : array
    {
        return $this->config->getAccounts();
    }

    /**
     * Get indexer latest updated at
     *
     * @param string $type
     * @param string $account
     * @return string
     */
    public function getLastExport(string $type, string $account) : string
    {
        return $this->scheduler->getLastExportByTypeAccount($type, $account);
    }

    /**
     * Latest run date for the exporter type
     *
     * @return null
     */
    public function getLatestRun() : string
    {
        return $this->scheduler->getLastExportByType($this->getExporterId());
    }

    /**
     * @param string $format
     * @return string
     * @throws \Exception
     */
    public function getCurrentStoreTime(string $format = 'Y-m-d H:i:s') : string
    {
        $time = new \DateTime();
        return $time->format($format);
    }

    abstract function getTimeout(string $account) : int;
    abstract function getIds() : array;
    abstract function exportDeniedOnAccount(string $account) : bool;
    abstract function getType() : string;
    abstract function getExporterId() : string;
    abstract function getExportFull() : bool;

}
