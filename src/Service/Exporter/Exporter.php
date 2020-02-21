<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter;

use Psr\Log\LoggerInterface;
use Boxalino\Boxalino\IntelligenceFramework\Service\Exporter\Helper\Content;
use Boxalino\Boxalino\IntelligenceFramework\Service\Exporter\Helper\Configuration;
use com\boxalino\bxclient\v1\BxClient;
use com\boxalino\bxclient\v1\BxData;


class Exporter extends AbstractExporter
{


    /**
     * Run Exporter
     * @return mixed
     */
    public function run()
    {
        set_time_limit(7200);
        $data = array();
        $type = $this->delta ? self::BXL_EXPORTER_TYPE_DELTA : self::BXL_EXPORTER_TYPE_FULL;
        try {
            $this->log->info("BxIndexLog: Start of Boxalino {$type} data sync.");
            $this->log->info("BxIndexLog: Exporting for accounts: " . implode(', ', $this->config->getAccounts()));

            foreach ($this->config->getAccounts() as $account) {
                $this->log->info("BxIndexLog: Exporting store ID : {$this->config->getAccountStoreId($account)}");
                $this->log->info("BxIndexLog: Initialize files on account: {$account}");
                $files = new BxFiles($this->dirPath, $account, $type);

                $this->prepareBxData($account);
                $this->log->info("BxIndexLog: verify credentials for account: " . $account);
                try {
                    $this->bxData->verifyCredentials();
                } catch (\Throwable $e){
                    $this->log->error("BxIndexLog: verifyCredentials failed with exception: {$e->getMessage()}");
                    continue;
                }

                $this->log->info('BxIndexLog: Preparing the attributes and category data for each language of the account: ' . $account);

                $this->log->info("BxIndexLog: Preparing products.");
                $this->productExporter->setAccount($account)->setFiles($files)->setBxData($this->bxData)->setType();
                $this->productExporter->export();

                $this->runExtra($files, $account);
                if (!$this->productExporter->getSuccess()) {
                    $this->log->info('BxIndexLog: No Products found for account: ' . $account);
                    $this->log->info('BxIndexLog: Finished account: ' . $account);
                } else {
                    $this->prepareXmlConfigurations($account);
                    $this->pushDI($account);

                }
            }
        } catch(\Throwable $e) {
            error_log("BxIndexLog: failed with exception: " .$e->getMessage());
            $this->log->info("BxIndexLog: failed with exception: " . $e->getMessage());
        }
        $this->log->info("BxIndexLog: End of boxalino $type data sync ");
        $this->updateExportTable();

        return var_export($data, true);
    }


    protected function prepareBxData($account)
    {
        $bxClient = new BxClient($account, $this->config->getAccountPassword($account), "");
        $this->bxData = new BxData($bxClient, $this->config->getAccountLanguages($account), $this->config->isAccountDev($account), $this->delta);

        return $this->bxData;
    }

    protected function prepareXmlConfigurations($account)
    {
        if ($this->delta)
        {
            return false;
        }

        $this->log->info('BxIndexLog: Prepare the final files: ' . $account);
        $this->log->info('BxIndexLog: Prepare XML configuration file: ' . $account);

        try {
            $this->log->info('BxIndexLog: Push the XML configuration file to the Data Indexing server for account: ' . $account);
            $this->bxData->pushDataSpecifications();
        } catch (\Throwable $e) {
            $value = @json_decode($e->getMessage(), true);
            if (isset($value['error_type_number']) && $value['error_type_number'] == 3) {
                $this->log->info('BxIndexLog: Try to push the XML file a second time, error 3 happens always at the very first time but not after: ' . $account);
                $this->bxData->pushDataSpecifications();
            } else {
                $this->log->info("BxIndexLog: pushDataSpecifications failed with exception: " . $e->getMessage());
            }
        }

        $this->log->info('BxIndexLog: Publish the configuration changes from the owner for account: ' . $account);
        $publish = $this->config->publishConfigurationChanges($account);
        $changes = $this->bxData->publishChanges($publish);
        $data['token'] = $changes['token'];
        if (sizeof($changes['changes']) > 0 && !$publish) {
            $this->log->info("BxIndexLog: changes in configuration detected but not published as publish configuration automatically option has not been activated for account: " . $account);
        }
        $this->log->info('BxIndexLog: NORMAL - stop waiting for Data Intelligence processing for account: ' . $account);

        return true;
    }

    protected function pushDI($account)
    {
        $this->log->info('BxIndexLog: pushing to DI for account: ' . $account);
        try {
            $this->bxData->pushData();
        } catch (\Throwable $e){
            $this->log->info("BxIndexLog: pushData failed with exception for : " . $e->getMessage());
        }
        $this->log->info('BxIndexLog: Finished account: ' . $account);
    }

    protected function runExtra($files, $account)
    {
        if($this->getDelta())
        {
            $this->transactionExporter->setFiles($files)->setAccount($account)->setConfig($this->config);
            $this->transactionExporter->export();

            $this->customerExporter->setFiles($files)->setAccount($account)->setConfig($this->config);
            $this->customerExporter->export();
        }

        return;
    }

}