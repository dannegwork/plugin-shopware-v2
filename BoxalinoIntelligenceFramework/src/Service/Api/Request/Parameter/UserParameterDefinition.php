<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Request\Parameter;

use Boxalino\IntelligenceFramework\Service\Api\Request\ParameterDefinition;

/**
 * Class UserParameterDefinition
 *
 * Required parameters for every request:
 * User-Host, User-Referer, User-Url, User-Agent
 *
 * Any additional parameters can be added
 * @package Boxalino\IntelligenceFramework\Service\Api\Request
 */
class UserParameterDefinition extends ParameterDefinition
{

    /**
     * @param string $property
     * @param array $values
     * @return $this
     */
    public function add(string $property, ?array $values)
    {
        $this->{$property} = $values;
        return $this;
    }
}
