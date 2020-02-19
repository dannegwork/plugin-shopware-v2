<?php
namespace Boxalino\Models\Exporter;

class Order implements ExporterInterface
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
        if (!$this->config->isTransactionsExportEnabled($this->account)) {
            $this->log->info("BxIndexLog: the transactions are not to be exported;");
            return true;
        }

        set_time_limit(7200);
        $this->log->info("BxIndexLog: Preparing transactions.");
    }

    /**
     * @param mixed $config
     * @return Order
     */
    public function setConfig($config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @param mixed $account
     * @return Order
     */
    public function setAccount($account)
    {
        $this->account = $account;
        return $this;
    }

    /**
     * @param mixed $files
     * @return Order
     */
    public function setFiles($files)
    {
        $this->files = $files;
        return $this;
    }


}