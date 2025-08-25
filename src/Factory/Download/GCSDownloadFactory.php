<?php

namespace Pi\Media\Factory\Download;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Media\Download\GCSDownload;
use Pi\Media\Service\GCSService;
use Psr\Container\ContainerInterface;

class GCSDownloadFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): GCSDownload
    {
        $config = $container->get('config');
        $config = $config['media'] ?? [];

        return new GCSDownload(
            $container->get(GCSService::class),
            $config
        );
    }
}
