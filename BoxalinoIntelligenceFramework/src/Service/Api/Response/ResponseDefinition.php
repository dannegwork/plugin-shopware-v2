<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Api\Response;

use Boxalino\IntelligenceFramework\Service\Api\Content\BlocksDataProvider;
use Boxalino\IntelligenceFramework\Service\Api\Response\Accessor\AccessorInterface;
use Boxalino\IntelligenceFramework\Service\Api\Response\Accessor\Block;
use Boxalino\IntelligenceFramework\Service\Api\Util\AccessorHandler;
use Boxalino\IntelligenceFramework\Service\Api\Util\AccessorHandlerInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\ErrorHandler\Error\UndefinedFunctionError;
use Symfony\Component\ErrorHandler\Error\UndefinedMethodError;

/**
 * Class ResponseDefinition
 *
 * @package Boxalino\IntelligenceFramework\Service\Api\Response
 */
class ResponseDefinition implements ResponseDefinitionInterface
{

    use ResponseHydratorTrait;

    /**
     * If the facets are declared on a certain position, they are isolated in a specific block
     * All the other content is located under "blocks"
     */
    const BOXALINO_RESPONSE_POSITION = ["left", "right", "main", "top", "bottom"];

    /**
     * @var string
     */
    protected $json;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var null | \StdClass
     */
    protected $data = null;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var AccessorHandlerInterface
     */
    protected $accessorHandler = null;


    public function __construct(LoggerInterface $logger, AccessorHandlerInterface $accessorHandler)
    {
        $this->logger = $logger;
        $this->accessorHandler = $accessorHandler;
    }

    /**
     * Allows accessing other parameters
     *
     * @param string $method
     * @param array $params
     * @return array
     */
    public function __call(string $method, array $params = [])
    {
        preg_match('/^(get)(.*?)$/i', $method, $matches);
        $prefix = $matches[1] ?? '';
        $element = $matches[2] ?? '';
        $element = strtolower($element);
        if ($prefix == 'get') {

            if (in_array($element, self::BOXALINO_RESPONSE_POSITION))
            {
                try {
                    $content = [];
                    foreach($this->get()->$element as $block)
                    {
                        $content[] = $this->getBlockObject($block);
                    }
                    return $content;
                } catch (\Exception $error)
                {
                    return [];
                }
            }

            throw new UndefinedMethodError("BoxalinoAPI: the requested method $method is not supported by the Boxalino API ResponseServer");
        }
    }

    /**
     * @return int
     */
    public function getHitCount() : ?int
    {
        return $this->get()->system->mainHitCount ?? $this->get()->system->acSuggestionHitcount ?? null;
    }

    /**
     * @return string|null
     */
    public function getRedirectUrl() : ?string
    {
        $index = 0;
        return $this->get()->advanced->$index->redirect_url ?? null;
    }

    /**
     * @return string|null
     */
    public function getCorrectedSearchQuery() : ?string
    {
        return $this->get()->advanced->system->correctedSearchQuery ?? null;
    }

    public function getRequestId() : ?string
    {
        $index = 0;
        return $this->get()->advanced->$index->_bx_request_id ?? null;
    }

    /**
     * @return string
     */
    public function getGroupBy() : string
    {
        $index = 0;
        return $this->get()->advanced->$index->_bx_group_by;
    }

    /**
     * @return string
     */
    public function getVariantId() : string
    {
        $index = 0;
        return $this->get()->advanced->$index->_bx_variant_uuid;
    }

    /**
     * @return \ArrayIterator
     */
    public function getBlocks() : \ArrayIterator
    {
        $blocks = $this->get()->blocks;
        $content = new \ArrayIterator();
        foreach($blocks as $block)
        {
            $content->append($this->getBlockObject($block));
        }

        #$this->logger->info(var_dump($content));
        return $content;
    }

    /**
     * @param \StdClass $block
     * @return AccessorInterface
     */
    public function getBlockObject(\StdClass $block) : AccessorInterface
    {
        return $this->toObject($block, $this->getAccessorHandler()->getAccessor("blocks"));
    }

    /**
     * Debug and performance information
     *
     * @return array
     */
    public function getAdvanced() : array
    {
        $index=0;
        return array_merge($this->get()->performance, $this->get()->advanced->$index);
    }

    /**
     * @return \StdClass|null
     */
    public function get() : ?\StdClass
    {
        if(is_null($this->data))
        {
            $this->data = json_decode($this->json);
        }

        return $this->data;
    }

    /**
     * @param Response $response
     * @return $this
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
        $this->setJson($response->getBody()->getContents());

        return $this;
    }

    /**
     * @return Response
     */
    public function getResponse() : Response
    {
        return $this->response;
    }

    /**
     * @param string $json
     * @return $this
     */
    public function setJson(string $json)
    {
        $this->json = $json;
        return $this;
    }

    /**
     * @return string
     */
    public function getJson() : string
    {
        return $this->json;
    }

    public function getAccessorHandler(): AccessorHandlerInterface
    {
        return $this->accessorHandler;
    }

}
