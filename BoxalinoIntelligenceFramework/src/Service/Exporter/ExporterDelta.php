<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter;

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
        return self::EXPORTER_TYPE;
    }

    /**
     * Get timeout for exporter
     * @return bool|int
     */
    public function getTimeout($account)
    {
        return self::SERVER_TIMEOUT_DEFAULT;
    }

    /**
     * If the exporter scheduler is enabled, the delta export time has to be validated
     * 1. the delta can only be triggered between configured start-end hours
     * 2. 2 subsequent deltas can only be run with the time difference configured
     * 3. the delta after a full export can only be run after the configured time
     *
     * @param $startExportDate
     * @return bool
     */
    public function exportDeniedOnAccount($account)
    {
        if(!$this->config->isExportSchedulerEnabled($account))
        {
            return false;
        }

        $startHour = $this->config->getExportSchedulerDeltaStart($account);
        $endHour = $this->config->getExportSchedulerDeltaEnd($account);
        $runDateStoreHour = $this->getCurrentStoreTime('H');;
        if($runDateStoreHour === min(max($runDateStoreHour, $startHour), $endHour))
        {
            $latestDeltaRunDate = $this->getLatestUpdatedAt($this->getIndexerId());
            $deltaTimeRange = $this->config->getExportSchedulerDeltaMinInterval($account);
            if($latestDeltaRunDate == min($latestDeltaRunDate, date("Y-m-d H:i:s", strtotime("-$deltaTimeRange min"))))
            {
                return false;
            }

            return true;
        }

        return true;
    }

    /**
     * Check latest run on delta
     *
     * @return false|string|null
     */
    public function getLatestRun()
    {
        if(is_null($this->latestRun))
        {
            $this->latestRun = $this->getLatestUpdatedAt($this->getExporterId());
        }

        if(empty($this->latestRun) || strtotime($this->latestRun) < 0)
        {
            $this->latestRun = date("Y-m-d H:i:s", strtotime("-1 hour"));
        }

        return $this->latestRun;
    }

    /**
     * @return array
     */
    public function getIds()
    {
        $lastUpdateDate = $this->getLatestRun();
        $directProductUpdates = $this->processResource->getProductIdsByUpdatedAt($lastUpdateDate);
        if(empty($directProductUpdates))
        {
            return [];
        }

        return $directProductUpdates;
    }

    /**
     * @return bool
     */
    public function getExportFull()
    {
        return false;
    }

}