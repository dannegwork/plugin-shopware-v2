<?php
namespace Boxalino\IntelligenceFramework\Service\Api\Util;

use Psr\Log\LoggerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * Class Configuration
 * Configurations defined for the REST API requests
 *
 * @package Boxalino\IntelligenceFramework\Service\Api\Util
 */
class Configuration extends \Boxalino\IntelligenceFramework\Service\Util\Configuration
{

    /**
     * @var null | string
     */
    protected $channelId = null;

    /**
     * @param string $channelId
     * @return $this
     */
    public function setChannelId(string $channelId) : self
    {
        $this->channelId = $channelId;
        $this->getPluginConfigByChannelId($channelId);

        return $this;
    }

    /**
     * @param string $method
     * @param array $params
     * @return mixed
     */
    public function __call(string $method, array $params = [])
    {
        preg_match('/^(get)(.*?)$/i', $method, $matches);
        $prefix = $matches[1] ?? '';
        if ($prefix == 'get')
        {
            if(isset($params[0]) && !isset($this->config[$params[0]]))
            {
                $this->getPluginConfigByChannelId($params[0]);
            }

            return $this->$method();
        }
    }


    /**
     * The API endpoint depends on the testing conditionals and on the data index
     * @param string $channelId
     * @return string
     */
    public function getRestApiEndpoint(string $channelId) : string
    {
        if(isset($this->config[$channelId]))
        {
            return $this->config[$channelId]['apiUrl'];
        }

        return "https://r-st.bx-cloud.com/narrative/dana_shopware_06/api/1";
    }

    /**
     * @param string $channelId
     * @return string
     */
    public function getUsername(string $channelId) : string
    {
        if(isset($this->config[$channelId]))
        {
            return $this->config[$channelId]['account'];
        }
    }

    /**
     * @param string $channelId
     * @return string
     */
    public function getApiKey(string $channelId) : string
    {
        if(isset($this->config[$channelId]))
        {
            return $this->config[$channelId]['apiKey'];
        }

        return "";
    }

    /**
     * @param string $channelId
     * @return string
     */
    public function getApiSecret(string $channelId) : string
    {
        if(isset($this->config[$channelId]))
        {
            return $this->config[$channelId]['apiSecret'];
        }

        return "";
    }

    /**
     * @param string $channelId
     * @return bool
     */
    public function getIsDev(string $channelId) : bool
    {
        if(isset($this->config[$channelId]))
        {
            return (bool)$this->config[$channelId]['devIndex'];
        }

        return false;
    }

    /**
     * @param string $channelId
     * @return bool
     */
    public function getIsTest(string $channelId) : bool
    {
        if(isset($this->config[$channelId]))
        {
            return (bool)$this->config[$channelId]['test'];
        }

        return false;
    }

}
