<?php

namespace Pi\Media\Factory\Download;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Media\Download\LocalDownload;
use Psr\Container\ContainerInterface;

class LocalDownloadFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): LocalDownload
    {
        $config = $container->get('config');
        $config = $config['media'] ?? [];

        return new LocalDownload($config);
    }
}
