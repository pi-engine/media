<?php

namespace Media\Factory\Storage\Local;

use Interop\Container\Containerinterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Media\Storage\LocalStorage;

class StorageFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): LocalStorage
    {
        $config = $container->get('config');
        $config = $config['media'] ?? [];

        return new LocalStorage($config);
    }
}
