<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter;

use Boxalino\IntelligenceFramework\Service\Exporter\Component\Customer;
use Boxalino\IntelligenceFramework\Service\Exporter\Component\Order;
use Boxalino\IntelligenceFramework\Service\Exporter\Component\Product;
use Boxalino\IntelligenceFramework\Service\Exporter\Util\Configuration;
use Boxalino\IntelligenceFramework\Service\Exporter\Util\FileHandler;
use Boxalino\IntelligenceFramework\Service\Exporter\Util\ContentLibrary;
use Boxalino\IntelligenceFramework\Service\Exporter\ExporterScheduler;
use Doctrine\DBAL\Connection;
use LogicException;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Psr\Log\LoggerInterface;

/**
 * Class Exporter
 * Data exporting service
 * @package Boxalino\IntelligenceFramework\Service\Exporter
 */
class Exporter
{

    /**
     * @var bool
     */
    protected $isFull = false;

    /**
     * @var null
     */
    protected $lastExport = null;
    protected $customerExporter;
    protected $transactionExporter;
    protected $productExporter;

    protected $directory = null;
    protected $logger;
    protected $scheduler;
    protected $fileHandler;
    protected $configurator;

    protected $deltaIds = array();
    protected $library;
    protected $files;
    protected $account;
    protected $type;
    protected $exporterId;
    protected $timeout;


    public function __construct(
        Order $transactionExporter,
        Customer $customerExporter,
        Product $productExporter,
        LoggerInterface $logger,
        Configuration $exporterConfigurator,
        ContentLibrary $library,
        FileHandler $fileHandler,
        ExporterScheduler $scheduler
    ) {
        $this->transactionExporter = $transactionExporter;
        $this->customerExporter = $customerExporter;
        $this->productExporter = $productExporter;

        $this->configurator = $exporterConfigurator;
        $this->logger = $logger;
        $this->library = $library;
        $this->fileHandler = $fileHandler;
        $this->scheduler = $scheduler;
    }

    /**
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */
    public function run()
    {
        set_time_limit(7200);
        $account = $this->getAccount();
        $directory = $this->getDirectory();

        try {
            if(empty($account) || empty($directory))
            {
                throw new \Exception("BxIndexLog: Cancelled Boxalino {$this->getType()} data sync. The account/directory path name can not be empty.");
            }

//            if(!$this->scheduler->canStartExport($this->getType(), $this->getAccount()))
  //          {
    //            throw new \Exception("BxIndexLog: Cancelled Boxalino {$this->getType()} data sync on {$account}. A different process is currently running.");
      //      }

            $this->logger->info("BxIndexLog: Start of Boxalino {$this->getType()} data sync.");
            if(!$this->getIsFull())
            {
                $this->logger->info("BxIndexLog: Exporting products updated since {$this->getLastExport()} data sync.");
            }

            $this->scheduler->updateScheduler(date("Y-m-d H:i:s"), $this->getType(), ExporterScheduler::BOXALINO_EXPORTER_STATUS_PROCESSING, $account);
            $this->logger->info("BxIndexLog: Exporting store ID : {$this->configurator->getAccountChannelId($account)}");

            $this->initFiles();
            $this->initLibrary();
            $this->verifyCredentials();

            $this->logger->info('BxIndexLog: Preparing the attributes and category data for each language of the account: ' . $account);
            $this->logger->info("BxIndexLog: Preparing products.");

            $this->exportProducts();
            $this->exportOthers();

            if (!$this->productExporter->getSuccess()) {
                $this->logger->info('BxIndexLog: No Products found for account: ' . $account);
                $this->logger->info('BxIndexLog: Finished account: ' . $account);
            } else {
                $this->prepareXmlConfigurations();
                $this->pushToDI();
            }

            $this->logger->info("BxIndexLog: End of Boxalino {$this->getType()} data sync on account {$account}");
            $this->scheduler->updateScheduler(date("Y-m-d H:i:s"), $this->getType(), ExporterScheduler::BOXALINO_EXPORTER_STATUS_SUCCESS, $this->getAccount());
            $this->logger->info("BxIndexLog: Log boxalino_exports {$this->getType()} data sync end for account {$account}");
        } catch(\Throwable $e) {
            $this->logger->info("BxIndexLog: failed with exception: " . $e->getMessage());

            $this->logger->info("BxIndexLog: Log boxalino_exports {$this->getType()} data sync end for account {$account}");
            $this->scheduler->updateScheduler(date("Y-m-d H:i:s"), $this->getType(), ExporterScheduler::BOXALINO_EXPORTER_STATUS_FAIL, $this->getAccount());
            $systemMessages[] = "BxIndexLog: failed with exception: ". $e->getMessage();

            throw new \Exception(implode(",",$systemMessages));
        }

        $this->logger->info("BxIndexLog: End of Boxalino {$this->getType()} data sync on account {$account}");
    }


    /**
     * Exporting products and product elements (tags, manufacturers, category, prices, reviews, etc)
     */
    public function exportProducts()
    {
        $this->productExporter->setAccount($this->getAccount())
            ->setFiles($this->getFiles())
            ->setLibrary($this->getLibrary())
            ->setIsDelta(!$this->getIsFull())
            ->setType($this->getType());

        $this->productExporter->export();
    }

    /**
     * Get the last export time for the account (used for deltas)
     * @return string|null
     */
    protected function getLastExport()
    {
        if(is_null($this->lastExport))
        {
            $this->lastExport = $this->scheduler->getLastSuccessfulExportByAccount($this->getAccount());
        }

        return $this->lastExport;
    }

    /**
     * Initializes export directory and files handler for the process
     */
    protected function initFiles()
    {
        $this->logger->info("BxIndexLog: Initialize files for account: {$this->getAccount()}");

        $this->fileHandler->setAccount($this->getAccount())
            ->setType($this->getType())
            ->setMainDir($this->getDirectory())
            ->init();
    }

    /**
     * Initializes the xml/zip content library
     */
    protected function initLibrary()
    {
        $this->logger->info("BxIndexLog: Initialize content library for account: {$this->getAccount()}");

        $this->getLibrary()->setAccount($this->getAccount())
            ->setPassword($this->configurator->getAccountPassword($this->getAccount()))
            ->setIsDelta($this->getDelta())
            ->setUseDevIndex($this->configurator->useDevIndex($this->getAccount()))
            ->setLanguages($this->configurator->getAccountLanguages($this->getAccount())); #@TODO CREATE ACCESSOR FOR LANGUAGES
    }

    /**
     * Verifies credentials to the DI
     * If the server is too busy it will trigger a timeout but the export should not be stopped
     */
    protected function verifyCredentials()
    {
        $this->logger->info("BxIndexLog: verify credentials for account: {$this->getAccount()}");
        try {
            $this->getLibrary()->verifyCredentials();
        } catch(\LogicException $e){
            $this->logger->warning("BxIndexLog: verifyCredentials returned a timeout: {$e->getMessage()}");
        } catch (\Throwable $e){
            $this->logger->error("BxIndexLog: verifyCredentials failed with exception: {$e->getMessage()}");
        }
    }

    /**
     * @return bool|string
     * @throws \Exception
     */
    protected function prepareXmlConfigurations() : string
    {
        if (!$this->getIsFull())
        {
            return false;
        }

        $this->logger->info('BxIndexLog: Prepare the final files: ' . $this->getAccount());
        $this->logger->info('BxIndexLog: Prepare XML configuration file: ' . $this->getAccount());

        try {
            $this->logger->info('BxIndexLog: Push the XML configuration file to the Data Indexing server for account: ' . $this->getAccount());
            $this->getLibrary()->pushDataSpecifications();
        } catch(\LogicException $e){
            $this->logger->warning('BxIndexLog: publishing XML configurations returned a timeout: ' . $e->getMessage());
        } catch (\Throwable $e) {
            $value = @json_decode($e->getMessage(), true);
            if (isset($value['error_type_number']) && $value['error_type_number'] == 3) {
                $this->logger->info('BxIndexLog: Try to push the XML file a second time, error 3 happens always at the very first time but not after: ' . $this->getAccount());
                $this->getLibrary()->pushDataSpecifications();
            } else {
                $this->logger->error("BxIndexLog: pushDataSpecifications failed with exception: " . $e->getMessage() . " If you have attribute changes, please check with Boxalino.");
                throw new \Exception("BxIndexLog: pushDataSpecifications failed with exception: " . $e->getMessage());
            }
        }

        $this->logger->info('BxIndexLog: Publish the configuration changes from the owner for account: ' . $this->getAccount());
        if($this->configurator->publishConfigurationChanges($this->getAccount()))
        {
            $changes = $this->getLibrary()->publishChanges();
            if (sizeof($changes['changes']) > 0) {
                $this->logger->info("BxIndexLog: changes in configuration detected and published for account " . $this->getAccount());
            }
            if(isset($changes['token']))
            {
                $this->logger->info("BxIndexLog: New token for account {$this->getAccount()} - {$changes['token']}");
            }
        }

        $this->logger->info('BxIndexLog: NORMAL - stop waiting for Data Intelligence processing for account: ' . $this->getAccount());
    }

    /**
     * @return array|string
     */
    protected function pushToDI() :string
    {
        $this->logger->info('BxIndexLog: pushing to DI for account: ' . $this->getAccount());
        try {
            $this->getLibrary()->pushData($this->configurator->getExportTemporaryArchivePath($this->getAccount()), $this->getTimeout());
        } catch(\LogicException $e){
            $this->logger->warning($e->getMessage());
        }
    }

    /**
     * export other components (orders, customers)
     */
    public function exportOthers() : void
    {
        if($this->getType() == ExporterScheduler::BOXALINO_EXPORTER_TYPE_FULL)
        {
            $this->transactionExporter->setFiles($this->getFiles())->setAccount($this->getAccount())->setConfig($this->configurator);
            $this->transactionExporter->export();

            $this->customerExporter->setFiles($this->getFiles())->setAccount($this->getAccount())->setConfig($this->configurator);
            $this->customerExporter->export();
        }
    }

    /**
     * @return bool
     */
    public function getIsFull() : bool
    {
        return $this->isFull;
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function setIsFull(bool $value)
    {
        $this->isFull = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getDirectory() : string
    {
        return $this->directory;
    }

    /**
     * @param mixed $directory
     * @return Exporter
     */
    public function setDirectory(string $directory) : Exporter
    {
        $this->directory = $directory;
        return $this;
    }

    /**
     * @param string $value
     * @return Exporter
     */
    public function setType(string $value) : Exporter
    {
        $this->type = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * @return FileHandler
     */
    public function getFiles() : FileHandler
    {
        return $this->files;
    }

    /**
     * @return ContentLibrary
     */
    public function getLibrary() : ContentLibrary
    {
        return $this->library;
    }

    /**
     * @param string $account
     * @return $this
     */
    public function setAccount(string $account)
    {
        $this->account = $account;
        return $this;
    }

    /**
     * @return string
     */
    public function getAccount() : string
    {
        return $this->account;
    }

    /**
     * @param string $id
     * @return Exporter
     */
    public function setExporterId(string $id) : Exporter
    {
        $this->exporterId = $id;
        return $this;
    }

    /**
     * @param string $timeout
     * @return Exporter
     */
    public function setTimeout(string $timeout) : Exporter
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * @return int
     */
    public function getTimeout() : int
    {
        return $this->timeout;
    }

}
