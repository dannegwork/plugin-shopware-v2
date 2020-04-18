<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Request;

/**
 * Interface ParameterInterface
 *
 * @package Boxalino\IntelligenceFramework\Service\Api\Request
 */
interface ParameterInterface
{
    /**
     * @return array
     */
    public function toArray() : array;
}
