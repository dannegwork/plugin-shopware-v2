<?php
namespace Boxalino\Subscriber;

use Boxalino\Components\Exporter\DataExporter;
use Enlight\Event\SubscriberInterface;
use Shopware_Components_Cron_CronJob;

class CronSubscriber  extends AbstractSubscriber
    implements  SubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_CronJob_BoxalinoExportCron' => 'onBoxalinoExportCronJob',
            'Shopware_CronJob_BoxalinoExportCronDelta' => 'onBoxalinoExportCronJobDelta'
        ];
    }

    public function onBoxalinoExportCronJob(Shopware_Components_Cron_CronJob $job) {
        return $this->runBoxalinoExportCronJob();
    }

    public function onBoxalinoExportCronJobDelta(Shopware_Components_Cron_CronJob $job) {
        return $this->runBoxalinoExportCronJob(true);
    }

    private function runBoxalinoExportCronJob($delta = false) {

        if($delta && !$this->canRunDelta()) {
            Shopware()->PluginLogger()->info("BxLog: Delta Export Cron is not allowed to run yet.");
            return true;
        }
        $tmpPath = Shopware()->DocPath('media_temp_boxalinoexport');
        $exporter = new DataExporter($tmpPath, $delta);
        $exporter->run();
        if(!$delta) {
            $this->updateCronExport();
        }
        return true;
    }

    private function canRunDelta() {
        $db = Shopware()->Db();
        $sql = $db->select()
            ->from('cron_exports', array('export_date'))
            ->limit(1);
        $stmt = $db->query($sql);
        if ($stmt->rowCount()) {
            $row = $stmt->fetch();
            $dbdate = strtotime($row['export_date']);
            $wait_time = Shopware()->Config()->get('boxalino_export_cron_schedule');
            if(time() - $dbdate < ($wait_time * 60)){
                return false;
            }
        }
        return true;
    }

    private function updateCronExport() {
        Shopware()->Db()->query('TRUNCATE `cron_exports`');
        Shopware()->Db()->query('INSERT INTO `cron_exports` values(NOW())');
    }

}