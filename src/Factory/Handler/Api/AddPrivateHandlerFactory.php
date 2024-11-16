<?php

namespace Pi\Media\Factory\Handler\Api;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Media\Handler\Api\AddPrivateHandler;
use Pi\Media\Service\MediaService;
use Psr\Container\ContainerInterface;
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