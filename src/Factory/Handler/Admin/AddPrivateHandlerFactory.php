<?php

namespace Pi\Media\Factory\Handler\Admin;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Media\Handler\Admin\AddPrivateHandler;
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