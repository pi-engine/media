<?php

namespace Pi\Media\Factory\Download;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Media\Download\S3Download;
use Psr\Container\ContainerInterface;

class S3DownloadFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): S3Download
    {
        $config = $container->get('config');
        $config = $config['media'] ?? [];

        return new S3Download($config);
    }
}
