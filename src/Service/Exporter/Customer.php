<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter;


class Customer implements ExporterInterface
{

    protected $config;
    protected $account;
    protected $files;
    protected $log;

    public function __construct()
    {
        $this->db = Shopware()->Db();
        $this->log = Shopware()->PluginLogger();
    }

    public function export()
    {
        if (!$this->config->isCustomersExportEnabled($this->account)) {
            $this->log->info("BxIndexLog: the customers are not to be exported;");
            return true;
        }

        set_time_limit(7200);
        $this->log->info("BxIndexLog: Preparing customers.");
    }

    /**
     * @param mixed $config
     * @return Customer
     */
    public function setConfig($config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @param mixed $account
     * @return Customer
     */
    public function setAccount($account)
    {
        $this->account = $account;
        return $this;
    }

    /**
     * @param mixed $files
     * @return Customer
     */
    public function setFiles($files)
    {
        $this->files = $files;
        return $this;
    }

}