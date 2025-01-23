<?php

return [
    'admin' => [
        [
            'title'       => 'Admin media add public',
            'module'      => 'media',
            'section'     => 'admin',
            'package'     => 'general',
            'handler'     => 'add-public',
            'permissions' => 'admin-media-add-public',
            'role'        => [
                'admin',
            ],
        ],
        [
            'title'       => 'Admin media add private',
            'module'      => 'media',
            'section'     => 'admin',
            'package'     => 'general',
            'handler'     => 'add-private',
            'permissions' => 'admin-media-add-private',
            'role'        => [
                'admin',
            ],
        ],
        [
            'title'       => 'Admin media add relation',
            'module'      => 'media',
            'section'     => 'admin',
            'package'     => 'general',
            'handler'     => 'add-relation',
            'permissions' => 'admin-media-add-relation',
            'role'        => [
                'admin',
            ],
        ],
        [
            'title'       => 'Admin media list',
            'module'      => 'media',
            'section'     => 'admin',
            'package'     => 'general',
            'handler'     => 'list',
            'permissions' => 'admin-media-list',
            'role'        => [
                'admin',
            ],
        ],
        [
            'title'       => 'Admin media get',
            'module'      => 'media',
            'section'     => 'admin',
            'package'     => 'general',
            'handler'     => 'get',
            'permissions' => 'admin-media-get',
            'role'        => [
                'admin',
            ],
        ],
        [
            'title'       => 'Admin media stream',
            'module'      => 'media',
            'section'     => 'admin',
            'package'     => 'general',
            'handler'     => 'stream',
            'permissions' => 'admin-media-stream',
            'role'        => [
                'admin',
            ],
        ],
        [
            'title'       => 'Admin media update',
            'module'      => 'media',
            'section'     => 'admin',
            'package'     => 'general',
            'handler'     => 'update',
            'permissions' => 'admin-media-update',
            'role'        => [
                'admin',
            ],
        ],
        [
            'title'       => 'Admin media read',
            'module'      => 'media',
            'section'     => 'admin',
            'package'     => 'general',
            'handler'     => 'read',
            'permissions' => 'admin-media-read',
            'role'        => [
                'admin',
            ],
        ],
        [
            'title'       => 'Admin media delete',
            'module'      => 'media',
            'section'     => 'admin',
            'package'     => 'general',
            'handler'     => 'delete',
            'permissions' => 'admin-media-delete',
            'role'        => [
                'admin',
            ],
        ],
    ],
];