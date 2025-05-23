<?php

namespace Pi\Media\Factory\Middleware;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Handler\ErrorHandler;
use Pi\Media\Middleware\GetMediaMiddleware;
use Pi\Media\Service\MediaService;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class GetMediaMiddlewareFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): GetMediaMiddleware
    {
        $config = $container->get('config');
        $config = $config['media'] ?? [];

        return new GetMediaMiddleware(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(ErrorHandler::class),
            $container->get(MediaService::class),
            $config
        );
    }
}