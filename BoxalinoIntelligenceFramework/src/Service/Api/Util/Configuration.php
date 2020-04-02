<?php
namespace Boxalino\IntelligenceFramework\Service\Api\Util;

/**
 * Class Configuration
 * Configurations defined for the REST API requests
 *
 * @package Boxalino\IntelligenceFramework\Service\Api\Util
 */
class Configuration extends \Boxalino\IntelligenceFramework\Service\Util\Configuration
{

    /**
     * The API endpoint depends on the testing conditionals and on the data index
     * @return string
     */
    public function getRestApiEndpoint() : string
    {
        return "https://r-st.bx-cloud.com/narrative/dana_shopware_06/api/1";
    }

    /**
     * @return string
     */
    public function getUsername() : string
    {
        return "dana_shopware_06";
    }

    /**
     * @return string
     */
    public function getApiKey() : string
    {
        return "dana_shopware_06";
    }

    /**
     * @return string
     */
    public function getApiSecret() : string
    {
        return "dana_shopware_06";
    }

    /**
     * @return bool
     */
    public function getIsDev() : bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function getIsTest() : bool
    {
        return false;
    }

}
