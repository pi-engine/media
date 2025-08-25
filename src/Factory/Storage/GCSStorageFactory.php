<?php

namespace Pi\Media\Factory\Storage;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Media\Service\GCSService;
use Pi\Media\Storage\GCSStorage;
use Psr\Container\ContainerInterface;

class GCSStorageFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): GCSStorage
    {
        $config = $container->get('config');
        $config = $config['media'] ?? [];

        return new GCSStorage(
            $container->get(GCSService::class),
            $config
        );
    }
}
