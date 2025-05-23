<?php

namespace Pi\Media\Service;

use Pi\Core\Service\UtilityService;
use Pi\Media\Download\LocalDownload;
use Pi\Media\Download\S3Download;
use Pi\Media\Repository\MediaRepositoryInterface;
use Pi\Media\Storage\LocalStorage;
use Pi\Media\Storage\S3Storage;
use Pi\User\Service\AccountService;

class MediaService implements ServiceInterface
{
    /** @var MediaRepositoryInterface */
    protected MediaRepositoryInterface $mediaRepository;

    /** @var AccountService */
    protected AccountService $accountService;

    /** @var UtilityService */
    protected UtilityService $utilityService;

    /** @var S3Service */
    protected S3Service $s3Service;

    /** @var LocalStorage */
    protected LocalStorage $localStorage;

    /** @var S3Storage */
    protected S3Storage $s3Storage;

    /** @var LocalDownload */
    protected LocalDownload $localDownload;

    /** @var S3Download */
    protected S3Download $s3Download;

    /* @var array */
    protected array $config;

    protected string $storage = 'local';

    protected array $defaultTypes
        = [
            'image'        => [
                'key'   => 'image',
                'title' => 'Image',
                'value' => 0,
            ],
            'document'     => [
                'key'   => 'document',
                'title' => 'Document',
                'value' => 0,
            ],
            'spreadsheet'  => [
                'key'   => 'spreadsheet',
                'title' => 'Spreadsheet',
                'value' => 0,
            ],
            'presentation' => [
                'key'   => 'presentation',
                'title' => 'Presentation',
                'value' => 0,
            ],
            'pdf'          => [
                'key'   => 'pdf',
                'title' => 'Pdf',
                'value' => 0,
            ],
            //'video'        => 0,
            //'audio'        => 0,
            //'archive'      => 0,
            //'script'       => 0,
            //'executable'   => 0,
            //'font'         => 0,
            //'config'       => 0,
        ];

    public function __construct(
        MediaRepositoryInterface $mediaRepository,
        AccountService           $accountService,
        UtilityService           $utilityService,
        S3Service                $s3Service,
        LocalStorage             $localStorage,
        S3Storage                $s3Storage,
        LocalDownload            $localDownload,
        S3Download               $s3Download,
                                 $config
    ) {
        $this->mediaRepository = $mediaRepository;
        $this->accountService  = $accountService;
        $this->utilityService  = $utilityService;
        $this->s3Service       = $s3Service;
        $this->localStorage    = $localStorage;
        $this->s3Storage       = $s3Storage;
        $this->localDownload   = $localDownload;
        $this->s3Download      = $s3Download;
        $this->config          = $config;
    }

    public function addMedia($uploadFile, $authorization, $params, $storeInfo = []): array
    {
        // Store media
        if (empty($storeInfo)) {
            $storeInfo = $this->storeMedia($uploadFile, $authorization, $params);
            $storeInfo = $storeInfo['data'];
        }

        // Save and return
        return $this->saveMedia($authorization, $params, $storeInfo);
    }

    public function storeMedia($uploadFile, $authorization, $params): array
    {
        // Set storage
        if (isset($params['storage']) && in_array($params['storage'], ['local', 's3'])) {
            $this->storage = $params['storage'];
        } elseif (isset($this->config['storage']) && in_array($this->config['storage'], ['local', 's3'])) {
            $this->storage = $this->config['storage'] ?? 'local';
        }

        // Set
        switch ($this->storage) {
            default:
            case 'local':
                // Set path
                switch ($params['access']) {
                    case 'company':
                        $path = sprintf('%s/%s/%s', $authorization['company']['hash'], date('Y'), date('m'));
                        break;

                    case 'private':
                        $path = sprintf('%s/%s/%s', $authorization['user']['hash'], date('Y'), date('m'));
                        break;

                    default:
                    case 'public':
                        $path = sprintf('%s/%s', date('Y'), date('m'));
                        break;
                }

                // Set storage params
                $storageParams = [
                    'storage'     => $this->storage,
                    'access'      => $params['access'],
                    'local_path'  => $path,
                    'random_name' => $params['random_name'] ?? 0,
                ];

                // Store media
                $result = $this->localStorage->storeMedia($uploadFile, $storageParams);
                break;

            case 's3':
                // Set storage params
                $storageParams = [
                    'storage'     => $this->storage,
                    'access'      => $authorization['access'],
                    'Bucket'      => $authorization['company']['slug'],
                    'random_name' => $params['random_name'] ?? 0,
                    'company_id'  => $authorization['company_id'],
                    'user_id'     => $authorization['user_id'],
                ];

                $result = $this->s3Storage->storeMedia($uploadFile, $storageParams);
                break;
        }

        return $result;
    }

    public function saveMedia($authorization, $params, $storeInfo): array
    {
        // Set storage params
        $addStorage = [
            'title'       => $params['title'] ?? $storeInfo['file_title'],
            'user_id'     => $authorization['user_id'],
            'company_id'  => $authorization['company_id'],
            'category_id' => $params['category_id'] ?? 0,
            'access'      => $params['access'],
            'storage'     => $this->storage,
            'type'        => $storeInfo['file_type'],
            'extension'   => $storeInfo['file_extension'],
            'status'      => 1,
            'size'        => $storeInfo['file_size'],
            'time_create' => time(),
            'time_update' => time(),
            'information' => json_encode(
                [
                    'storage'  => $storeInfo,
                    'category' => $params['category'] ?? [],
                    'review'   => (isset($params['review']) && !empty($params['review'])) ? [$params['review']] : [],
                    'ai'       => null,
                    'history'  => [
                        [
                            'action'  => 'add',
                            'user_id' => $authorization['user_id'],
                            'data'    => [
                                'title'       => $params['title'] ?? $storeInfo['file_title'],
                                'type'        => $storeInfo['file_type'],
                                'extension'   => $storeInfo['file_extension'],
                                'status'      => 1,
                                'size'        => $storeInfo['file_size'],
                                'time_update' => time(),
                            ],
                        ],
                    ],
                ],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK
            ),
        ];

        // Set slug for a check duplicate
        if (isset($this->config['check_duplicate']) && (int)$this->config['check_duplicate'] === 1) {
            $slug = sprintf(
                '%s-%s-%s-%s',
                $params['access'],
                $authorization['user_id'],
                $authorization['company_id'],
                $storeInfo['original_name']
            );

            $addStorage['slug'] = $this->utilityService->slug($slug);
        }

        // Save storage
        $storage = $this->mediaRepository->addMedia($addStorage);
        $storage = $this->canonizeStorage($storage, ['view' => $params['view'] ?? 'limited']);

        // Check media have a relation
        if (
            !empty($params['relation_module'])
            && !empty($params['relation_section'])
            && !empty($params['relation_item'])
            && is_numeric($params['relation_item'])
        ) {
            // Set relation params
            $addRelation = [
                'storage_id'       => $storage['id'],
                'user_id'          => $authorization['user_id'],
                'company_id'       => $authorization['company_id'],
                'access'           => $params['access'],
                'relation_module'  => $params['relation_module'],
                'relation_section' => $params['relation_section'],
                'relation_item'    => (int)$params['relation_item'],
                'status'           => 1,
                'time_create'      => time(),
                'time_update'      => time(),
                'information'      => json_encode(
                    [
                        'relation_item_title'      => $params['relation_item_title'] ?? null,
                        'relation_framework_id'    => $params['relation_framework_id'] ?? null,
                        'relation_framework_title' => $params['relation_framework_title'] ?? null,
                        'relation_domain_id'       => $params['relation_domain_id'] ?? null,
                        'relation_domain_title'    => $params['relation_domain_title'] ?? null,
                    ],
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK
                ),
            ];

            // Save relation
            $relation              = $this->mediaRepository->addMediaRelation($addRelation);
            $storage['relation'][] = $this->canonizeRelation($relation);
        }

        return $storage;
    }

    public function createMedia($authorization, $params, $storageParams): array
    {
        // Store media
        $storeInfo = $this->localStorage->createMedia($storageParams);

        // Save and return
        return $this->saveMedia($authorization, $params, $storeInfo);
    }

    public function getMediaList($authorization, $params): array
    {
        if (!in_array($authorization['access'], ['private', 'company', 'group', 'admin'])) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message' => 'Please select the true access type !',
                ],
            ];
        }

        $view   = (isset($params['view']) && in_array($params['view'], ['limited', 'compressed', 'full'])) ? $params['view'] : 'limited';
        $limit  = (int)($params['limit'] ?? 25);
        $page   = (int)($params['page'] ?? 1);
        $order  = $params['order'] ?? ['time_create DESC'];
        $offset = ($page - 1) * $limit;

        $listParams = [
            'order'  => $order,
            'offset' => $offset,
            'limit'  => $limit,
        ];

        if ($authorization['access'] == 'company') {
            $listParams['access']     = 'company';
            $listParams['company_id'] = $authorization['company_id'];
            if (!$authorization['is_admin']) {
                $listParams['user_id'] = $authorization['user_id'];
            } elseif (isset($params['user_id']) && !empty($params['user_id'])) {
                $listParams['user_id'] = $params['user_id'];
            }
        } elseif ($authorization['access'] == 'private') {
            $listParams['access']  = 'private';
            $listParams['user_id'] = $authorization['user_id'];
        } elseif ($authorization['access'] == 'admin') {
            if (isset($params['user_id']) && !empty($params['user_id'])) {
                $listParams['user_id'] = $params['user_id'];
            }
        }

        if (isset($params['type']) && !empty($params['type'])) {
            $listParams['type'] = $params['type'];
        }
        if (isset($params['extension']) && !empty($params['extension'])) {
            $listParams['extension'] = $params['extension'];
        }
        if (isset($params['category_id']) && !empty($params['category_id'])) {
            $listParams['category_id'] = $params['category_id'];
        }
        if (isset($params['status']) && !empty($params['status'])) {
            $listParams['status'] = $params['status'];
        }
        if (isset($params['slug']) && !empty($params['slug'])) {
            $listParams['slug'] = $params['slug'];
        }
        if (isset($params['id']) && !empty($params['id'])) {
            $listParams['id'] = $params['id'];
        }

        // Check request has relation information
        if (isset($params['relation_module'])
            && !empty($params['relation_module'])
            && isset($params['relation_section'])
            && !empty($params['relation_section'])
            && isset($params['relation_item'])
            && !empty($params['relation_item'])
        ) {
            $listParams['relation_module']  = $params['relation_module'];
            $listParams['relation_section'] = $params['relation_section'];
            $listParams['relation_item']    = $params['relation_item'];

            // Get list
            $list   = [];
            $rowSet = $this->mediaRepository->getMediaListByRelationList($listParams);
            foreach ($rowSet as $row) {
                $list[$row->getId()] = $this->canonizeStorage($row, ['view' => $view]);
            }

            // Get count
            $count = $this->mediaRepository->getMediaListByRelationCount($listParams);
        } else {
            // Get list
            $list   = [];
            $rowSet = $this->mediaRepository->getMediaList($listParams);
            foreach ($rowSet as $row) {
                $list[$row->getId()] = $this->canonizeStorage($row, ['view' => $view]);
            }

            if (!empty($list) && !in_array($view, ['limited', 'compressed'])) {
                $rowSet = $this->mediaRepository->getMediaRelationList(['storage_id' => array_keys($list)]);
                foreach ($rowSet as $row) {
                    $list[$row->getStorageId()]['relation'][] = $this->canonizeRelation($row);
                }
            }

            // Get count
            $count = $this->mediaRepository->getMediaCount($listParams);
        }

        return [
            'result' => true,
            'data'   => [
                'list'      => array_values($list),
                'paginator' => [
                    'count' => $count,
                    'limit' => $limit,
                    'page'  => $page,
                ],
            ],
            'error'  => [],
        ];
    }

    public function addRelation($storage, $authorization, $params): array
    {
        // Set relation params
        $addRelation = [
            'storage_id'       => $storage['id'],
            'user_id'          => $authorization['user_id'],
            'company_id'       => $authorization['company_id'],
            'access'           => $storage['access'],
            'relation_module'  => $params['relation_module'],
            'relation_section' => $params['relation_section'],
            'relation_item'    => (int)$params['relation_item'],
            'status'           => 1,
            'time_create'      => time(),
            'time_update'      => time(),
            'information'      => json_encode(
                [
                    'relation_item_title'      => $params['relation_item_title'] ?? null,
                    'relation_framework_id'    => $params['relation_framework_id'] ?? null,
                    'relation_framework_title' => $params['relation_framework_title'] ?? null,
                    'relation_domain_id'       => $params['relation_domain_id'] ?? null,
                    'relation_domain_title'    => $params['relation_domain_title'] ?? null,
                ],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK
            ),
        ];

        // Save relation
        $relation              = $this->mediaRepository->addMediaRelation($addRelation);
        $storage['relation'][] = $this->canonizeRelation($relation);

        // Clean up
        unset($storage['information']['storage']);

        return $storage;
    }

    public function updateMediaWhitFile($media, $uploadFile, $authorization, $params): array
    {
        // Store media
        $storeInfo = $this->storeMedia($uploadFile, $authorization, $params);
        $storeInfo = $storeInfo['data'];

        // Set update params
        $updateParams = [
            'time_update' => time(),
        ];
        if (isset($params['title']) && !empty($params['title'])) {
            $updateParams['title'] = $params['title'];
        }
        if (isset($params['status']) && is_numeric($params['status'])) {
            $updateParams['status'] = (int)$params['status'];
        }
        if (isset($storeInfo['file_extension']) && !empty($storeInfo['file_extension'])) {
            $updateParams['extension'] = $storeInfo['file_extension'];
            $updateParams['type']      = $storeInfo['file_type'];
        }
        if (isset($storeInfo['file_size']) && !empty($storeInfo['file_size'])) {
            $updateParams['size'] = $storeInfo['file_size'];
        }

        // Set information
        $information = $media['information'];
        if (isset($storeInfo) && !empty($storeInfo)) {
            $information['storage'] = $storeInfo;
        }
        if (isset($params['category']) && !empty($params['category'])) {
            $information['category'] = $params['category'];
        }
        if (isset($params['review']) && !empty($params['review'])) {
            $information['review'][] = $params['review'];
        }
        if (isset($params['ai']) && !empty($params['ai'])) {
            $information['ai'] = $params['ai'];
        }

        // Set history
        $information['history'][] = [
            'action'  => 'update',
            'user_id' => $authorization['user_id'],
            'data'    => $updateParams,
        ];

        // Set information
        $updateParams['information'] = json_encode(
            $information,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK
        );

        // Save and get
        $this->mediaRepository->updateMedia((int)$media['id'], $updateParams);
        return $this->getMedia($media);
    }

    public function updateMedia($media, $authorization, $params): array
    {
        // Set update params
        $updateParams = [
            'time_update' => time(),
        ];
        if (isset($params['title']) && !empty($params['title'])) {
            $updateParams['title'] = $params['title'];
        }
        if (isset($params['status']) && is_numeric($params['status'])) {
            $updateParams['status'] = (int)$params['status'];
        }
        if (isset($params['category_id']) && is_numeric($params['category_id'])) {
            $updateParams['category_id'] = (int)$params['category_id'];
        }

        // Set information
        $information = $media['information'];
        if (isset($params['category']) && !empty($params['category'])) {
            $information['category'] = $params['category'];
        }
        if (isset($params['review']) && !empty($params['review'])) {
            $information['review'][] = $params['review'];
        }
        if (isset($params['ai']) && !empty($params['ai'])) {
            $information['ai'] = $params['ai'];
        }

        // Set history
        $information['history'][] = [
            'action'  => 'update',
            'user_id' => $authorization['user_id'],
            'data'    => $updateParams,
        ];

        // Set information
        $updateParams['information'] = json_encode(
            $information,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK
        );

        // Save and get
        $this->mediaRepository->updateMedia((int)$media['id'], $updateParams);
        return $this->getMedia($media);
    }

    public function getMedia($params): array
    {
        $media = $this->mediaRepository->getMedia(['id' => $params['id']]);
        return $this->canonizeStorage($media);
    }

    public function deleteMedia($media, $authorization): void
    {
        // Delete the file
        switch ($media['storage']) {
            case 'local':
                $this->localStorage->remove($media['information']['storage']['local']['file_path']);
                break;

            case 's3':
                $this->s3Storage->remove($media['storage']['s3']);
                break;
        }

        // Delete relation
        $this->mediaRepository->deleteMediaRelation((int)$media['id']);

        // Delete storage
        $this->mediaRepository->deleteMedia((int)$media['id']);
    }

    public function streamMedia($media): string
    {
        // Update download count
        $this->mediaRepository->updateDownloadCount((int)$media['id']);

        switch ($media['storage']) {
            default:
            case 'local':
                // Set stream params
                $params = [
                    'source' => $media['information']['storage']['local']['file_path'],
                ];
                if (isset($media['information']['storage']['original_name'])) {
                    $params['filename'] = $media['information']['storage']['original_name'];
                }
                if (isset($media['information']['storage']['file_extension'])) {
                    $params['content_type'] = $media['information']['storage']['file_extension'];
                }

                // Start stream
                return $this->localDownload->stream($params);
                break;

            case 's3':
                // Set stream params
                $params = [
                    'Key'    => $media['information']['storage']['s3']['Key'],
                    'Bucket' => $media['information']['storage']['s3']['Bucket'],
                ];

                // Start stream
                return $this->s3Download->stream($params);
                break;
        }
    }

    public function streamFile($filePath): string
    {
        // Set params
        $params = ['source' => $filePath];

        // Start stream
        return $this->localDownload->stream($params);
    }

    public function readMedia($media): array
    {
        $filePath = '';
        if (isset($media['information']['storage']['s3'])) {
            // Set stream params
            $params = [
                'Key'    => $media['information']['storage']['s3']['Key'],
                'Bucket' => $media['information']['storage']['s3']['Bucket'],
            ];

            // Get download and file path
            $filePath = $this->s3Storage->getFilePath($params);
        } elseif (isset($media['information']['storage']['local']['file_path'])) {
            $filePath = $this->localStorage->getFilePath($media);
        }

        // Read file
        $fileReader = new FileReader($filePath);
        $fileData   = $fileReader->readFile();

        // Check file content and set it to result
        if ($fileData['result'] === true && isset($fileData['data']) && !empty($fileData['data'])) {
            return $fileData['data'];
        }
        return [];
    }

    public function importFromS3($authorization, $bucketName): array
    {
        $list = $this->s3Service->getFilesFromBucket($bucketName);
        foreach ($list['data'] as $value) {

            // Get file info
            $fileInfo = pathinfo($value['Key']);

            // Set storage params
            $addStorage = [
                'title'       => $value['Key'],
                'user_id'     => $authorization['user_id'],
                'company_id'  => $authorization['company_id'] ?? 0,
                'category_id' => 0,
                'access'      => 'company',
                'storage'     => $this->storage,
                'type'        => $this->s3Storage->makeFileType(strtolower($fileInfo['extension'])),
                'extension'   => strtolower($fileInfo['extension']),
                'status'      => 1,
                'size'        => $value['Size'],
                'time_create' => time(),
                'time_update' => time(),
                'information' => json_encode(
                    [
                        'storage'  => [
                            's3'             => [
                                'Key'          => $value['Key'],
                                'Bucket'       => $value['Bucket'],
                                'fileRequest'  => $value['Key'],
                                'effectiveUri' => '',
                            ],
                            'original_name'  => $value['Key'],
                            'file_name'      => $value['Key'],
                            'file_title'     => $fileInfo['filename'],
                            'file_extension' => strtolower($fileInfo['extension']),
                            'file_size'      => $value['Size'],
                            'file_type'      => $this->s3Storage->makeFileType(strtolower($fileInfo['extension'])),
                            'file_size_view' => $this->s3Storage->transformSize($value['Size']),
                        ],
                        'category' => $params['category'] ?? [],
                        'review'   => [],
                        'history'  => [
                            [
                                'action'  => 'add',
                                'user_id' => $authorization['user_id'],
                                'data'    => [
                                    'title'       => $value['Key'],
                                    'type'        => $this->s3Storage->makeFileType(strtolower($fileInfo['extension'])),
                                    'extension'   => strtolower($fileInfo['extension']),
                                    'status'      => 1,
                                    'size'        => $value['Size'],
                                    'time_update' => time(),
                                ],
                            ],
                        ],
                    ],
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK
                ),
            ];

            $this->mediaRepository->addMedia($addStorage);

        }
        return ['message' => 'ok'];
    }

    public function isDuplicated($slug): bool
    {
        return (bool)$this->mediaRepository->duplicatedMedia(
            [
                'slug' => $slug,
            ]
        );
    }

    public function dashboard($params): array
    {
        $storage  = $this->calculateStorage($params);
        $analytic = $this->analytic($params);

        // Set list params
        $listParams = [
            'limit'      => 10,
            'access'     => 'company',
            'company_id' => $params['company_id'],
        ];

        // Check is admin
        if (isset($params['user_id']) && !empty($params['user_id'])) {
            $listParams['user_id'] = $params['user_id'];
        }

        // Get list
        $list   = [];
        $rowSet = $this->mediaRepository->getMediaList($listParams);
        foreach ($rowSet as $row) {
            $list[] = $this->canonizeStorageLight($row);
        }

        return [
            'storage'  => $storage,
            'analytic' => $analytic,
            'list'     => $list,
        ];
    }

    public function analytic($params): array
    {
        $result = $this->mediaRepository->analytic($params);
        foreach ($result as $row) {
            $this->defaultTypes[$row['type']]['value'] = $row['count'];
        }

        return array_values($this->defaultTypes);
    }

    public function calculateStorage($params): array
    {
        $storage         = $this->mediaRepository->calculateStorage($params);
        $maxStorage      = 1 * 1024 * 1024 * 1024;
        $usagePercentage = $maxStorage > 0 ? round(($storage['used_bytes'] / $maxStorage) * 100, 2) : 0;

        return [
            'file_count'       => (int)$storage['file_count'] ?? 0,
            'used_bytes'       => (int)$storage['used_bytes'] ?? 0,
            'max_bytes'        => (int)$maxStorage,
            'used_readable'    => $this->localStorage->transformSize((int)$storage['used_bytes'] ?? 0),
            'max_readable'     => $this->localStorage->transformSize($maxStorage),
            'usage_percentage' => $usagePercentage,
        ];
    }

    public function canonizeStorage($storage, $options = []): array
    {
        if (empty($storage)) {
            return [];
        }

        if (isset($options['view']) && $options['view'] == 'compressed') {
            return $this->canonizeStorageCompressed($storage);
        }
        if (isset($options['view']) && $options['view'] == 'light') {
            return $this->canonizeStorageCompressed($storage);
        }

        if (is_object($storage)) {
            $storage = [
                'id'             => $storage->getId(),
                'slug'           => $storage->getSlug(),
                'title'          => $storage->getTitle(),
                'user_id'        => $storage->getUserId(),
                'company_id'     => $storage->getCompanyId(),
                'category_id'    => $storage->getCategoryId(),
                'access'         => $storage->getAccess(),
                'storage'        => $storage->getStorage(),
                'type'           => $storage->getType(),
                'extension'      => $storage->getExtension(),
                'size'           => $storage->getSize(),
                'download_count' => $storage->getDownloadCount(),
                'status'         => $storage->getStatus(),
                'time_create'    => $storage->getTimeCreate(),
                'time_update'    => $storage->getTimeUpdate(),
                'information'    => $storage->getInformation(),
                'user_identity'  => $storage->getUserIdentity(),
                'user_name'      => $storage->getUserName(),
                'user_email'     => $storage->getUserEmail(),
                'user_mobile'    => $storage->getUserMobile(),
            ];
        } else {
            $storage = [
                'id'             => $storage['id'],
                'slug'           => $storage['slug'],
                'title'          => $storage['title'],
                'user_id'        => $storage['user_id'],
                'company_id'     => $storage['company_id'],
                'category_id'    => $storage['category_id'],
                'access'         => $storage['access'],
                'storage'        => $storage['storage'],
                'type'           => $storage['type'],
                'extension'      => $storage['extension'],
                'size'           => $storage['size'],
                'download_count' => $storage['download_count'],
                'status'         => $storage['status'],
                'time_create'    => $storage['time_create'],
                'time_update'    => $storage['time_update'],
                'information'    => $storage['information'],
                'user_identity'  => $storage['user_identity'],
                'user_name'      => $storage['user_name'],
                'user_email'     => $storage['user_email'],
                'user_mobile'    => $storage['user_mobile'],
            ];
        }

        // Set time view
        $storage['time_create_view'] = $this->utilityService->date($storage['time_create']);
        $storage['time_update_view'] = $this->utilityService->date($storage['time_update']);

        // Set information
        $storage['information'] = json_decode($storage['information'], true);

        // Set original name
        $storage['original_name'] = $storage['information']['storage']['original_name'] ?? '';

        // Set size view
        $storage['size_view'] = $storage['information']['storage']['file_size_view'];

        // Set private uri
        if (in_array($storage['access'], ['private', 'company', 'group', 'admin'])) {
            $storage['information']['download']['private_uri'] = $this->localDownload->makePrivateUrl($storage);
        }

        // Set AI data if not set
        // ToDo: remove it when all media updated
        $storage['information']['ai'] = $storage['information']['ai'] ?? null;

        // Clean up
        if (isset($options['view']) && in_array($options['view'], ['light', 'limited', 'compressed'])) {

            // Bucket and Key
            if ($storage['storage'] == 's3') {
                $storage['Key']    = $storage['information']['storage']['s3']['Key'];
                $storage['Bucket'] = $storage['information']['storage']['s3']['Bucket'];
            }

            unset($storage['information']['storage']);
        }

        return $storage;
    }

    public function canonizeStorageCompressed($storage): array
    {
        if (is_object($storage)) {
            $information = $storage->getInformation();
            $storage     = [
                'id'    => $storage->getId(),
                'title' => $storage->getTitle(),
            ];
        } else {
            $information = $storage['information'];
            $storage     = [
                'id'    => $storage['id'],
                'title' => $storage['title'],
            ];
        }

        // Set information
        $information = json_decode($information, true);

        // Set original name
        $storage['original_name'] = $information['storage']['original_name'] ?? '';

        return $storage;
    }

    public function canonizeStorageLight($storage): array
    {
        if (is_object($storage)) {
            $information = $storage->getInformation();
            $storage     = [
                'id'             => $storage->getId(),
                'title'          => $storage->getTitle(),
                'type'           => $storage->getType(),
                'extension'      => $storage->getExtension(),
                'size'           => $storage->getSize(),
                'download_count' => $storage->getDownloadCount(),
                'category_id'    => $storage->getCategoryId(),
                'time_create'    => $storage->getTimeCreate(),
                'time_update'    => $storage->getTimeUpdate(),
            ];
        } else {
            $information = $storage['information'];
            $storage     = [
                'id'             => $storage['id'],
                'title'          => $storage['title'],
                'type'           => $storage['type'],
                'extension'      => $storage['extension'],
                'size'           => $storage['size'],
                'download_count' => $storage['download_count'],
                'category_id'    => $storage['category_id'],
                'time_create'    => $storage['time_create'],
                'time_update'    => $storage['time_update'],
            ];
        }

        // Set information
        $information = json_decode($information, true);

        // Set original name
        $storage['original_name'] = $information['storage']['original_name'] ?? '';

        // Set time view
        $storage['time_create_view'] = $this->utilityService->date($storage['time_create']);
        $storage['time_update_view'] = $this->utilityService->date($storage['time_update']);

        // Set size view
        $storage['size_view'] = $information['storage']['file_size_view'];

        return $storage;
    }

    public function canonizeRelation($relation): array
    {
        if (empty($relation)) {
            return [];
        }

        if (is_object($relation)) {
            $relation = [
                'id'               => $relation->getId(),
                'storage_id'       => $relation->getStorageId(),
                'user_id'          => $relation->getUserId(),
                'company_id'       => $relation->getCompanyId(),
                'access'           => $relation->getAccess(),
                'relation_module'  => $relation->getRelationModule(),
                'relation_section' => $relation->getRelationSection(),
                'relation_item'    => $relation->getRelationItem(),
                'status'           => $relation->getStatus(),
                'time_create'      => $relation->getTimeCreate(),
                'time_update'      => $relation->getTimeUpdate(),
                'information'      => $relation->getInformation(),
            ];
        } else {
            $relation = [
                'id'               => $relation['id'],
                'storage_id'       => $relation['storage_id'],
                'user_id'          => $relation['user_id'],
                'company_id'       => $relation['company_id'],
                'access'           => $relation['access'],
                'relation_module'  => $relation['relation_module'],
                'relation_section' => $relation['relation_section'],
                'relation_item'    => $relation['relation_item'],
                'status'           => $relation['status'],
                'time_create'      => $relation['time_create'],
                'time_update'      => $relation['time_update'],
                'information'      => $relation['information'],
            ];
        }

        // Set time view
        $relation['time_create_view'] = $this->utilityService->date($relation['time_create']);
        $relation['time_update_view'] = $this->utilityService->date($relation['time_update']);

        // Set information
        $relation['information'] = json_decode($relation['information'], true);

        return $relation;
    }
}