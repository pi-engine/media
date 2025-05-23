<?php

namespace Pi\Media\Factory\Middleware;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Handler\ErrorHandler;
use Pi\Core\Service\ConfigService;
use Pi\Core\Service\UtilityService;
use Pi\Media\Middleware\UploadMediaMiddleware;
use Pi\Media\Service\MediaService;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class UploadMediaMiddlewareFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): UploadMediaMiddleware
    {
        $config = $container->get('config');
        $config = $config['media'] ?? [];

        return new UploadMediaMiddleware(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(ErrorHandler::class),
            $container->get(MediaService::class),
            $container->get(UtilityService::class),
            $container->get(ConfigService::class),
            $config
        );
    }
}