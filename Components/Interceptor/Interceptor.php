<?php
namespace Boxalino\Components\Interceptor;

use Boxalino\Components\Benchmark\Benchmark;
use Boxalino\Components\Event\EventReporter;
use Boxalino\Helper\BxData;
use Boxalino\Helper\P13NHelper;
use Enlight_Controller_Request_Request;
use Enlight_Event_EventArgs;
use Enlight_View_Default;
use Shopware_Components_Config;
use Shopware_Controllers_Frontend_Index;
use Boxalino\Boxalino;

class Interceptor
{
    /**
     * @var Shopware_Components_Config
     */
    private $config;

    /**
     * @var P13NHelper
     */
    private $helper;

    /**
     * @var Shopware_Controllers_Frontend_Index
     */
    private $controller;

    /**
     * @var Enlight_Controller_Request_Request
     */
    private $request;

    /**
     * @var Enlight_View_Default
     */
    private $view;

    /**
     * @var Benchmark
     */
    private $benchmark;

    /**
     * @var bool
     */
    protected $isActive;

    /**
     * @var EventReporter
     */
    protected $eventReporter;

    /**
     * @var Boxalino
     */
    protected $setup;

    /**
     * @var BxData
     */
    protected $bxData;

    /**
     * constructor
     * @param Boxalino $setup
     * @param Benchmark $benchmark
     * @param P13NHelper $helper
     * @param BxData $bxData
     */
    public function __construct(
        Boxalino $setup,
        Benchmark $benchmark,
        P13NHelper $helper,
        BxData $bxData,
        EventReporter $eventReporter
    ) {
        $this->isActive = (bool) Shopware()->Config()->get('boxalino_active');
        $this->benchmark = $benchmark;
        $this->helper = $helper;
        $this->bxData = $bxData;
        $this->config = Shopware()->Config();
        $this->setup = $setup;
        $this->eventReporter = $eventReporter;
    }

    /**
     * Initialize important variables
     * @param Enlight_Event_EventArgs $arguments
     */
    protected function init(Enlight_Event_EventArgs $arguments)
    {
        $this->controller = $arguments->getSubject();
        $this->request = $this->controller->Request();
        $this->view = $this->controller->View();
        $this->helper->setRequest($this->request);
    }

    public function getEventReporter()
    {
        return $this->eventReporter;
    }

    /**
     * Returns config instance
     *
     * @return Shopware_Components_Config
     */
    public function Config()
    {
        return $this->config;
    }

    /**
     * Returns helper instance
     *
     * @return P13NHelper
     */
    public function Helper()
    {
        return $this->helper;
    }

    /**
     * Returns controller instance
     *
     * @return Shopware_Controllers_Frontend_Index
     */
    public function Controller()
    {
        return $this->controller;
    }

    /**
     * Returns request instance
     *
     * @return Enlight_Controller_Request_Request
     */
    public function Request()
    {
        return $this->request;
    }

    /**
     * Returns view instance
     *
     * @return Enlight_View_Default
     */
    public function View()
    {
        return $this->view;
    }

    /**
     * @return Benchmark
     */
    public function Benchmark()
    {
        return $this->benchmark;
    }

    public function BxData() {
        return $this->bxData;
    }

    /**
     * @return string
     */
    public function getPluginPath()
    {
        return $this->setup->getPath();
    }

    /**
     * @return Boxalino
     */
    public function Boxalino()
    {
        return $this->setup;
    }
    
}