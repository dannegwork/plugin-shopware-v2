<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter;

use Boxalino\IntelligenceFramework\Service\Exporter\ExporterScheduler;

class ExporterDelta extends ExporterManager
{

    const EXPORTER_ID = 'boxalino.exporter.delta';

    const EXPORTER_TYPE = 'delta';

    /**
     * Default server timeout
     */
    const SERVER_TIMEOUT_DEFAULT = 120;

    /**
     * @var array
     */
    protected $ids = [];

    public function getType(): string
    {
        return self::EXPORTER_ID;
    }

    public function getExporterId(): string
    {
        return ExporterScheduler::BOXALINO_EXPORTER_TYPE_DELTA;
    }

    /**
     * Get timeout for exporter
     * @return bool|int
     */
    public function getTimeout(string $account) : int
    {
        return self::SERVER_TIMEOUT_DEFAULT;
    }

    /**
     * If the exporter scheduler is enabled, the delta export time has to be validated
     * 2 subsequent deltas can only be run with the time difference allowed ( > 30min difference)
     * the delta after a full export can only be run after 1h
     *
     * @param string $account
     * @return bool
     */
    public function exportDeniedOnAccount(string $account) : bool
    {
        $latestDeltaRunDate = $this->getLastExport($this->getType(), $account);
        $deltaTimeRange = 60;
        if($latestDeltaRunDate == min($latestDeltaRunDate, date("Y-m-d H:i:s", strtotime("-$deltaTimeRange min"))))
        {
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    public function getIds() : array
    {
        return [];
    }

    /**
     * @return bool
     */
    public function getExportFull() : bool
    {
        return false;
    }

}