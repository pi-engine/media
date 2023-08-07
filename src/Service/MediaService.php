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
            'type'        => '',
            'extension'   => '',
            'status'      => 1,
            'time_create' => time(),
            'time_update' => time(),
            'information' => json_encode(
                [
                    'storage' => $storeInfo,
                    'download' => $downloadInfo,
                ],
                JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT
            ),
        ];

        // Save storage
        $storage = $this->mediaRepository->addMedia($addStorage);
        $storage = $this->canonizeStorage($storage);

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
                $list[] = $this->canonizeStorage($row);
            }

            // Get count
            $count = $this->mediaRepository->getMediaListByRelationCount($listParams);
        } else {
            // Get list
            $list   = [];
            $rowSet = $this->mediaRepository->getMediaList($listParams);
            foreach ($rowSet as $row) {
                $list[] = $this->canonizeStorage($row);
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

    /**
     * @throws \Exception
     */
    public function streamMedia($params): string
    {
        $source = '/var/www/html/local/laminas/data/upload/654cde20ead03f441e8a8aed6c2527d6/2023/08/screenshot_20230713_170927-2023-08-01-07-44-00-3595.png';
        return $this->localDownload->stream($source);
    }

    public function canonizeStorage($storage): array
    {
        if (empty($storage)) {
            return [];
        }

        if (is_object($storage)) {
            $storage = [
                'id'          => $storage->getId(),
                'slug'        => $storage->getSlug(),
                'title'       => $storage->getTitle(),
                'user_id'     => $storage->getUserId(),
                'company_id'  => $storage->getCompanyId(),
                'access'      => $storage->getAccess(),
                'storage'     => $storage->getStorage(),
                'type'        => $storage->getType(),
                'extension'   => $storage->getExtension(),
                'status'      => $storage->getStatus(),
                'time_create' => $storage->getTimeCreate(),
                'time_update' => $storage->getTimeUpdate(),
                'information' => $storage->getInformation(),
            ];
        } else {
            $storage = [
                'id'          => $storage['id'],
                'slug'        => $storage['slug'],
                'title'       => $storage['title'],
                'user_id'     => $storage['user_id'],
                'company_id'  => $storage['company_id'],
                'access'      => $storage['access'],
                'storage'     => $storage['storage'],
                'type'        => $storage['type'],
                'extension'   => $storage['extension'],
                'status'      => $storage['status'],
                'time_create' => $storage['time_create'],
                'time_update' => $storage['time_update'],
                'information' => $storage['information'],
            ];
        }

        $storage['information'] = json_decode($storage['information'], true);

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