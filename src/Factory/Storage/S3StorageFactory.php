<?php

namespace Pi\Media\Factory\Storage;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Media\Storage\S3Storage;
use Psr\Container\ContainerInterface;

class S3StorageFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): S3Storage
    {
        $config = $container->get('config');
        $config = $config['media'] ?? [];

        return new S3Storage($config);
    }
}
