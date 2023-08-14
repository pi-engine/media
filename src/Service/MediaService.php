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

    /**
     * @throws \Exception
     */
    public function addMedia($uploadFile, $authorization, $params): array
    {
        // Set storage params
        $storageParams = [
            'local_path' => isset($authorization['company']['hash'])
                ? sprintf('%s/%s/%s', $authorization['company']['hash'], date('Y'), date('m'))
                : sprintf('%s/%s', date('Y'), date('m')),
            'access'     => $params['access'],
            'storage'    => 'local',
        ];

        // Store media
        $storeInfo = $this->localStorage->storeMedia($uploadFile, $storageParams);

        // Make download info
        $downloadInfo = [
            'public_uri' => ($params['access'] == 'public') ? $this->localDownload->makePublicUri($storeInfo) : '',
        ];

        // Set storage params
        $addStorage = [
            'title'       => $params['title'] ?? $storeInfo['file_title'],
            'user_id'     => $authorization['user_id'] ?? $authorization['id'],
            'company_id'  => $authorization['company_id'] ?? 0,
            'access'      => $params['access'],
            'storage'     => 'local',
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
                ],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            ),
        ];

        // Save storage
        $storage = $this->mediaRepository->addMedia($addStorage);
        $storage = $this->canonizeStorage($storage, ['view' => 'limited']);

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
                'user_id'          => $authorization['user_id'] ?? $authorization['id'],
                'company_id'       => $authorization['company_id'] ?? 0,
                'access'           => $params['access'],
                'relation_module'  => $params['relation_module'],
                'relation_section' => $params['relation_section'],
                'relation_item'    => (int)$params['relation_item'],
                'status'           => 1,
                'time_create'      => time(),
                'time_update'      => time(),
            ];

            // Save relation
            $relation              = $this->mediaRepository->addMediaRelation($addRelation);
            $storage['relation'][] = $this->canonizeRelation($relation);
        }

        return $storage;
    }

    public function updateMedia($authorization, $params)
    {
        return $params;
    }

    public function getMediaList($authorization, $params): array
    {
        $limit  = (int)($params['limit'] ?? 25);
        $page   = (int)($params['page'] ?? 1);
        $order  = $params['order'] ?? ['time_create DESC'];
        $offset = ($page - 1) * $limit;

        $listParams = [
            'order'      => $order,
            'offset'     => $offset,
            'limit'      => $limit,
            'access'     => 'company',
            'company_id' => $authorization['company_id'],
        ];
        if (!empty($params['status'])) {
            $listParams['status'] = $params['status'];
        }
        if (!empty($params['user_id'])) {
            $listParams['user_id'] = $params['user_id'];
        }
        if (!empty($params['slug'])) {
            $listParams['slug'] = $params['slug'];
        }
        if (!empty($params['id'])) {
            $listParams['id'] = $params['id'];
        }

        // Check request has relation information
        if (
            !empty($params['relation_module'])
            && !empty($params['relation_section'])
            && !empty($params['relation_item'])
        ) {
            $listParams['relation_module']  = $params['relation_module'];
            $listParams['relation_section'] = $params['relation_section'];
            $listParams['relation_item']    = $params['relation_item'];

            // Get list
            $list   = [];
            $rowSet = $this->mediaRepository->getMediaListByRelationList($listParams);
            foreach ($rowSet as $row) {
                $list[] = $this->canonizeStorage($row, ['view' => 'limited']);
            }

            // Get count
            $count = $this->mediaRepository->getMediaListByRelationCount($listParams);
        } else {
            // Get list
            $list   = [];
            $rowSet = $this->mediaRepository->getMediaList($listParams);
            foreach ($rowSet as $row) {
                $storage = $this->canonizeStorage($row, ['view' => 'limited']);
                unset($storage['information']['storage']);
                $list[] = $storage;
            }

            // Get count
            $count = $this->mediaRepository->getMediaCount($listParams);
        }

        return [
            'result' => true,
            'data'   => [
                'list'      => $list,
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

    /**
     * @throws \Exception
     */
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

    public function canonizeStorage($storage, $options = []): array
    {
        if (empty($storage)) {
            return [];
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
            ];
        }

        // Set information
        $storage['information'] = json_decode($storage['information'], true);

        // Set original name
        $storage['original_name'] = $storage['information']['storage']['original_name'];

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
            ];
        }

        return $relation;
    }
}