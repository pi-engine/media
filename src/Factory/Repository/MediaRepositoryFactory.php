<?php

namespace Pi\Media\Factory\Repository;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Hydrator\ReflectionHydrator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Media\Model\Relation;
use Pi\Media\Model\Storage;
use Pi\Media\Repository\MediaRepository;
use Psr\Container\ContainerInterface;

class MediaRepositoryFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): MediaRepository
    {
        return new MediaRepository(
            $container->get(AdapterInterface::class),
            new ReflectionHydrator(),
            new Storage('', '', 0, 0, 0, '', '', '', '', 0, 0, 0, 0, 0, '', '', '', '', '', 0),
            new Relation(0, 0, 0, '', '', '', 0, 0, 0, 0, '', 0)
        );
    }
}