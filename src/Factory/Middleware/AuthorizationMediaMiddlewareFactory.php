<?php

namespace Media\Factory\Middleware;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Media\Middleware\AuthorizationMediaMiddleware;
use Media\Service\MediaService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use User\Handler\ErrorHandler;

class AuthorizationMediaMiddlewareFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): AuthorizationMediaMiddleware
    {
        $config = $container->get('config');
        $config = $config['media'] ?? [];

        return new AuthorizationMediaMiddleware(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(ErrorHandler::class),
            $container->get(MediaService::class),
            $config
        );
    }
}