<?php
namespace Boxalino\IntelligenceFramework\Service\ErrorHandler;

class UndefinedMethodError extends \Error
{
    /**
     * {@inheritdoc}
     */
    public function __construct(string $message, \Throwable $previous)
    {
        parent::__construct($message, $previous->getCode(), $previous->getPrevious());

        foreach ([
                     'file' => $previous->getFile(),
                     'line' => $previous->getLine(),
                     'trace' => $previous->getTrace(),
                 ] as $property => $value) {
            $refl = new \ReflectionProperty(\Error::class, $property);
            $refl->setAccessible(true);
            $refl->setValue($this, $value);
        }
    }
}
