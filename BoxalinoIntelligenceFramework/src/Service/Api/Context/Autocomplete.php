<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Context;

use Boxalino\IntelligenceFramework\Service\Api\RequestFactory;
use GuzzleHttp\Client;
use JsonSerializable;
use Psr\Http\Message\RequestInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * The autocomplete request can have facets&filters set; predefined order, etc
 *
 * @package Boxalino\IntelligenceFramework\Service\Api
 */
class Autocomplete extends RequestFactory
{

}
