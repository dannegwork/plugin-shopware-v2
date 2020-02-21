<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter;

use Boxalino\IntelligenceFramework\Service\Exporter\Customer;
use Boxalino\IntelligenceFramework\Service\Exporter\Order;
use Boxalino\IntelligenceFramework\Service\Exporter\Product;
use Boxalino\IntelligenceFramework\Service\Exporter\Helper\Configuration as ExporterConfigurator;
use Psr\Log\LoggerInterface;

use Boxalino\Helper\BxIndexConfig;
use com\boxalino\bxclient\v1\BxClient;

class AbstractExporter
{
    CONST BOXALINO_EXPORTER_TYPE_DELTA = "delta";
    CONST BOXALINO_EXPORTER_TYPE_FULL = "full";

    CONST BOXALINO_EXPORTER_STATUS_SUCCESS = "success";
    CONST BOXALINO_EXPORTER_STATUS_FAIL = "fail";
    CONST BOXALINO_EXPORTER_STATUS_PROCESSING = "processing";

    protected $delta = false;
    protected $customerExporter;
    protected $transactionExporter;
    protected $productExporter;

    protected $request;
    protected $manager;

    protected $propertyDescriptions = array();
    protected $dirPath = null;
    protected $plugin;
    protected $connection;
    protected $logger;

    protected $fileHandle;

    protected $deltaIds = array();
    protected $_config;
    protected $bxData;
    protected $_attributes = array();
    protected $config = array();
    protected $locales = array();
    protected $languages = array();
    protected $rootCategories = array();


    public function __construct(
        Order $transactionExporter,
        Customer $customerExporter,
        Product $productExporter,
        LoggerInterface $logger,
        ExporterConfigurator $exporterConfigurator
    ) {
        $this->transactionExporter = $transactionExporter;
        $this->customerExporter = $customerExporter;
        $this->productExporter = $productExporter;

        $this->configurator = $exporterConfigurator;
        $this->db = Shopware()->Db();
        $this->logger = $logger;

        $this->plugin = new Boxalino();
        $libPath = $this->plugin->getPath() . '/lib';
        require_once($libPath . '/BxClient.php');
        BxClient::LOAD_CLASSES($libPath);
    }

    /**
     * @return mixed
     */
    public function getDelta()
    {
        return $this->delta;
    }

    /**
     * @param mixed $delta
     */
    public function setDelta($delta)
    {
        $this->delta = $delta;
    }

    /**
     * @return mixed
     */
    public function getDirPath()
    {
        return $this->dirPath;
    }

    /**
     * @param mixed $dirPath
     */
    public function setDirPath($dirPath)
    {
        $this->dirPath = $dirPath;
    }

}