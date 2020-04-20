<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Request;

use Boxalino\IntelligenceFramework\Service\Api\Request\Parameter\FacetDefinition;
use Boxalino\IntelligenceFramework\Service\Api\Request\Parameter\FilterDefinition;
use Boxalino\IntelligenceFramework\Service\Api\Request\Parameter\HeaderParameterDefinition;
use Boxalino\IntelligenceFramework\Service\Api\Request\Parameter\UserParameterDefinition;
use Boxalino\IntelligenceFramework\Service\Api\Request\Parameter\SortingDefinition;
use JsonSerializable;

/**
 * Boxalino API Request definition interface
 *
 * @package Boxalino\IntelligenceFramework\Service\Api\Request
 */
interface RequestDefinitionInterface extends \JsonSerializable
{

    /**
     * @param FilterDefinition ...$filterDefinitions
     * @return $this
     */
    public function addFilters(FilterDefinition ...$filterDefinitions) : RequestDefinition ;

    /**
     * @param SortingDefinition ...$sortingDefinitions
     * @return $this
     */
    public function addSort(SortingDefinition ...$sortingDefinitions) : RequestDefinition ;

    /**
     * @param FacetDefinition ...$facetDefinitions
     * @return $this
     */
    public function addFacets(FacetDefinition ...$facetDefinitions) : RequestDefinition;

    /**
     * @param HeaderParameterDefinition ...$headerParameterDefinitions
     * @return $this
     */
    public function addHeaderParameters(HeaderParameterDefinition ...$headerParameterDefinitions);

    /**
     * @param UserParameterDefinition ...$userParameterDefinitions
     * @return $this
     */
    public function addParameters(UserParameterDefinition ...$userParameterDefinitions);

    /**
     * @param string $apiSecret
     * @return RequestDefinition
     */
    public function setApiSecret(string $apiSecret) : RequestDefinition;

    /**
     * @param bool $dev
     * @return RequestDefinition
     */
    public function setDev(bool $dev) : RequestDefinition;

    /**
     * @param bool $test
     * @return RequestDefinition
     */
    public function setTest(bool $test) : RequestDefinition;

    /**
     * @param string $language
     * @return RequestDefinition
     */
    public function setLanguage(string $language) : RequestDefinition;

    /**
     * @param string $sessionId
     * @return RequestDefinition
     */
    public function setSessionId(string $sessionId) : RequestDefinition;

    /**
     * @param string $profileId
     * @return RequestDefinition
     */
    public function setProfileId(string $profileId) : RequestDefinition;

    /**
     * @return string
     */
    public function getProfileId() : string;

    /**
     * @param string $customerId
     * @return RequestDefinition
     */
    public function setCustomerId(string $customerId) : RequestDefinition;

    /**
     * @param string $widget
     * @return RequestDefinition
     */
    public function setWidget(string $widget) : RequestDefinition;

    /**
     * @param int $hitCount
     * @return RequestDefinition
     */
    public function setHitCount(int $hitCount) : RequestDefinition;

    /**
     * @param int $offset
     * @return RequestDefinition
     */
    public function setOffset(int $offset) : RequestDefinition;

    /**
     * @param string $groupBy
     * @return RequestDefinition
     */
    public function setGroupBy(string $groupBy) : RequestDefinition;

    /**
     * @param string $query
     * @return $this
     */
    public function setQuery(string $query) : RequestDefinition;

    /**
     * @param array $returnFields
     * @return RequestDefinition
     */
    public function setReturnFields(array $returnFields) : RequestDefinition;

    /**
     * @param bool $orFilters
     * @return RequestDefinition
     */
    public function setOrFilters(bool $orFilters) : RequestDefinition;

    /**
     * @param array $parameters
     * @return RequestDefinition
     */
    public function setParameters(array $parameters): RequestDefinition;

    /**
     * @param string $username
     * @return RequestDefinition
     */
    public function setUsername(string $username) : RequestDefinition;

    /**
     * @param string $apiKey
     * @return RequestDefinition
     */
    public function setApiKey(string $apiKey) : RequestDefinition;

    /**
     * @return array
     */
    public function toArray() : array;

}
