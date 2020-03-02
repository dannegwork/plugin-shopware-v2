<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter;

use Boxalino\IntelligenceFramework\Service\Exporter\ExporterScheduler;

class ExporterFull extends ExporterManager
{

    const EXPORTER_ID = 'boxalino.exporter.full';

    /**
     * Default server timeout
     */
    const SERVER_TIMEOUT_DEFAULT = 300;

    public function getType(): string
    {
        return ExporterScheduler::BOXALINO_EXPORTER_TYPE_FULL;
    }

    public function getExporterId(): string
    {
        return self::EXPORTER_ID;
    }

    public function exportDeniedOnAccount(string $account) : bool
    {
        return false;
    }

    /**
     * Get timeout for exporter
     * @param string $account
     * @return bool|int
     */
    public function getTimeout(string $account) : int
    {
        $customTimeout = $this->config->getExporterTimeout($account);
        if($customTimeout)
        {
            return $customTimeout;
        }

        return self::SERVER_TIMEOUT_DEFAULT;
    }


    /**
     * Full export does not care for ids -- everything is exported
     *
     * @return array
     */
    public function getIds(): array
    {
        return [];
    }

    /**
     * @return bool
     */
    public function getExportFull() : bool
    {
        return true;
    }

}