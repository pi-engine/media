<?php

namespace Pi\Media\Factory\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Service\UtilityService;
use Pi\Media\Download\LocalDownload;
use Pi\Media\Download\MinioDownload;
use Pi\Media\Repository\MediaRepositoryInterface;
use Pi\Media\Service\MediaService;
use Pi\Media\Storage\LocalStorage;
use Pi\Media\Storage\MinioStorage;
use Pi\User\Service\AccountService;
use Psr\Container\ContainerInterface;

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
