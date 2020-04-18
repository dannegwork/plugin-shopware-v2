<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Test\Api;

use Boxalino\IntelligenceFramework\Service\Api\ResponseInterface;
use Boxalino\IntelligenceFramework\Service\Api\Util\Configuration;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use JsonSerializable;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\ErrorHandler\Error\UndefinedFunctionError;
use Symfony\Component\ErrorHandler\Error\UndefinedMethodError;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @package Boxalino\IntelligenceFramework\Service\Api
 */
class ResponseService
{
    const BOXALINO_RESPONSE_POSITION = ["left", "right", "main", "top", "bottom"];
    const BOXALINO_RESPONSE_ELEMENTS = ["blocks", "performance"];

    /**
     * @var
     */
    protected $json;
    protected $response;
    protected $data = null;

    public function __call(string $method, array $params = [])
    {
        preg_match('/^(get)(.*?)$/i', $method, $matches);
        $prefix = $matches[1] ?? '';
        $element = $matches[2] ?? '';
        $element = strtolower($element);
        if ($prefix == 'get') {

            if (in_array($element, array_merge(self::BOXALINO_RESPONSE_POSITION, self::BOXALINO_RESPONSE_ELEMENTS)))
            {
                try {
                    return $this->get()->$element;
                } catch (\Exception $error)
                {
                    return [];
                }
            }

            throw new UndefinedMethodError("BoxalinoAPI: the requested method $method is not supported by the Boxalino API ResponseServer");
        }
    }


    public function getHitCount() : int
    {
        return $this->get()->system->mainHitCount ?? $this->get()->system->acSuggestionHitcount;
    }

    public function getDebugInformation()
    {
        $index = 0;
        return $this->get()->advanced->$index;
    }

    public function get()
    {
        if(is_null($this->data))
        {
            $this->data = json_decode($this->json);
        }

        return $this->data;
    }

    public function set(Response $response)
    {
        $this->response = $response;
        $this->setJson($response->getBody()->getContents());

        return $this;
    }

    public function setJson(string $json)
    {
        $this->json = $json;
        return $this;
    }

    public function getJson() : string
    {
        return $this->json;
    }

}
