<?php

namespace Pi\Media\Factory\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Media\Service\S3Service;
use Psr\Container\ContainerInterface;

class S3ServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): S3Service
    {
        $config = $container->get('config');
        $config = $config['media'] ?? [];

        return new S3Service($config);
    }
}
