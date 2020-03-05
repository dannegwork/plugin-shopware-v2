<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Util;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ConfigJsonField;

class Configuration
{

    CONST BOXALINO_FRAMEWORK_CONFIG_KEY = "BoxalinoIntelligenceFramework.config.";

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
     * @var Psr\Log\LoggerInterface
     */
    protected $logger;


    /**
     * @param Connection $connection
     */
    public function __construct(
        Connection $connection,
        SystemConfigService $systemConfigService,
        \Psr\Log\LoggerInterface $logger
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
        return $allConfig[self::BOXALINO_FRAMEWORK_CONFIG_KEY]['config'];
    }

    public function validateChannelConfig($config, $channel)
    {
        if($config['status']!=1 || !(bool)$config['status'])
        {
            return [];
        }

        if (empty($config['account'] || $config['password']))
        {
            $this->logger->info("BoxalinoIntelligenceFramework:: Account not found on channel $channel; Plugin Configurations skipped.");
            return [];
        }

        if (!(bool)$config['exporter'] || $config['exporter'] != 1)
        {
            $this->logger->info("BoxalinoIntelligenceFramework:: Exporter disabled on channel $channel; Plugin Configurations skipped.");
            return [];
        }

        return array_combine($this->exporterConfigurationFields, $config);
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
            'LOWER(sales_channel.id) as sales_channel_id',
            'sales_channel.customer_group_id as sales_channel_customer_group_id',
            'channel.name as sales_channel_name',
            "GROUP_CONCAT(SUBSTR(locale.code, 0, 2) SEPARATOR ',') as sales_channel_languages_locale",
            "GROUP_CONCAT(language.id SEPARATOR ',') as sales_channel_languages_id",
            'sales_channel.navigation_category_id as sales_channel_navigation_category_id',
            'sales_channel.navigation_category_version_id as sales_channel_navigation_category_version_id'
        ])
            ->from('sales_channel')
            ->leftJoin(
                'sales_channel',
                'sales_channel_language',
                'mapping',
                'sales_channel.id = mapping.sales_channel_id'
            )
            ->leftJoin(
                'sales_channel',
                'sales_channel_translation',
                'channel',
                'sales_channel.id = channel.sales_channel_id'
            )
            ->leftJoin(
                'mapping',
                'language',
                'language',
                'mapping.language_id = language.id'
            )
            ->leftJoin(
                'language',
                'locale',
                'locale',
                'language.locale_id = locale.id'
            )
            ->addGroupBy('sales_channel.id')
            ->andWhere('active = 1')
            ->andWhere('type_id = :type')
            ->setParameter('type', Uuid::fromHexToBytes(Defaults::SALES_CHANNEL_TYPE_STOREFRONT));

        return $query->execute()->fetchAll();
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
    public function getTransactionMode(string $account) : string
    {
        $config = $this->getAccountConfig($account);
        return $config['exportTransactionMode'];
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
        return explode(",", $config['sales_channel_languages']);
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
        $includes = explode(',', $config['export_product_include']);
        $excludes = explode(',', $config['export_product_exclude']);

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
        $includes = explode(',', $config['export_customer_include']);
        $excludes = array_merge($excludedProperties, explode(',', $config['export_customer_exclude']));

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
        $includes = explode(',', $config['export_transaction_include']);
        $excludes = explode(',', $config['export_transaction_exclude']);

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
        foreach($includes as $k => $incl) {
            if($incl == "") {
                unset($includes[$k]);
            }
        }

        foreach($excludes as $k => $excl) {
            if($excl == "") {
                unset($excludes[$k]);
            }
        }

        if(sizeof($includes) > 0) {
            foreach($includes as $incl) {
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
                throw new \Exception("BoxalinoIntelligenceFramework: Exporter Configuration: Requested exclude property $excl which is not part of all the properties provided");
            }
            if(in_array($excl, $requiredProperties)) {
                throw new \Exception("BoxalinoIntelligenceFramework: Exporter Configuration: Requested exclude property $excl which is part of the required properties and therefore cannot be excluded");
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