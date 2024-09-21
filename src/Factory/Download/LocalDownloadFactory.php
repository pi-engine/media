<?php

namespace Media\Factory\Download;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Media\Download\LocalDownload;

class LocalDownloadFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): LocalDownload
    {
        $config = $container->get('config');
        $config = $config['media'] ?? [];

        return new LocalDownload($config);
    }
}
