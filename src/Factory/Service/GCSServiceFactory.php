<?php

namespace Pi\Media\Factory\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Media\Service\GCSService;
use Psr\Container\ContainerInterface;

class GCSServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): GCSService
    {
        $config = $container->get('config');
        $config = $config['media'] ?? [];

        return new GCSService($config);
    }
}
