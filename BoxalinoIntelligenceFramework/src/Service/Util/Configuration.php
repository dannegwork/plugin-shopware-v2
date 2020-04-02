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
        $allConfig = $this->systemConfigService->all($id);
        return $allConfig[self::BOXALINO_FRAMEWORK_CONFIG_KEY]['config'];
    }

}
