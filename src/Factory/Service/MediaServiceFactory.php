<?php

namespace Media\Factory\Service;

use Media\Download\MinioDownload;
use Media\Storage\MinioStorage;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Media\Download\LocalDownload;
use Media\Repository\MediaRepositoryInterface;
use Media\Service\MediaService;
use Media\Storage\LocalStorage;
use User\Service\AccountService;
use User\Service\UtilityService;

class MediaServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): MediaService
    {
        $config = $container->get('config');
        $config = $config['media'] ?? [];

        return new MediaService(
            $container->get(MediaRepositoryInterface::class),
            $container->get(AccountService::class),
            $container->get(UtilityService::class),
            $container->get(LocalStorage::class),
            $container->get(MinioStorage::class),
            $container->get(LocalDownload::class),
            $container->get(MinioDownload::class),
            $config
        );
    }
}
