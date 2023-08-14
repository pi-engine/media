<?php

namespace Media\Factory\Handler\Api;

use Interop\Container\Containerinterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Media\Handler\Api\AddRelationHandler;
use Media\Service\MediaService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class AddRelationHandlerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): AddRelationHandler
    {
        return new AddRelationHandler(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(MediaService::class)
        );
    }
}