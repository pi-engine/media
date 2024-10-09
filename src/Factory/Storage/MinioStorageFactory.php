<?php

namespace Media\Factory\Storage;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Media\Storage\MinioStorage;
use Psr\Container\ContainerInterface;

class MinioStorageFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): MinioStorage
    {
        $config = $container->get('config');
        $config = $config['media'] ?? [];

        return new MinioStorage($config);
    }
}
