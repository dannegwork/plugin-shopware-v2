<?php
namespace Boxalino\Models\Exporter;

use Boxalino\Boxalino;
use Boxalino\Helper\BxIndexConfig;
use com\boxalino\bxclient\v1\BxClient;

class AbstractExporter
{
    CONST BXL_EXPORTER_TYPE_DELTA = "delta";
    CONST BXL_EXPORTER_TYPE_FULL = "full";

    protected $delta = false;
    protected $customerExporter;
    protected $transactionExporter;
    protected $productExporter;

    protected $request;
    protected $manager;

    protected $propertyDescriptions = array();

    protected $dirPath;


    protected $plugin;
    protected $db;
    protected $log;


    protected $fileHandle;

    protected $deltaIds = array();
    protected $_config;
    protected $bxData;
    protected $_attributes = array();
    protected $config = array();
    protected $locales = array();
    protected $languages = array();
    protected $rootCategories = array();



    /**
     * constructor
     *
     * @param string $dirPath
     * @param bool   $delta
     */
    public function __construct()
    {

        $this->transactionExporter = new Order();
        $this->customerExporter = new Order();
        $this->productExporter = new Product();

        $this->config = new BxIndexConfig();
        $this->db = Shopware()->Db();
        $this->log = Shopware()->PluginLogger();

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