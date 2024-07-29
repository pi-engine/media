<?php

return [
    'admin' => [
        [
            'title'       => 'Admin media add public',
            'module'      => 'media',
            'section'     => 'admin',
            'package'     => 'media',
            'handler'     => 'add-public',
            'permissions' => 'media-media-add-public',
            'role'        => [
                'admin',
            ],
        ],
        [
            'title'       => 'Admin media add private',
            'module'      => 'media',
            'section'     => 'admin',
            'package'     => 'media',
            'handler'     => 'add-private',
            'permissions' => 'media-media-add-private',
            'role'        => [
                'admin',
            ],
        ],
        [
            'title'       => 'Admin media add relation',
            'module'      => 'media',
            'section'     => 'admin',
            'package'     => 'media',
            'handler'     => 'add-relation',
            'permissions' => 'media-media-add-relation',
            'role'        => [
                'admin',
            ],
        ],
        [
            'title'       => 'Admin media list',
            'module'      => 'media',
            'section'     => 'admin',
            'package'     => 'media',
            'handler'     => 'list',
            'permissions' => 'media-media-list',
            'role'        => [
                'admin',
            ],
        ],
        [
            'title'       => 'Admin media get',
            'module'      => 'media',
            'section'     => 'admin',
            'package'     => 'media',
            'handler'     => 'get',
            'permissions' => 'media-media-get',
            'role'        => [
                'admin',
            ],
        ],
        [
            'title'       => 'Admin media stream',
            'module'      => 'media',
            'section'     => 'admin',
            'package'     => 'media',
            'handler'     => 'stream',
            'permissions' => 'media-media-stream',
            'role'        => [
                'admin',
            ],
        ],
        [
            'title'       => 'Admin media update',
            'module'      => 'media',
            'section'     => 'admin',
            'package'     => 'media',
            'handler'     => 'update',
            'permissions' => 'media-media-update',
            'role'        => [
                'admin',
            ],
        ],
        [
            'title'       => 'Admin media delete',
            'module'      => 'media',
            'section'     => 'admin',
            'package'     => 'media',
            'handler'     => 'delete',
            'permissions' => 'media-media-delete',
            'role'        => [
                'admin',
            ],
        ],
    ],
];