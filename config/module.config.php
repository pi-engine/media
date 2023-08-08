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
            Repository\MediaRepository::class       => Factory\Repository\MediaRepositoryFactory::class,
            Service\MediaService::class             => Factory\Service\MediaServiceFactory::class,
            Storage\LocalStorage::class             => Factory\Storage\LocalStorageFactory::class,
            Download\LocalDownload::class           => Factory\Download\LocalDownloadFactory::class,
            Middleware\UploadMediaMiddleware::class => Factory\Middleware\UploadMediaMiddlewareFactory::class,
            Middleware\GetMediaMiddleware::class    => Factory\Middleware\GetMediaMiddlewareFactory::class,
            Handler\Api\AddPrivateHandler::class    => Factory\Handler\Api\AddPrivateHandlerFactory::class,
            Handler\Api\AddPublicHandler::class     => Factory\Handler\Api\AddPublicHandlerFactory::class,
            Handler\Api\ListHandler::class          => Factory\Handler\Api\ListHandlerFactory::class,
            Handler\Api\GetHandler::class           => Factory\Handler\Api\GetHandlerFactory::class,
            Handler\Api\StreamHandler::class           => Factory\Handler\Api\StreamHandlerFactory::class,
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
                                    Middleware\UploadMediaMiddleware::class,
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
                                    Middleware\UploadMediaMiddleware::class,
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
                                    Middleware\GetMediaMiddleware::class,
                                    Handler\Api\GetHandler::class
                                ),
                            ],
                        ],
                    ],
                    'stream'   => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/stream',
                            'defaults' => [
                                'module'     => 'media',
                                'section'    => 'api',
                                'package'    => 'media',
                                'handler'    => 'stream',
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    SecurityMiddleware::class,
                                    AuthenticationMiddleware::class,
                                    CompanyMiddleware::class,
                                    Middleware\GetMediaMiddleware::class,
                                    Handler\Api\StreamHandler::class
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