<?php

namespace Pi\Media\Factory\Validator;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Media\Service\MediaService;
use Pi\Media\Validator\SlugValidator;
use Pi\User\Validator\MobileValidator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class SlugValidatorFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return MobileValidator
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): SlugValidator
    {
        return new SlugValidator(
            $container->get(MediaService::class)
        );
    }
}