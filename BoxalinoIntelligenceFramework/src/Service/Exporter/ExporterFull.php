<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter;

class ExporterFull extends ExporterManager
{

    const EXPORTER_ID = 'boxalino.exporter.full';

    const EXPORTER_TYPE = 'full';

    /**
     * Default server timeout
     */
    const SERVER_TIMEOUT_DEFAULT = 3000;

    public function getType(): string
    {
        return self::EXPORTER_TYPE;
    }

    public function getExporterId(): string
    {
        return self::EXPORTER_ID;
    }

    public function exportDeniedOnAccount($account)
    {
        return false;
    }

    /**
     * Get timeout for exporter
     * @return bool|int
     */
    public function getTimeout($account)
    {
        $customTimeout = $this->config->getExporterTimeout($account);
        if($customTimeout)
        {
            return $customTimeout;
        }

        return self::SERVER_TIMEOUT_DEFAULT;
    }

    /**
     * Latest run date is not checked for the full export
     *
     * @return null
     */
    public function getLatestRun()
    {
        return $this->getLatestUpdatedAt($this->getExporterId());;
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
    public function getExportFull()
    {
        return true;
    }

}