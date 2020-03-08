<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter;

use Boxalino\IntelligenceFramework\Service\Exporter\ExporterScheduler;

class ExporterDelta extends ExporterManager
{

    const EXPORTER_ID = 'boxalino.exporter.delta';

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
     * 2 subsequent deltas can only be run with the time difference allowed
     * the delta after a full export can only be run after the configured time only
     *
     * @param string $account
     * @return bool
     * @throws \Exception
     */
    public function exportDeniedOnAccount(string $account) : bool
    {
        $latestDeltaRunDate = $this->getLastExport($account);
        $latestFullRunDate = $this->scheduler->getLastSuccessfulExportByTypeAccount(ExporterScheduler::BOXALINO_EXPORTER_TYPE_FULL, $account);
        $deltaFrequency = $this->config->getDeltaFrequencyMinInterval($account);
        $deltaFullRange = $this->config->getDeltaScheduleTime($account);

        if($latestFullRunDate != min($latestFullRunDate, date('Y-m-d H:i:s', strtotime("-$deltaFullRange min"))))
        {
            return true;
        }

        if($latestDeltaRunDate == min($latestDeltaRunDate, date("Y-m-d H:i:s", strtotime("-$deltaFrequency min"))))
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
