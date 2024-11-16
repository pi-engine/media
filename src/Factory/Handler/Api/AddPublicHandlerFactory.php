<?php

namespace Pi\Media\Factory\Handler\Api;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Media\Handler\Api\AddPublicHandler;
use Pi\Media\Service\MediaService;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class AddPublicHandlerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): AddPublicHandler
    {
        return new AddPublicHandler(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(MediaService::class)
        );
    }
}