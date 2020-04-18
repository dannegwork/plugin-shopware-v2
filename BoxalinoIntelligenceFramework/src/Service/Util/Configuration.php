<?php
namespace Boxalino\IntelligenceFramework\Service\Util;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Psr\Log\LoggerInterface;

/**
 * Class Configuration
 * General Boxalino configuration accessor
 *
 * @package Boxalino\IntelligenceFramework\Service\Util
 */
class Configuration
{
    CONST BOXALINO_FRAMEWORK_CONFIG_KEY = "BoxalinoIntelligenceFramework";

    /**
     * @var SystemConfigService
     */
    protected $systemConfigService;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @param SystemConfigService $systemConfigService
     * @param \Psr\Log\LoggerInterface $boxalinoLogger
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        LoggerInterface $boxalinoLogger
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->logger = $boxalinoLogger;
    }

    public function getPluginConfigByChannelId($id)
    {
        if(empty($this->config) || !isset($this->config[$id]))
        {
            $allConfig = $this->systemConfigService->all($id);
            $this->config[$id] = $allConfig[self::BOXALINO_FRAMEWORK_CONFIG_KEY]['config'];
        }

        return $this->config[$id];
    }

}
