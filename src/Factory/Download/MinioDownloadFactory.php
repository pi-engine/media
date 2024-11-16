<?php

namespace Pi\Media\Factory\Download;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Media\Download\MinioDownload;
use Psr\Container\ContainerInterface;

class MinioDownloadFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): MinioDownload
    {
        $config = $container->get('config');
        $config = $config['media'] ?? [];

        return new MinioDownload($config);
    }
}
