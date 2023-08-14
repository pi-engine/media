<?php

namespace Media\Factory\Middleware;

use Interop\Container\Containerinterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Media\Middleware\GetMediaMiddleware;
use Media\Service\MediaService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use User\Handler\ErrorHandler;

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