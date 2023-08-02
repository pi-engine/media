<?php

namespace Media;

use Company\Middleware\CompanyMiddleware;
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
            Repository\MediaRepository::class    => Factory\Repository\MediaRepositoryFactory::class,
            Service\MediaService::class          => Factory\Service\MediaServiceFactory::class,
            Storage\LocalStorage::class          => Factory\Storage\Local\StorageFactory::class,
            Storage\LocalDownload::class         => Factory\Storage\Local\DownloadFactory::class,
            Middleware\MediaMiddleware::class    => Factory\Middleware\MediaMiddlewareFactory::class,
            Handler\Api\AddPrivateHandler::class => Factory\Handler\Api\AddPrivateHandlerFactory::class,
            Handler\Api\AddPublicHandler::class  => Factory\Handler\Api\AddPublicHandlerFactory::class,
            Handler\Api\ListHandler::class       => Factory\Handler\Api\ListHandlerFactory::class,
            Handler\Api\GetHandler::class        => Factory\Handler\Api\GetHandlerFactory::class,
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
                    'add-private' => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/add-private',
                            'defaults' => [
                                'module'     => 'media',
                                'section'    => 'api',
                                'package'    => 'media',
                                'handler'    => 'add-private',
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    SecurityMiddleware::class,
                                    AuthenticationMiddleware::class,
                                    CompanyMiddleware::class,
                                    Middleware\MediaMiddleware::class,
                                    Handler\Api\AddPrivateHandler::class
                                ),
                            ],
                        ],
                    ],
                    'add-public' => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/add-public',
                            'defaults' => [
                                'module'     => 'media',
                                'section'    => 'api',
                                'package'    => 'media',
                                'handler'    => 'add-public',
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    SecurityMiddleware::class,
                                    AuthenticationMiddleware::class,
                                    Middleware\MediaMiddleware::class,
                                    Handler\Api\AddPublicHandler::class
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
                                    CompanyMiddleware::class,
                                    Handler\Api\ListHandler::class
                                ),
                            ],
                        ],
                    ],
                    'get'   => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/get',
                            'defaults' => [
                                'module'     => 'media',
                                'section'    => 'api',
                                'package'    => 'media',
                                'handler'    => 'get',
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    SecurityMiddleware::class,
                                    AuthenticationMiddleware::class,
                                    CompanyMiddleware::class,
                                    Handler\Api\GetHandler::class
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