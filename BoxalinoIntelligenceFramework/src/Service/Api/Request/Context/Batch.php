<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Request\Context;

use GuzzleHttp\Client;
use JsonSerializable;
use Psr\Http\Message\RequestInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * Batch Request
 *
 * The batch request is used to access customized real-time customer content recommendations
 * It requires return fields, filters&sort for the content (if any) and parameters (ex: campaign, etc)
 * These requests are using a different endpoint
 *
 * @package Boxalino\IntelligenceFramework\Service\Api
 */
class Batch
{

    public function getEndpoint()
    {
        return "https://track.bx-cloud.com/narrative/{$this->getUsername()}/api/1";
    }
}
