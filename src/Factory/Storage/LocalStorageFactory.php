<?php

namespace Pi\Media\Factory\Storage;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Media\Storage\LocalStorage;
use Psr\Container\ContainerInterface;

class LocalStorageFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): LocalStorage
    {
        $config = $container->get('config');
        $config = $config['media'] ?? [];

        return new LocalStorage($config);
    }
}
