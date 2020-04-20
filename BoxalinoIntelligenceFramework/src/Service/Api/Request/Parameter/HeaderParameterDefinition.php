<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Request\Parameter;

use Boxalino\IntelligenceFramework\Service\Api\Request\ParameterDefinition;

/**
 * Class HeaderParameterDefinition
 *
 * Required parameters for every request:
 * User-Host, User-Referer, User-Url, User-Agent
 *
 * Any additional parameters can be added
 * @package Boxalino\IntelligenceFramework\Service\Api\Request
 */
class HeaderParameterDefinition extends ParameterDefinition
{

    /**
     * @param string $property
     * @param string $value
     * @return $this
     */
    public function add(string $property, ?string $value)
    {
        $this->{$property} = $value;
        return $this;
    }

}
