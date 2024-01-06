<?php

namespace Media\Factory\Handler\Admin;

use Interop\Container\Containerinterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Media\Handler\Admin\StreamHandler;
use Media\Service\MediaService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class StreamHandlerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): StreamHandler
    {
        return new StreamHandler(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(MediaService::class)
        );
    }
}