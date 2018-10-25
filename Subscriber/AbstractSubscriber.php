<?php
namespace Boxalino\Subscriber;

use Boxalino\Components\Interceptor\FrontendInterceptor;
use Boxalino\Components\Interceptor\SearchInterceptor;

class AbstractSubscriber
{

    /**
     * @var \Boxalino\Components\Interceptor\SearchInterceptor
     */
    protected  $searchInterceptor;

    /**
     * @var \Boxalino\Components\Interceptor\FrontendInterceptor
     */
    protected $frontendInterceptor;

    /**
     * @var string
     */
    protected $path;

    public function __construct(
        SearchInterceptor $searchInterceptor,
        FrontendInterceptor $frontendInterceptor
    ){
        $this->searchInterceptor = $searchInterceptor;
        $this->frontendInterceptor = $frontendInterceptor;
        $this->path = $frontendInterceptor->getPluginPath();
    }

    /**
     * @param $exception
     */
    public function logException(\Exception $exception, $context, $uri = null)
    {
        Shopware()->PluginLogger()->error("BxExceptionLog: Exception on \"{$context}\" [uri: {$uri} line: {$exception->getLine()}, file: {$exception->getFile()}] with message : " . $exception->getMessage() . ', stack trace: ' . $exception->getTraceAsString());
    }

    public function getSearchInterceptor()
    {
        return $this->searchInterceptor;
    }

    public function getFrontendInterceptor()
    {
        return $this->frontendInterceptor;
    }

    public function getPath()
    {
        return $this->path;
    }
}