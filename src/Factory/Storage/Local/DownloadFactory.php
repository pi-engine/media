<?php

namespace Media\Factory\Storage\Local;

use Interop\Container\Containerinterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Media\Storage\LocalDownload;

class DownloadFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): LocalDownload
    {
        $config = $container->get('config');
        $config = $config['media'] ?? [];

        return new LocalDownload($config);
    }
}
