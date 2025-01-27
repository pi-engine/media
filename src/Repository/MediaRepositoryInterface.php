<?php

namespace Pi\Media\Repository;

use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\ResultSet\HydratingResultSet;
use Pi\Media\Model\Relation;
use Pi\Media\Model\Storage;

interface MediaRepositoryInterface
{
    public function getMediaList($params = []): HydratingResultSet;

    public function getMediaListByRelationList($params = []): HydratingResultSet;

    public function getMediaCount(array $params = []): int;

    public function getMediaListByRelationCount(array $params = []): int;

    public function getMedia(array $params = []): array|Storage;

    public function addMedia(array $params = []): array|Storage;

    public function updateMedia(int $mediaId, array $params = []): void;

    public function deleteMedia(int $mediaId): void;

    public function updateDownloadCount(int $mediaId): void;

    public function duplicatedMedia(array $params = []): int;

    public function getMediaRelation(array $params = []): array|Relation;

    public function addMediaRelation(array $params = []): array|Relation;

    public function getMediaRelationList($params = []): HydratingResultSet;

    public function deleteMediaRelation(int $mediaId): void;

    public function analytic($params): array|ResultInterface;

    public function calculateStorage(array $params = []): array;
}