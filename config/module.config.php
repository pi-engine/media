<?php

namespace Media;

use Company\Middleware\CheckMiddleware;
use Laminas\Mvc\Middleware\PipeSpec;
use Laminas\Router\Http\Literal;
use User\Middleware\AuthenticationMiddleware;
use User\Middleware\SecurityMiddleware;

return [
    'service_manager' => [
        'aliases'   => [
            Repository\MediaRepositoryInterface::class => Repository\MediaRepository::class,
        ],
        'factories' => [
            Repository\MediaRepository::class  => Factory\Repository\MediaRepositoryFactory::class,
            Service\MediaService::class        => Factory\Service\MediaServiceFactory::class,
            Service\LocalStorageService::class => Factory\Service\LocalStorageServiceFactory::class,
            Middleware\MediaMiddleware::class  => Factory\Middleware\MediaMiddlewareFactory::class,
            Handler\Api\AddHandler::class      => Factory\Handler\Api\AddHandlerFactory::class,
            Handler\Api\UpdateHandler::class   => Factory\Handler\Api\UpdateHandlerFactory::class,
            Handler\Api\ListHandler::class     => Factory\Handler\Api\ListHandlerFactory::class,
        ],
    ],

    'router'       => [
        'routes' => [
            // Api section
            'api_media' => [
                'type'         => Literal::class,
                'options'      => [
                    'route'    => '/media',
                    'defaults' => [],
                ],
                'child_routes' => [
                    'assign' => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/add',
                            'defaults' => [
                                'module'     => 'media',
                                'section'    => 'api',
                                'package'    => 'media',
                                'handler'    => 'add',
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    SecurityMiddleware::class,
                                    AuthenticationMiddleware::class,
                                    CheckMiddleware::class,
                                    Middleware\MediaMiddleware::class,
                                    Handler\Api\AddHandler::class
                                ),
                            ],
                        ],
                    ],
                    'update' => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/update',
                            'defaults' => [
                                'module'     => 'media',
                                'section'    => 'api',
                                'package'    => 'media',
                                'handler'    => 'update',
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    SecurityMiddleware::class,
                                    AuthenticationMiddleware::class,
                                    CheckMiddleware::class,
                                    Handler\Api\UpdateHandler::class
                                ),
                            ],
                        ],
                    ],
                    'list'   => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/list',
                            'defaults' => [
                                'module'     => 'media',
                                'section'    => 'api',
                                'package'    => 'media',
                                'handler'    => 'list',
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    SecurityMiddleware::class,
                                    AuthenticationMiddleware::class,
                                    CheckMiddleware::class,
                                    Handler\Api\ListHandler::class
                                ),
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'view_manager' => [
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
];