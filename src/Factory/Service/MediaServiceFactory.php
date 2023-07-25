<?php

namespace Media\Factory\Service;

use Interop\Container\Containerinterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Media\Repository\MediaRepositoryInterface;
use Media\Service\LocalStorageService;
use Media\Service\MediaService;
use User\Service\AccountService;
use User\Service\UtilityService;

class MediaServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): MediaService
    {
        //$config = $container->get('config');
        //$config = $config['controls'] ?? [];
        $config = [];

        return new MediaService(
            $container->get(MediaRepositoryInterface::class),
            $container->get(LocalStorageService::class),
            $container->get(AccountService::class),
            $container->get(UtilityService::class),
            $config
        );
    }
}
