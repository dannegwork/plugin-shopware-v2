<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Util;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ConfigJsonField;

class Configuration
{

    CONST BOXALINO_FRAMEWORK_CONFIG_KEY = "BoxalinoIntelligenceFramework";

    protected $exporterConfigurationFields = [
        "status",
        "account",
        "password",
        "index",
        "export",
        "exportPublishConfig",
        "exportProductInclude",
        "exportProductExclude",
        "exportProductImages",
        "exportProductUrl",
        "exportCustomerEnable",
        "exportCustomerInclude",
        "exportCustomerExclude",
        "exportTransactionEnable",
        "exportTransactionMode",
        "exportTransactionInclude",
        "exportTransactionExclude",
        "exportVoucherEnable",
        "exportCronSchedule"
    ];

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var SystemConfigService
     */
    protected $systemConfigService;

    /**
     * @var array
     */
    protected $indexConfig = array();

    /**
     * @var LoggerInterface
     */
    protected $logger;


    /**
     * @param Connection $connection
     * @param SystemConfigService $systemConfigService
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        Connection $connection,
        SystemConfigService $systemConfigService,
        LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
        $this->init();
    }

    protected function init()
    {
        foreach($this->getShops() as $shopData)
        {
            $pluginConfig = $this->getPluginConfigByChannelId($shopData['sales_channel_id']);
            $config = $this->validateChannelConfig($pluginConfig, $shopData['sales_channel_name']);
            if(!isset($this->indexConfig[$config['account']]))
            {
                $this->indexConfig[$config['account']] = array_merge($shopData, $config);
            }
        }
    }

    public function getPluginConfigByChannelId($id)
    {
        $allConfig = $this->systemConfigService->all($id);
        $this->logger->debug(json_encode($allConfig[self::BOXALINO_FRAMEWORK_CONFIG_KEY]['config']));
        return $allConfig[self::BOXALINO_FRAMEWORK_CONFIG_KEY]['config'];
    }

    /**
     * @param $config
     * @param $channel
     * @return array
     */
    public function validateChannelConfig($config, $channel)
    {
        if(!(bool)$config['status'])
        {
            return [];
        }

        if (empty($config['account']) || empty($config['password']))
        {
            $this->logger->info("BoxalinoIntelligenceFramework:: Account not found on channel $channel; Plugin Configurations skipped.");
            return [];
        }

        if (!(bool)$config['export'])
        {
            $this->logger->info("BoxalinoIntelligenceFramework:: Exporter disabled on channel $channel; Plugin Configurations skipped.");
            return [];
        }

        foreach($this->exporterConfigurationFields as $field)
        {
            if(!isset($config[$field])) {$config[$field] = "";}
        }

        return $config;
    }

    /**
     * Getting shop details: id, languages, root category
     * @return array
     * @throws \Shopware\Core\Framework\Uuid\Exception\InvalidUuidException
     */
    protected function getShops() : array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select([
            'LOWER(HEX(sales_channel.id)) as sales_channel_id',
            'LOWER(HEX(sales_channel.language_id)) AS sales_channel_default_language_id',
            'LOWER(HEX(sales_channel.currency_id)) AS sales_channel_default_currency_id',
            'LOWER(HEX(sales_channel.customer_group_id)) as sales_channel_customer_group_id',
            'ANY_VALUE(channel.name) as sales_channel_name',
            "GROUP_CONCAT(SUBSTR(locale.code, 1, 2) SEPARATOR ',') as sales_channel_languages_locale",
            "GROUP_CONCAT(LOWER(HEX(language.id)) SEPARATOR ',') as sales_channel_languages_id",
            'LOWER(HEX(sales_channel.navigation_category_id)) as sales_channel_navigation_category_id',
            'LOWER(HEX(sales_channel.navigation_category_version_id)) as sales_channel_navigation_category_version_id'
        ])
            ->from('sales_channel')
            ->leftJoin(
                'sales_channel',
                'sales_channel_language',
                'sales_channel_language',
                'sales_channel.id = sales_channel_language.sales_channel_id'
            )
            ->leftJoin(
                'sales_channel',
                'sales_channel_translation',
                'channel',
                'sales_channel.id = channel.sales_channel_id'
            )
            ->leftJoin(
                'sales_channel_language',
                'language',
                'language',
                'sales_channel_language.language_id = language.id'
            )
            ->leftJoin(
                'language',
                'locale',
                'locale',
                'language.locale_id = locale.id'
            )
            ->addGroupBy('sales_channel.id')
            ->andWhere('sales_channel.active = 1')
            ->andWhere('sales_channel.type_id = :type')
            ->setParameter('type', Uuid::fromHexToBytes(Defaults::SALES_CHANNEL_TYPE_STOREFRONT), ParameterType::BINARY);

        return $query->execute()->fetchAll();
    }

    /**
     * @param string $account
     * @throws \Exception
     */
    public function getChannelDefaultLanguageId(string $account)
    {
        $config = $this->getAccountConfig($account);
        return $config['sales_channel_default_language_id'];
    }

    /**
     * @return array
     */
    public function getAccounts() : array
    {
        return array_keys($this->indexConfig);
    }

    /**
     * @param $account
     * @return mixed
     * @throws \Exception
     */
    public function getAccountConfig(string $account) : array
    {
        if(isset($this->indexConfig[$account]))
        {
            return $this->indexConfig[$account];
        }

        throw new \Exception("Account is not defined: " . $account);
    }

    /**
     * @param $account
     * @return mixed
     * @throws \Exception
     */
    public function getCustomerGroupId(string $account)  : string
    {
        $config = $this->getAccountConfig($account);
        return $config['sales_channel_customer_group_id'];
    }

    /**
     * @param $account
     * @return mixed
     * @throws \Exception
     */
    public function getChannelRootCategoryId(string $account) : string
    {
        $config = $this->getAccountConfig($account);
        return $config['sales_channel_navigation_category_id'];
    }

    /**
     * @param $account
     * @return bool
     * @throws \Exception
     */
    public function isCustomersExportEnabled(string $account) : bool
    {
        $config = $this->getAccountConfig($account);
        return (bool)$config['exportCustomerEnable'];
    }

    /**
     * @param $account
     * @return bool
     * @throws \Exception
     */
    public function isTransactionsExportEnabled(string $account) : bool
    {
        $config = $this->getAccountConfig($account);
        return (bool) $config['exportTransactionEnable'];
    }

    /**
     * @param $account
     * @return bool
     * @throws \Exception
     */
    public function isVoucherExportEnabled(string $account) : bool
    {
        $config = $this->getAccountConfig($account);
        return (bool) $config['exportVoucherEnable'];
    }

    /**
     * @param $account
     * @return string
     * @throws \Exception
     */
    public function getExportTransactionIncremental(string $account) : string
    {
        $config = $this->getAccountConfig($account);
        if($config['exportTransactionMode'])
        {
            return true;
        }

        return false;
    }

    /**
     * Getting additional tables for each entity to be exported (products, customers, transactions)
     *
     * @param string $account
     * @param string $type
     * @return array
     * @throws \Exception
     */
    public function getAccountExtraTablesByComponent(string $account, string $type) : array
    {
        $config = $this->getAccountConfig($account);
        $additionalTablesList = $config["{$type}ExtraTable"];
        if($additionalTablesList)
        {
            return explode(',', $additionalTablesList);
        }

        return [];
    }

    /**
     * @param $account
     * @return mixed
     * @throws \Exception
     */
    public function getAccountPassword(string $account) : string
    {
        $config = $this->getAccountConfig($account);
        $password = $config['password'];
        if(empty($password) || is_null($password)) {
            throw new \Exception("Please provide a password for your boxalino account in the configuration");
        }

        return $password;
    }

    /**
     * @param $account
     * @return mixed
     * @throws \Exception
     */
    public function useDevIndex(string $account) : bool
    {
        $config = $this->getAccountConfig($account);
        return (bool) $config['index'];
    }

    /**
     * @param $account
     * @return mixed
     * @throws \Exception
     */
    public function getAccountChannelId(string $account) : string
    {
        $config = $this->getAccountConfig($account);
        return $config['sales_channel_id'];
    }

    /**
     * @param $account
     * @return []
     * @throws \Exception
     */
    public function getAccountLanguages(string $account) : array
    {
        $config = $this->getAccountConfig($account);
        $languages = explode(",", $config['sales_channel_languages_locale']);
        $languageIds = explode(",", $config['sales_channel_languages_id']);
        return array_combine($languageIds, $languages);
    }

    /**
     * @param $account
     * @return bool
     * @throws \Exception
     */
    public function exportProductImages(string $account) : bool
    {
        $config = $this->getAccountConfig($account);
        return (bool) $config['exportProductImages'];
    }

    /**
     * @param $account
     * @return string
     */
    public function getExportServer() : string
    {
        return 'http://di1.bx-cloud.com';
    }

    /**
     * @param $account
     * @return bool
     * @throws \Exception
     */
    public function exportProductUrl(string $account) : bool
    {
        $config = $this->getAccountConfig($account);
        return (bool)$config['exportProductUrl'];
    }

    /**
     * @param $account
     * @return bool
     * @throws \Exception
     */
    public function publishConfigurationChanges(string $account) : bool
    {
        $config = $this->getAccountConfig($account);
        return (bool) $config['exportPublishConfig'];
    }

    /**
     * @TODO add new param
     * @param string $account
     * @return int
     */
    public function getExporterTimeout(string $account) : int
    {
        return 300;
    }

    /**
     * Time interval after a full data export that a delta is allowed to run
     * It is set in order to avoid overlapping index updates
     *
     * @param string $account
     * @return int
     * @throws \Exception
     */
    public function getDeltaScheduleTime(string $account) : int
    {
        $config = $this->getAccountConfig($account);
        if(isset($config['exportCronSchedule']) && !empty($config['exportCronSchedule']))
        {
            return $config['exportCronSchedule'];
        }

        return 60;
    }

    /**
     * Minimum time interval between 2 deltas to allow a run (minutes)
     *
     * @param string $account
     * @return int
     */
    public function getDeltaFrequencyMinInterval(string $account) : int
    {
        return 30;
    }

    /**
     * @TODO add new param
     * @param string $account
     * @return int
     */
    public function getExporterTemporaryArchivePath(string $account) : int
    {
        return null;
    }

    /**
     * @param $account
     * @param $allProperties
     * @param array $requiredProperties
     * @return array
     * @throws \Exception
     */
    public function getAccountProductsProperties(string $account, array $allProperties, array $requiredProperties=[]) : array
    {
        $config = $this->getAccountConfig($account);
        $includes = explode(',', $config['exportProductInclude']);
        $excludes = explode(',', $config['exportProductExclude']);

        return $this->getFinalProperties($allProperties, $includes, $excludes, $requiredProperties);
    }

    /**
     * @param string $account
     * @param array $allProperties
     * @param array $requiredProperties
     * @param array $excludedProperties
     * @return array
     * @throws \Exception
     */
    public function getAccountCustomersProperties(string $account, array $allProperties, array $requiredProperties=[], array $excludedProperties=[]) : array
    {
        $config = $this->getAccountConfig($account);
        $includes = array_filter(explode(',', $config['exportCustomerInclude']));
        $excludes = array_merge($excludedProperties, explode(',', $config['exportCustomerExclude']));

        return $this->getFinalProperties($allProperties, $includes, $excludes, $requiredProperties);
    }

    /**
     * @param $account
     * @param $allProperties
     * @param array $requiredProperties
     * @return array
     * @throws \Exception
     */
    public function getAccountTransactionsProperties(string $account, array $allProperties, array $requiredProperties=[]) : array
    {
        $config = $this->getAccountConfig($account);
        $includes = array_filter(explode(',', $config['exportTransactionInclude']));
        $excludes = array_filter(explode(',', $config['exportTransactionExclude']));

        return $this->getFinalProperties($allProperties, $includes, $excludes, $requiredProperties);
    }

    /**
     * @param $allProperties
     * @param $includes
     * @param $excludes
     * @param array $requiredProperties
     * @return array
     * @throws \Exception
     */
    protected function getFinalProperties($allProperties, $includes, $excludes, $requiredProperties=[]) : array
    {
        if(sizeof($includes) > 0) {
            foreach($includes as $incl) {
                if(empty($incl)) { continue; }
                if(!in_array($incl, $allProperties)) {
                    throw new \Exception("BoxalinoIntelligenceFramework: Exporter Configuration: Requested include property $incl which is not part of all the properties provided");
                }

                if(!in_array($incl, $requiredProperties)) {
                    $requiredProperties[] = $incl;
                }
            }
            return $requiredProperties;
        }

        foreach($excludes as $excl) {
            if(!in_array($excl, $allProperties)) {
                $this->logger->info("BoxalinoIntelligenceFramework: Exporter Configuration: Requested exclude property $excl which is not part of all the properties provided");
            }
            if(in_array($excl, $requiredProperties)) {
                $this->logger->info("BoxalinoIntelligenceFramework: Exporter Configuration: Requested exclude property $excl which is part of the required properties and therefore cannot be excluded");
            }
        }

        $finalProperties = array();
        foreach($allProperties as $i => $p) {
            if(!in_array($p, $excludes)) {
                $finalProperties[$i] = $p;
            }
        }

        return $finalProperties;
    }

}
