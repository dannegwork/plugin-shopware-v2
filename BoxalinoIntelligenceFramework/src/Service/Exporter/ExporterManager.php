<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter;

use Boxalino\IntelligenceFramework\Service\Exporter\Util\Configuration;
use Boxalino\IntelligenceFramework\Service\Exporter\ExporterService;
use Boxalino\IntelligenceFramework\Service\Exporter\ExporterScheduler;
use \Psr\Log\LoggerInterface;

/**
 * Class ExporterManager
 * Handles generic logic for the data exporting to Boxalino DI server
 *
 * @package Boxalino\IntelligenceFramework\Service\Exporter
 */
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
     * @var null
     */
    protected $account = null;

    /**
     * @var ExporterService
     */
    protected $exporterService;


    public function __construct(
        LoggerInterface $logger,
        Configuration $exporterConfigurator,
        ExporterScheduler $scheduler,
        ExporterService $exporterService
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
        $this->logger->info("BxIndexLog: starting Boxalino {$this->getType()} exporter process.");
        $exporterHasRun = false;
        foreach($accounts as $account)
        {
            try{
                if($this->exportAllowedByAccount($account))
                {
                    $exporterHasRun = true;
                    $this->exporterService
                        ->setAccount($account)
                        ->setType($this->getType())
                        ->setExporterId($this->getExporterId())
                        ->setIsFull($this->getExportFull())
                        ->setTimeout($this->getTimeout($account))
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


    public function exportAllowedByAccount(string $account) : bool
    {
        if($this->scheduler->canStartExport($this->getType(), $account) && !$this->exportDeniedOnAccount($account))
        {
            return true;
        }

        $this->logger->info("BxIndexLog: The {$this->getType()} export is denied permission to run on account {$account}. Check your exporter configurations.");
        return false;
    }

    /**
     * Returns either the specific account to run the exporter for OR the list of accounts configured for all the channels
     *
     * @return array
     */
    public function getAccounts() : array
    {
        if(is_null($this->account))
        {
            return $this->config->getAccounts();
        }

        return [$this->account];
    }

    /**
     * Get indexer latest updated at
     *
     * @param string $account
     * @return string
     */
    public function getLastSuccessfulExport(string $account) : string
    {
        return $this->scheduler->getLastSuccessfulExportByTypeAccount($this->getType(), $account);
    }

    /**
     * @param string $account
     * @return ExporterManager
     */
    public function setAccount(string $account) : ExporterManager
    {
        $this->account = $account;
        return $this;
    }

    abstract function getTimeout(string $account) : int;
    abstract function getIds() : array;
    abstract function exportDeniedOnAccount(string $account) : bool;
    abstract function getType() : string;
    abstract function getExporterId() : string;
    abstract function getExportFull() : bool;

}
