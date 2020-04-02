<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Request;

interface ParameterInterface
{
    public function toArray() : array;
}
