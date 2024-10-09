<?php

namespace Media\Factory\Download;

use Media\Download\MinioDownload;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class MinioDownloadFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): MinioDownload
    {
        $config = $container->get('config');
        $config = $config['media'] ?? [];

        return new MinioDownload($config);
    }
}
