<?php

namespace Pi\Media;

use Laminas\Mvc\Middleware\PipeSpec;
use Laminas\Router\Http\Literal;
use Pi\Company\Middleware\CompanyMiddleware;
use Pi\Core\Middleware\ErrorMiddleware;
use Pi\Core\Middleware\InstallerMiddleware;
use Pi\Core\Middleware\RequestPreparationMiddleware;
use Pi\Core\Middleware\SecurityMiddleware;
use Pi\Logger\Middleware\LoggerRequestResponseMiddleware;
use Pi\User\Middleware\AuthenticationMiddleware;
use Pi\User\Middleware\AuthorizationMiddleware;

return [
    'service_manager' => [
        'aliases'   => [
            Repository\MediaRepositoryInterface::class => Repository\MediaRepository::class,
        ],
        'factories' => [
            Repository\MediaRepository::class              => Factory\Repository\MediaRepositoryFactory::class,
            Service\MediaService::class                    => Factory\Service\MediaServiceFactory::class,
            Service\S3Service::class                       => Factory\Service\S3ServiceFactory::class,
            Storage\LocalStorage::class                    => Factory\Storage\LocalStorageFactory::class,
            Storage\S3Storage::class                       => Factory\Storage\S3StorageFactory::class,
            Download\LocalDownload::class                  => Factory\Download\LocalDownloadFactory::class,
            Download\S3Download::class                     => Factory\Download\S3DownloadFactory::class,
            Middleware\AuthorizationMediaMiddleware::class => Factory\Middleware\AuthorizationMediaMiddlewareFactory::class,
            Middleware\UploadMediaMiddleware::class        => Factory\Middleware\UploadMediaMiddlewareFactory::class,
            Middleware\GetMediaMiddleware::class           => Factory\Middleware\GetMediaMiddlewareFactory::class,
            Middleware\DeleteMediaMiddleware::class        => Factory\Middleware\DeleteMediaMiddlewareFactory::class,
            Validator\SlugValidator::class                 => Factory\Validator\SlugValidatorFactory::class,
            Handler\Api\AddPrivateHandler::class           => Factory\Handler\Api\AddPrivateHandlerFactory::class,
            Handler\Api\AddPublicHandler::class            => Factory\Handler\Api\AddPublicHandlerFactory::class,
            Handler\Api\AddRelationHandler::class          => Factory\Handler\Api\AddRelationHandlerFactory::class,
            Handler\Api\ListHandler::class                 => Factory\Handler\Api\ListHandlerFactory::class,
            Handler\Api\GetHandler::class                  => Factory\Handler\Api\GetHandlerFactory::class,
            Handler\Api\StreamHandler::class               => Factory\Handler\Api\StreamHandlerFactory::class,
            Handler\Api\UpdateHandler::class               => Factory\Handler\Api\UpdateHandlerFactory::class,
            Handler\Api\DeleteHandler::class               => Factory\Handler\Api\DeleteHandlerFactory::class,
            Handler\Admin\AddPrivateHandler::class         => Factory\Handler\Admin\AddPrivateHandlerFactory::class,
            Handler\Admin\AddPublicHandler::class          => Factory\Handler\Admin\AddPublicHandlerFactory::class,
            Handler\Admin\AddRelationHandler::class        => Factory\Handler\Admin\AddRelationHandlerFactory::class,
            Handler\Admin\ListHandler::class               => Factory\Handler\Admin\ListHandlerFactory::class,
            Handler\Admin\GetHandler::class                => Factory\Handler\Admin\GetHandlerFactory::class,
            Handler\Admin\StreamHandler::class             => Factory\Handler\Admin\StreamHandlerFactory::class,
            Handler\Admin\UpdateHandler::class             => Factory\Handler\Admin\UpdateHandlerFactory::class,
            Handler\Admin\ReadHandler::class               => Factory\Handler\Admin\ReadHandlerFactory::class,
            Handler\Admin\DeleteHandler::class             => Factory\Handler\Admin\DeleteHandlerFactory::class,
            Handler\InstallerHandler::class                => Factory\Handler\InstallerHandlerFactory::class,
        ],
    ],
    'router'          => [
        'routes' => [
            // Api section
            'api_media'   => [
                'type'         => Literal::class,
                'options'      => [
                    'route'    => '/media',
                    'defaults' => [],
                ],
                'child_routes' => [
                    'company' => [
                        'type'         => Literal::class,
                        'options'      => [
                            'route'    => '/company',
                            'defaults' => [],
                        ],
                        'child_routes' => [
                            'add-public'   => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/add-public',
                                    'defaults' => [
                                        'module'     => 'media',
                                        'section'    => 'api',
                                        'package'    => 'company',
                                        'handler'    => 'add-public',
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            AuthenticationMiddleware::class,
                                            CompanyMiddleware::class,
                                            Middleware\AuthorizationMediaMiddleware::class,
                                            Middleware\UploadMediaMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Api\AddPublicHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'add-private'  => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/add-private',
                                    'defaults' => [
                                        'module'     => 'media',
                                        'section'    => 'api',
                                        'package'    => 'company',
                                        'handler'    => 'add-private',
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            AuthenticationMiddleware::class,
                                            CompanyMiddleware::class,
                                            Middleware\AuthorizationMediaMiddleware::class,
                                            Middleware\UploadMediaMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Api\AddPrivateHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'add-relation' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/add-relation',
                                    'defaults' => [
                                        'module'     => 'media',
                                        'section'    => 'api',
                                        'package'    => 'company',
                                        'handler'    => 'add-relation',
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            AuthenticationMiddleware::class,
                                            CompanyMiddleware::class,
                                            Middleware\AuthorizationMediaMiddleware::class,
                                            Middleware\GetMediaMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Api\AddRelationHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'list'         => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/list',
                                    'defaults' => [
                                        'module'     => 'media',
                                        'section'    => 'api',
                                        'package'    => 'company',
                                        'handler'    => 'list',
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            AuthenticationMiddleware::class,
                                            CompanyMiddleware::class,
                                            Middleware\AuthorizationMediaMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Api\ListHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'get'          => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/get',
                                    'defaults' => [
                                        'module'     => 'media',
                                        'section'    => 'api',
                                        'package'    => 'company',
                                        'handler'    => 'get',
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            AuthenticationMiddleware::class,
                                            CompanyMiddleware::class,
                                            Middleware\AuthorizationMediaMiddleware::class,
                                            Middleware\GetMediaMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Api\GetHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'stream'       => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/stream',
                                    'defaults' => [
                                        'module'     => 'media',
                                        'section'    => 'api',
                                        'package'    => 'company',
                                        'handler'    => 'stream',
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            AuthenticationMiddleware::class,
                                            CompanyMiddleware::class,
                                            Middleware\AuthorizationMediaMiddleware::class,
                                            Middleware\GetMediaMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Api\StreamHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'update'       => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/update',
                                    'defaults' => [
                                        'module'     => 'media',
                                        'section'    => 'api',
                                        'package'    => 'company',
                                        'handler'    => 'update',
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            AuthenticationMiddleware::class,
                                            CompanyMiddleware::class,
                                            Middleware\AuthorizationMediaMiddleware::class,
                                            Middleware\GetMediaMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Api\UpdateHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'delete'       => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/delete',
                                    'defaults' => [
                                        'module'     => 'media',
                                        'section'    => 'api',
                                        'package'    => 'company',
                                        'handler'    => 'delete',
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            AuthenticationMiddleware::class,
                                            CompanyMiddleware::class,
                                            Middleware\AuthorizationMediaMiddleware::class,
                                            Middleware\DeleteMediaMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Api\DeleteHandler::class
                                        ),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'private' => [
                        'type'         => Literal::class,
                        'options'      => [
                            'route'    => '/private',
                            'defaults' => [],
                        ],
                        'child_routes' => [
                            'add-private'  => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/add-private',
                                    'defaults' => [
                                        'module'     => 'media',
                                        'section'    => 'api',
                                        'package'    => 'private',
                                        'handler'    => 'add-private',
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMediaMiddleware::class,
                                            Middleware\UploadMediaMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Api\AddPrivateHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'add-relation' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/add-relation',
                                    'defaults' => [
                                        'module'     => 'media',
                                        'section'    => 'api',
                                        'package'    => 'private',
                                        'handler'    => 'add-relation',
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMediaMiddleware::class,
                                            Middleware\GetMediaMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Api\AddRelationHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'list'         => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/list',
                                    'defaults' => [
                                        'module'     => 'media',
                                        'section'    => 'api',
                                        'package'    => 'private',
                                        'handler'    => 'list',
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMediaMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Api\ListHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'get'          => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/get',
                                    'defaults' => [
                                        'module'     => 'media',
                                        'section'    => 'api',
                                        'package'    => 'private',
                                        'handler'    => 'get',
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMediaMiddleware::class,
                                            Middleware\GetMediaMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Api\GetHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'stream'       => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/stream',
                                    'defaults' => [
                                        'module'     => 'media',
                                        'section'    => 'api',
                                        'package'    => 'private',
                                        'handler'    => 'stream',
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMediaMiddleware::class,
                                            Middleware\GetMediaMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Api\StreamHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'update'       => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/update',
                                    'defaults' => [
                                        'module'     => 'media',
                                        'section'    => 'api',
                                        'package'    => 'private',
                                        'handler'    => 'update',
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMediaMiddleware::class,
                                            Middleware\GetMediaMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Api\UpdateHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'delete'       => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/delete',
                                    'defaults' => [
                                        'module'     => 'media',
                                        'section'    => 'api',
                                        'package'    => 'private',
                                        'handler'    => 'delete',
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            AuthenticationMiddleware::class,
                                            Middleware\AuthorizationMediaMiddleware::class,
                                            Middleware\DeleteMediaMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Api\DeleteHandler::class
                                        ),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'public'  => [
                        'type'         => Literal::class,
                        'options'      => [
                            'route'    => '/public',
                            'defaults' => [],
                        ],
                        'child_routes' => [
                            'get'    => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/get',
                                    'defaults' => [
                                        'module'     => 'media',
                                        'section'    => 'api',
                                        'package'    => 'public',
                                        'handler'    => 'get',
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            Middleware\AuthorizationMediaMiddleware::class,
                                            Middleware\GetMediaMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Api\GetHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'stream' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/stream',
                                    'defaults' => [
                                        'module'     => 'media',
                                        'section'    => 'api',
                                        'package'    => 'public',
                                        'handler'    => 'stream',
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            Middleware\AuthorizationMediaMiddleware::class,
                                            Middleware\GetMediaMiddleware::class,
                                            LoggerRequestResponseMiddleware::class,
                                            ErrorMiddleware::class,
                                            Handler\Api\StreamHandler::class
                                        ),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            // Admin section
            'admin_media' => [
                'type'         => Literal::class,
                'options'      => [
                    'route'    => '/admin/media',
                    'defaults' => [],
                ],
                'child_routes' => [
                    'add-public'   => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/add-public',
                            'defaults' => [
                                'module'       => 'media',
                                'section'      => 'admin',
                                'package'      => 'general',
                                'handler'      => 'add-public',
                                'media_access' => 'admin',
                                'permissions'  => 'admin-media-add-public',
                                'controller'   => PipeSpec::class,
                                'middleware'   => new PipeSpec(
                                    RequestPreparationMiddleware::class,
                                    SecurityMiddleware::class,
                                    AuthenticationMiddleware::class,
                                    AuthorizationMiddleware::class,
                                    Middleware\AuthorizationMediaMiddleware::class,
                                    Middleware\UploadMediaMiddleware::class,
                                    LoggerRequestResponseMiddleware::class,
                                    ErrorMiddleware::class,
                                    Handler\Admin\AddPublicHandler::class
                                ),
                            ],
                        ],
                    ],
                    'add-private'  => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/add-private',
                            'defaults' => [
                                'module'       => 'media',
                                'section'      => 'admin',
                                'package'      => 'general',
                                'media_access' => 'admin',
                                'handler'      => 'add-private',
                                'permissions'  => 'admin-media-add-private',
                                'controller'   => PipeSpec::class,
                                'middleware'   => new PipeSpec(
                                    RequestPreparationMiddleware::class,
                                    SecurityMiddleware::class,
                                    AuthenticationMiddleware::class,
                                    AuthorizationMiddleware::class,
                                    Middleware\AuthorizationMediaMiddleware::class,
                                    Middleware\UploadMediaMiddleware::class,
                                    LoggerRequestResponseMiddleware::class,
                                    ErrorMiddleware::class,
                                    Handler\Admin\AddPrivateHandler::class
                                ),
                            ],
                        ],
                    ],
                    'add-relation' => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/add-relation',
                            'defaults' => [
                                'module'       => 'media',
                                'section'      => 'admin',
                                'package'      => 'general',
                                'media_access' => 'admin',
                                'handler'      => 'add-relation',
                                'permissions'  => 'admin-media-add-relation',
                                'controller'   => PipeSpec::class,
                                'middleware'   => new PipeSpec(
                                    RequestPreparationMiddleware::class,
                                    SecurityMiddleware::class,
                                    AuthenticationMiddleware::class,
                                    AuthorizationMiddleware::class,
                                    Middleware\AuthorizationMediaMiddleware::class,
                                    Middleware\GetMediaMiddleware::class,
                                    LoggerRequestResponseMiddleware::class,
                                    ErrorMiddleware::class,
                                    Handler\Admin\AddRelationHandler::class
                                ),
                            ],
                        ],
                    ],
                    'list'         => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/list',
                            'defaults' => [
                                'module'       => 'media',
                                'section'      => 'admin',
                                'package'      => 'general',
                                'handler'      => 'list',
                                'media_access' => 'admin',
                                'permissions'  => 'admin-media-list',
                                'controller'   => PipeSpec::class,
                                'middleware'   => new PipeSpec(
                                    RequestPreparationMiddleware::class,
                                    SecurityMiddleware::class,
                                    AuthenticationMiddleware::class,
                                    AuthorizationMiddleware::class,
                                    Middleware\AuthorizationMediaMiddleware::class,
                                    LoggerRequestResponseMiddleware::class,
                                    ErrorMiddleware::class,
                                    Handler\Admin\ListHandler::class
                                ),
                            ],
                        ],
                    ],
                    'get'          => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/get',
                            'defaults' => [
                                'module'       => 'media',
                                'section'      => 'admin',
                                'package'      => 'general',
                                'handler'      => 'get',
                                'media_access' => 'admin',
                                'permissions'  => 'admin-media-get',
                                'controller'   => PipeSpec::class,
                                'middleware'   => new PipeSpec(
                                    RequestPreparationMiddleware::class,
                                    SecurityMiddleware::class,
                                    AuthenticationMiddleware::class,
                                    AuthorizationMiddleware::class,
                                    Middleware\AuthorizationMediaMiddleware::class,
                                    Middleware\GetMediaMiddleware::class,
                                    LoggerRequestResponseMiddleware::class,
                                    ErrorMiddleware::class,
                                    Handler\Admin\GetHandler::class
                                ),
                            ],
                        ],
                    ],
                    'stream'       => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/stream',
                            'defaults' => [
                                'module'       => 'media',
                                'section'      => 'admin',
                                'package'      => 'general',
                                'handler'      => 'stream',
                                'media_access' => 'admin',
                                'permissions'  => 'admin-media-stream',
                                'controller'   => PipeSpec::class,
                                'middleware'   => new PipeSpec(
                                    RequestPreparationMiddleware::class,
                                    SecurityMiddleware::class,
                                    AuthenticationMiddleware::class,
                                    AuthorizationMiddleware::class,
                                    Middleware\AuthorizationMediaMiddleware::class,
                                    Middleware\GetMediaMiddleware::class,
                                    LoggerRequestResponseMiddleware::class,
                                    ErrorMiddleware::class,
                                    Handler\Admin\StreamHandler::class
                                ),
                            ],
                        ],
                    ],
                    'update'       => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/update',
                            'defaults' => [
                                'module'       => 'media',
                                'section'      => 'admin',
                                'package'      => 'general',
                                'handler'      => 'update',
                                'media_access' => 'admin',
                                'permissions'  => 'admin-media-update',
                                'controller'   => PipeSpec::class,
                                'middleware'   => new PipeSpec(
                                    RequestPreparationMiddleware::class,
                                    SecurityMiddleware::class,
                                    AuthenticationMiddleware::class,
                                    AuthorizationMiddleware::class,
                                    Middleware\AuthorizationMediaMiddleware::class,
                                    Middleware\GetMediaMiddleware::class,
                                    LoggerRequestResponseMiddleware::class,
                                    ErrorMiddleware::class,
                                    Handler\Api\UpdateHandler::class
                                ),
                            ],
                        ],
                    ],
                    'read'         => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/read',
                            'defaults' => [
                                'module'       => 'media',
                                'section'      => 'admin',
                                'package'      => 'general',
                                'handler'      => 'read',
                                'media_access' => 'admin',
                                'permissions'  => 'admin-media-read',
                                'controller'   => PipeSpec::class,
                                'middleware'   => new PipeSpec(
                                    RequestPreparationMiddleware::class,
                                    SecurityMiddleware::class,
                                    AuthenticationMiddleware::class,
                                    AuthorizationMiddleware::class,
                                    Middleware\AuthorizationMediaMiddleware::class,
                                    Middleware\GetMediaMiddleware::class,
                                    LoggerRequestResponseMiddleware::class,
                                    ErrorMiddleware::class,
                                    Handler\Admin\ReadHandler::class
                                ),
                            ],
                        ],
                    ],
                    'delete'       => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/delete',
                            'defaults' => [
                                'module'       => 'media',
                                'section'      => 'admin',
                                'package'      => 'general',
                                'handler'      => 'delete',
                                'media_access' => 'admin',
                                'permissions'  => 'admin-media-delete',
                                'controller'   => PipeSpec::class,
                                'middleware'   => new PipeSpec(
                                    RequestPreparationMiddleware::class,
                                    SecurityMiddleware::class,
                                    AuthenticationMiddleware::class,
                                    AuthorizationMiddleware::class,
                                    Middleware\AuthorizationMediaMiddleware::class,
                                    Middleware\DeleteMediaMiddleware::class,
                                    LoggerRequestResponseMiddleware::class,
                                    ErrorMiddleware::class,
                                    Handler\Admin\DeleteHandler::class
                                ),
                            ],
                        ],
                    ],
                    // Admin installer
                    'installer'    => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/installer',
                            'defaults' => [
                                'module'     => 'risk',
                                'section'    => 'admin',
                                'package'    => 'installer',
                                'handler'    => 'installer',
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    RequestPreparationMiddleware::class,
                                    SecurityMiddleware::class,
                                    AuthenticationMiddleware::class,
                                    InstallerMiddleware::class,
                                    Handler\InstallerHandler::class
                                ),
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'view_manager'    => [
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
];