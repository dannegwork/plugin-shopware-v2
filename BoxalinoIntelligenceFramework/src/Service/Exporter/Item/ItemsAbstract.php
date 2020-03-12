<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Item;

use Boxalino\IntelligenceFramework\Service\Exporter\Component\ExporterComponentAbstract;
use Boxalino\IntelligenceFramework\Service\Exporter\ExporterInterface;
use Boxalino\IntelligenceFramework\Service\Exporter\Util\Configuration;
use Boxalino\IntelligenceFramework\Service\Exporter\Util\ContentLibrary;
use Boxalino\IntelligenceFramework\Service\Exporter\Util\FileHandler;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

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


    abstract function export();
    abstract function getRequiredFields() : array;


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
     * @param array $ids
     * @return ItemsAbstract
     */
    public function setExportedProductIds(array $ids) : ItemsAbstract
    {
        $this->exportedProductIds = $ids;
        return $this;
    }

}