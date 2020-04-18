<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Request;

use Boxalino\IntelligenceFramework\Service\Api\Request\Parameter\FacetDefinition;
use Boxalino\IntelligenceFramework\Service\Api\Request\Parameter\FilterDefinition;
use Boxalino\IntelligenceFramework\Service\Api\Request\Parameter\HeaderParameterDefinition;
use Boxalino\IntelligenceFramework\Service\Api\Request\Parameter\UserParameterDefinition;
use Boxalino\IntelligenceFramework\Service\Api\Request\Parameter\ItemDefinition;
use Boxalino\IntelligenceFramework\Service\Api\Request\Parameter\SortingDefinition;

/**
 * Boxalino API Request definition object
 *
 * @package Boxalino\IntelligenceFramework\Service\Api
 */
class RequestDefinition implements RequestDefinitionInterface
{
    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */
    protected $apiSecret;

    /**
     * @var bool
     */
    protected $dev;

    /**
     * @var bool
     */
    protected $test;

    /**
     * @var string
     */
    protected $language;

    /**
     * @var string
     */
    protected $sessionId;

    /**
     * @var string
     */
    protected $profileId;

    /**
     * @var string
     */
    protected $customerId;

    /**
     * @var string
     */
    protected $widget;

    /**
     * @var int
     */
    protected $hitCount;

    /**
     * @var int
     */
    protected $offset;

    /**
     * @var string
     */
    protected $groupBy;

    /**
     * @var string
     */
    protected $query = "";

    /**
     * @var array
     */
    protected $returnFields = [];

    /**
     * @var array
     */
    protected $sort = [];

    /**
     * @var array
     */
    protected $filters = [];

    /**
     * @var bool
     */
    protected $orFilters = false;

    /**
     * @var array
     */
    protected $facets = [];

    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * @param FilterDefinition ...$filterDefinitions
     * @return $this
     */
    public function addFilters(FilterDefinition ...$filterDefinitions) : self
    {
        foreach ($filterDefinitions as $filter) {
            $this->filters[] = $filter->toArray();
        }

        return $this;
    }

    /**
     * @param SortingDefinition ...$sortingDefinitions
     * @return $this
     */
    public function addSort(SortingDefinition ...$sortingDefinitions) : self
    {
        foreach ($sortingDefinitions as $sort) {
            $this->sort[] = $sort->toArray();
        }

        return $this;
    }

    /**
     * @param FacetDefinition ...$facetDefinitions
     * @return $this
     */
    public function addFacets(FacetDefinition ...$facetDefinitions) : self
    {
        foreach ($facetDefinitions as $facet) {
            $this->facets[] = $facet->toArray();
        }

        return $this;
    }

    /**
     * @param HeaderParameterDefinition ...$headerParameterDefinitions
     * @return $this
     */
    public function addHeaderParameters(HeaderParameterDefinition ...$headerParameterDefinitions)
    {
        foreach ($headerParameterDefinitions as $parameter) {
            $this->parameters = array_merge($this->parameters, $parameter->toArray());
        }

        return $this;
    }

    /**
     * @param UserParameterDefinition ...$userParameterDefinitions
     * @return $this
     */
    public function addParameters(UserParameterDefinition ...$userParameterDefinitions)
    {
        foreach ($userParameterDefinitions as $parameter) {
            $this->parameters = array_merge($this->parameters, $parameter->toArray());
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getApiSecret(): string
    {
        return $this->apiSecret;
    }

    /**
     * @param string $apiSecret
     * @return RequestDefinition
     */
    public function setApiSecret(string $apiSecret) : self
    {
        $this->apiSecret = $apiSecret;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDev(): bool
    {
        return $this->dev;
    }

    /**
     * @param bool $dev
     * @return RequestDefinition
     */
    public function setDev(bool $dev) : self
    {
        $this->dev = $dev;
        return $this;
    }

    /**
     * @return bool
     */
    public function isTest(): bool
    {
        return $this->test;
    }

    /**
     * @param bool $test
     * @return RequestDefinition
     */
    public function setTest(bool $test) : self
    {
        $this->test = $test;
        return $this;
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @param string $language
     * @return RequestDefinition
     */
    public function setLanguage(string $language) : self
    {
        $this->language = $language;
        return $this;
    }

    /**
     * @return string
     */
    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    /**
     * @param string $sessionId
     * @return RequestDefinition
     */
    public function setSessionId(string $sessionId) : self
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    /**
     * @return string
     */
    public function getProfileId(): string
    {
        return $this->profileId;
    }

    /**
     * @param string $profileId
     * @return RequestDefinition
     */
    public function setProfileId(string $profileId) : self
    {
        $this->profileId = $profileId;
        return $this;
    }

    /**
     * @return string
     */
    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    /**
     * @param string $customerId
     * @return RequestDefinition
     */
    public function setCustomerId(string $customerId) : self
    {
        $this->customerId = $customerId;
        return $this;
    }

    /**
     * @return string
     */
    public function getWidget(): string
    {
        return $this->widget;
    }

    /**
     * @param string $widget
     * @return RequestDefinition
     */
    public function setWidget(string $widget) : self
    {
        $this->widget = $widget;
        return $this;
    }

    /**
     * @return int
     */
    public function getHitCount(): int
    {
        return $this->hitCount;
    }

    /**
     * @param int $hitCount
     * @return RequestDefinition
     */
    public function setHitCount(int $hitCount) : self
    {
        $this->hitCount = $hitCount;
        return $this;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     * @return RequestDefinition
     */
    public function setOffset(int $offset) : self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * @return string
     */
    public function getGroupBy(): string
    {
        return $this->groupBy;
    }

    /**
     * @param string $groupBy
     * @return RequestDefinition
     */
    public function setGroupBy(string $groupBy) : self
    {
        $this->groupBy = $groupBy;
        return $this;
    }

    /**
     * @return string
     */
    public function getQuery() : string
    {
        return $this->query;
    }

    /**
     * @param string $query
     * @return $this
     */
    public function setQuery(string $query) : self
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @return array
     */
    public function getReturnFields(): array
    {
        return $this->returnFields;
    }

    /**
     * @param array $returnFields
     * @return RequestDefinition
     */
    public function setReturnFields(array $returnFields) : self
    {
        $this->returnFields = $returnFields;
        return $this;
    }

    /**
     * @return array
     */
    public function getSort(): array
    {
        return $this->sort;
    }


    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @return bool
     */
    public function isOrFilters(): bool
    {
        return $this->orFilters;
    }

    /**
     * @param bool $orFilters
     * @return RequestDefinition
     */
    public function setOrFilters(bool $orFilters) : self
    {
        $this->orFilters = $orFilters;
        return $this;
    }

    /**
     * @return array
     */
    public function getFacets(): array
    {
        return $this->facets;
    }


    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param array $parameters
     * @return RequestDefinition
     */
    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * @return string
     */
    public function getUsername() : string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return RequestDefinition
     */
    public function setUsername(string $username) : self
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return string
     */
    public function getApiKey() : string
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     * @return RequestDefinition
     */
    public function setApiKey(string $apiKey) : self
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * @return array
     */
    public function toArray() : array
    {
        return get_object_vars($this);
    }

    /**
     * @return false|mixed|string
     */
    public function jsonSerialize()
    {
        return json_encode($this->toArray());
    }

}
