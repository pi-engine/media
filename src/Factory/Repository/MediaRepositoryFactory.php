<?php

namespace Media\Factory\Repository;

use Interop\Container\Containerinterface;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Hydrator\ReflectionHydrator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Media\Model\Relation;
use Media\Model\Storage;
use Media\Repository\MediaRepository;

class MediaRepositoryFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): MediaRepository
    {
        return new MediaRepository(
            $container->get(AdapterInterface::class),
            new ReflectionHydrator(),
            new Storage('', '', 0, 0, '', '', '', '', 0, 0, 0, '', 0),
            new Relation(0, 0, 0, '', '', '', 0, 0, 0, 0, 0)
        );
    }
}