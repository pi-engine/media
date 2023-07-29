<?php

namespace Media\Factory\Service;

use Interop\Container\Containerinterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Media\Service\LocalStorageService;

class LocalStorageServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): LocalStorageService
    {
        $config = $container->get('config');
        $config = $config['media'] ?? [];

        return new LocalStorageService(
            $config
        );
    }
}
