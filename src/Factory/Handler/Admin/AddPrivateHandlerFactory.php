<?php

namespace Media\Factory\Handler\Admin;

use Interop\Container\Containerinterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Media\Handler\Admin\AddPrivateHandler;
use Media\Service\MediaService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class AddPrivateHandlerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): AddPrivateHandler
    {
        return new AddPrivateHandler(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(MediaService::class)
        );
    }
}