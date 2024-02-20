<?php

namespace Media\Service;

use Media\Download\LocalDownload;
use Media\Repository\MediaRepositoryInterface;
use Media\Storage\LocalStorage;
use User\Service\AccountService;
use User\Service\UtilityService;

class MediaService implements ServiceInterface
{
    /** @var MediaRepositoryInterface */
    protected MediaRepositoryInterface $mediaRepository;

    /** @var AccountService */
    protected AccountService $accountService;

    /** @var UtilityService */
    protected UtilityService $utilityService;

    /** @var LocalStorage */
    protected LocalStorage $localStorage;

    /** @var LocalDownload */
    protected LocalDownload $localDownload;

    /* @var array */
    protected array $config;

    protected string $storage = 'local';

    public function __construct(
        MediaRepositoryInterface $mediaRepository,
        AccountService $accountService,
        UtilityService $utilityService,
        LocalStorage $localStorage,
        LocalDownload $localDownload,
        $config
    ) {
        $this->mediaRepository = $mediaRepository;
        $this->accountService  = $accountService;
        $this->utilityService  = $utilityService;
        $this->localStorage    = $localStorage;
        $this->localDownload   = $localDownload;
        $this->config          = $config;
    }

    public function storeMedia($uploadFile, $authorization, $params): array
    {
        // Set path
        switch ($params['access']) {
            case 'company':
                $path = sprintf('%s/%s/%s', $authorization['company']['hash'], date('Y'), date('m'));
                break;

            case 'user':
                $path = sprintf('%s/%s/%s', $authorization['user']['hash'], date('Y'), date('m'));
                break;

            default:
            case 'public':
                $path = sprintf('%s/%s', date('Y'), date('m'));
                break;
        }

        // Set storage params
        $storageParams = [
            'storage'    => $this->storage,
            'access'     => $params['access'],
            'local_path' => $path,
        ];

        // Store media
        return $this->localStorage->storeMedia($uploadFile, $storageParams);
    }

    public function addMedia($uploadFile, $authorization, $params): array
    {
        // Store media
        $storeInfo = $this->storeMedia($uploadFile, $authorization, $params);

        // Save and return
        return $this->saveMedia($authorization, $params, $storeInfo);
    }

    public function createMedia($authorization, $params, $storageParams): array
    {
        // Store media
        $storeInfo = $this->localStorage->createMedia($storageParams);

        // Save and return
        return $this->saveMedia($authorization, $params, $storeInfo);
    }

    public function saveMedia($authorization, $params, $storeInfo): array
    {
        // Make download info
        $downloadInfo = [
            'public_uri' => ($params['access'] == 'public') ? $this->localDownload->makePublicUri($storeInfo) : '',
        ];

        // Set storage params
        $addStorage = [
            'title'       => $params['title'] ?? $storeInfo['file_title'],
            'user_id'     => $authorization['user_id'],
            'company_id'  => $authorization['company_id'],
            'access'      => $params['access'],
            'storage'     => $this->storage,
            'type'        => $this->localStorage->makeFileType($storeInfo['file_extension']),
            'extension'   => $storeInfo['file_extension'],
            'status'      => 1,
            'size'        => $storeInfo['file_size'],
            'time_create' => time(),
            'time_update' => time(),
            'information' => json_encode(
                [
                    'storage'  => $storeInfo,
                    'download' => $downloadInfo,
                    'category' => $params['category'] ?? [],
                    'review'   => (isset($params['review']) && !empty($params['review'])) ? [$params['review']] : [],
                    'history'  => [
                        [
                            'action'  => 'add',
                            'storage' => $storeInfo,
                            'user_id' => $authorization['user_id'],
                            'data'    => [
                                'title'       => $params['title'] ?? $storeInfo['file_title'],
                                'type'        => $this->localStorage->makeFileType($storeInfo['file_extension']),
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

    public function getMediaList($authorization, $params): array
    {
        if (!in_array($authorization['access'], ['user', 'company', 'admin'])) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message' => 'Please select the true access type !',
                ],
            ];
        }

        $view   = (isset($params['view']) && in_array($params['view'], ['limited', 'compressed'])) ? $params['view'] : 'limited';
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
        } elseif ($authorization['access'] == 'user') {
            $listParams['access']  = 'user';
            $listParams['user_id'] = $authorization['user_id'];
        } elseif ($authorization['access'] == 'admin') {
            if (isset($params['user_id']) && !empty($params['user_id'])) {
                $listParams['user_id'] = $params['user_id'];
            }
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
        if (isset($params['relation_module']) && !empty($params['relation_module']) &&
            isset($params['relation_section']) && !empty($params['relation_section']) &&
            isset($params['relation_item']) && !empty($params['relation_item'])
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

            if (!empty($list) && $view != 'compressed') {
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

    public function getMedia($params): array
    {
        $media = $this->mediaRepository->getMedia(['id' => $params['id']]);
        return $this->canonizeStorage($media);
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

        // Set download uri
        $downloadInfo = [
            'public_uri' => ($params['access'] == 'public') ? $this->localDownload->makePublicUri($storeInfo) : '',
        ];

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
            $updateParams['type']      = $this->localStorage->makeFileType($storeInfo['file_extension']);
        }
        if (isset($storeInfo['file_size']) && !empty($storeInfo['file_size'])) {
            $updateParams['size'] = $storeInfo['file_size'];
        }

        // Set information
        $information             = $media['information'];
        $information['download'] = $downloadInfo;
        if (isset($storeInfo) && !empty($storeInfo)) {
            $information['storage'] = $storeInfo;
        }
        if (isset($params['category']) && !empty($params['category'])) {
            $information['category'] = $params['category'];
        }
        if (isset($params['review']) && !empty($params['review'])) {
            $information['review'][] = $params['review'];
        }

        // Set history
        $information['history'][] = [
            'action'  => 'update',
            'storage' => $storeInfo,
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

        // Set information
        $information = $media['information'];
        if (isset($params['category']) && !empty($params['category'])) {
            $information['category'] = $params['category'];
        }
        if (isset($params['review']) && !empty($params['review'])) {
            $information['review'][] = $params['review'];
        }

        // Set history
        $information['history'][] = [
            'action'  => 'update',
            'storage' => [],
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

    public function streamMedia($media): string
    {
        // Update download count
        $this->mediaRepository->updateDownloadCount((int)$media['id']);

        // Set options
        $options = [];
        if (isset($media['information']['storage']['original_name'])) {
            $options['filename'] = $media['information']['storage']['original_name'];
        }
        if (isset($media['information']['storage']['file_extension'])) {
            $options['content_type'] = $media['information']['storage']['file_extension'];
        }

        // Start stream
        return $this->localDownload->stream($media['information']['storage']['file_path'], $options);
    }

    public function streamFile($filePath, $options = []): string
    {
        // Start stream
        return $this->localDownload->stream($filePath, $options);
    }

    public function canonizeStorage($storage, $options = []): array
    {
        if (empty($storage)) {
            return [];
        }

        if (isset($options['view']) && $options['view'] == 'compressed') {
            return $this->canonizeStorageCompressed($storage);
        }

        if (is_object($storage)) {
            $storage = [
                'id'             => $storage->getId(),
                'slug'           => $storage->getSlug(),
                'title'          => $storage->getTitle(),
                'user_id'        => $storage->getUserId(),
                'company_id'     => $storage->getCompanyId(),
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
        $storage['size_view'] = $this->localStorage->transformSize($storage['size']);

        // Set private uri
        if (in_array($storage['access'], ['user', 'company'])) {
            $storage['information']['download']['private_uri'] = $this->localDownload->makePrivateUrl($storage);
        }

        // Clean up
        if (isset($options['view']) && $options['view'] == 'limited') {
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