<?php

namespace Media\Repository;

use Laminas\Db\ResultSet\HydratingResultSet;
use Media\Model\Relation;
use Media\Model\Storage;

interface MediaRepositoryInterface
{
    public function getMediaList($params = []): HydratingResultSet;

    public function getMediaListByRelationList($params = []): HydratingResultSet;

    public function getMediaCount(array $params = []): int;

    public function getMediaListByRelationCount(array $params = []): int;

    public function getMedia(array $params = []): array|Storage;

    public function addMedia(array $params = []): array|Storage;

    public function updateMedia(int $mediaId, array $params = []): void;

    public function updateDownloadCount(int $mediaId): void;

    public function getMediaRelation(array $params = []): array|Relation;

    public function addMediaRelation(array $params = []): array|Relation;

    public function getMediaRelationList($params = []): HydratingResultSet;
}