<?php

namespace Media\Repository;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\ResultSet\HydratingResultSet;
use Laminas\Db\Sql\Delete;
use Laminas\Db\Sql\Insert;
use Laminas\Db\Sql\Predicate\Expression;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Update;
use Laminas\Hydrator\HydratorInterface;
use Media\Model\Relation;
use Media\Model\Storage;
use RuntimeException;

class MediaRepository implements MediaRepositoryInterface
{
    private string $tableStorage = 'media_storage';

    private string $tableRelation = 'media_relation';

    private string $tableAccount = 'user_account';

    private AdapterInterface $db;

    private HydratorInterface $hydrator;

    private Storage  $storagePrototype;
    private Relation $relationPrototype;

    public function __construct(
        AdapterInterface  $db,
        HydratorInterface $hydrator,
        Storage           $storagePrototype,
        Relation          $relationPrototype
    ) {
        $this->db                = $db;
        $this->hydrator          = $hydrator;
        $this->storagePrototype  = $storagePrototype;
        $this->relationPrototype = $relationPrototype;
    }

    public function getMediaList($params = []): HydratingResultSet
    {
        $where = [];
        if (isset($params['access']) && !empty($params['access'])) {
            $where['access'] = $params['access'];
        }
        if (isset($params['status']) && !empty($params['status'])) {
            $where['status'] = $params['status'];
        }
        if (isset($params['company_id']) && !empty($params['company_id'])) {
            $where['company_id'] = $params['company_id'];
        }
        if (isset($params['user_id']) && !empty($params['user_id'])) {
            $where['user_id'] = $params['user_id'];
        }
        if (isset($params['slug']) && !empty($params['slug'])) {
            $where['slug'] = $params['slug'];
        }
        if (isset($params['id']) && !empty($params['id'])) {
            $where['id'] = $params['id'];
        }

        $sql    = new Sql($this->db);
        $from   = ['storage' => $this->tableStorage];
        $select = $sql->select()->from($from)->where($where)->order($params['order'])->offset($params['offset'])->limit($params['limit']);
        $select->join(
            ['account' => $this->tableAccount],
            'storage.user_id=account.id',
            [
                'user_identity' => 'identity',
                'user_name'     => 'name',
                'user_email'    => 'email',
                'user_mobile'   => 'mobile',
            ],
            $select::JOIN_LEFT . ' ' . $select::JOIN_OUTER
        );
        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
            return [];
        }

        $resultSet = new HydratingResultSet($this->hydrator, $this->storagePrototype);
        $resultSet->initialize($result);

        return $resultSet;
    }

    public function getMediaListByRelationList($params = []): HydratingResultSet
    {
        $where = [
            'relation.relation_module'  => $params['relation_module'],
            'relation.relation_section' => $params['relation_section'],
            'relation.relation_item'    => $params['relation_item'],
        ];
        if (isset($params['access']) && !empty($params['access'])) {
            $where['storage.access'] = $params['access'];
        }
        if (isset($params['status']) && !empty($params['status'])) {
            $where['storage.status'] = $params['status'];
        }
        if (isset($params['company_id']) && !empty($params['company_id'])) {
            $where['storage.company_id'] = $params['company_id'];
        }
        if (isset($params['user_id']) && !empty($params['user_id'])) {
            $where['storage.user_id'] = $params['user_id'];
        }
        if (isset($params['slug']) && !empty($params['slug'])) {
            $where['storage.slug'] = $params['slug'];
        }
        if (isset($params['id']) && !empty($params['id'])) {
            $where['storage.id'] = $params['id'];
        }

        $sql    = new Sql($this->db);
        $from   = ['storage' => $this->tableStorage];
        $select = $sql->select()->from($from)->where($where)->order($params['order'])->offset($params['offset'])->limit($params['limit']);
        $select->join(
            ['relation' => $this->tableRelation],
            'storage.id=relation.storage_id',
            [
                'relation_module',
                'relation_section',
                'relation_item',
            ],
            $select::JOIN_LEFT . ' ' . $select::JOIN_OUTER
        );
        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
            return [];
        }

        $resultSet = new HydratingResultSet($this->hydrator, $this->storagePrototype);
        $resultSet->initialize($result);

        return $resultSet;
    }

    public function getMediaCount(array $params = []): int
    {
        // Set where
        $columns = ['count' => new Expression('count(*)')];
        $where   = [];
        if (isset($params['access']) && !empty($params['access'])) {
            $where['access'] = $params['access'];
        }
        if (isset($params['status']) && !empty($params['status'])) {
            $where['status'] = $params['status'];
        }
        if (isset($params['company_id']) && !empty($params['company_id'])) {
            $where['company_id'] = $params['company_id'];
        }
        if (isset($params['user_id']) && !empty($params['user_id'])) {
            $where['user_id'] = $params['user_id'];
        }
        if (isset($params['slug']) && !empty($params['slug'])) {
            $where['slug'] = $params['slug'];
        }
        if (isset($params['id']) && !empty($params['id'])) {
            $where['id'] = $params['id'];
        }

        $sql       = new Sql($this->db);
        $select    = $sql->select($this->tableStorage)->columns($columns)->where($where);
        $statement = $sql->prepareStatementForSqlObject($select);
        $row       = $statement->execute()->current();

        return (int)$row['count'];
    }

    public function getMediaListByRelationCount(array $params = []): int
    {
        // Set where
        $columns = ['count' => new Expression('count(*)')];

        $where = [
            'relation.relation_module'  => $params['relation_module'],
            'relation.relation_section' => $params['relation_section'],
            'relation.relation_item'    => $params['relation_item'],
        ];
        if (isset($params['access']) && !empty($params['access'])) {
            $where['storage.access'] = $params['access'];
        }
        if (isset($params['status']) && !empty($params['status'])) {
            $where['storage.status'] = $params['status'];
        }
        if (isset($params['company_id']) && !empty($params['company_id'])) {
            $where['storage.company_id'] = $params['company_id'];
        }
        if (isset($params['user_id']) && !empty($params['user_id'])) {
            $where['storage.user_id'] = $params['user_id'];
        }
        if (isset($params['slug']) && !empty($params['slug'])) {
            $where['storage.slug'] = $params['slug'];
        }
        if (isset($params['id']) && !empty($params['id'])) {
            $where['storage.id'] = $params['id'];
        }

        $sql    = new Sql($this->db);
        $from   = ['storage' => $this->tableStorage];
        $select = $sql->select()->from($from)->columns($columns)->where($where);
        $select->join(
            ['relation' => $this->tableRelation],
            'storage.id=relation.storage_id',
            [
                'relation_module',
                'relation_section',
                'relation_item',
            ],
            $select::JOIN_LEFT . ' ' . $select::JOIN_OUTER
        );
        $statement = $sql->prepareStatementForSqlObject($select);
        $row       = $statement->execute()->current();

        return (int)$row['count'];
    }

    public function addMedia(array $params = []): array|Storage
    {
        $insert = new Insert($this->tableStorage);
        $insert->values($params);

        $sql       = new Sql($this->db);
        $statement = $sql->prepareStatementForSqlObject($insert);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface) {
            throw new RuntimeException(
                'Database error occurred during blog post insert operation'
            );
        }

        $id = $result->getGeneratedValue();

        return $this->getMedia(['id' => $id]);
    }

    public function getMedia(array $params = []): array|Storage
    {
        // Set
        $where = ['id' => (int)$params['id']];

        $sql       = new Sql($this->db);
        $select    = $sql->select($this->tableStorage)->where($where);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
            throw new RuntimeException(
                'Failed retrieving row with identifier; unknown database error.',
            );
        }

        $resultSet = new HydratingResultSet($this->hydrator, $this->storagePrototype);
        $resultSet->initialize($result);
        $storage = $resultSet->current();

        if (!$storage) {
            return [];
        }

        return $storage;
    }

    public function updateMedia(int $mediaId, array $params = []): void
    {
        $update = new Update($this->tableStorage);
        $update->set($params);
        $update->where(['id' => $mediaId]);

        $sql       = new Sql($this->db);
        $statement = $sql->prepareStatementForSqlObject($update);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface) {
            throw new RuntimeException(
                'Database error occurred during update operation'
            );
        }
    }

    public function deleteMedia(int $mediaId, array $params = []): void
    {
        // Set where
        $where = ['id' => $mediaId];

        // Delete from role table
        $delete = new Delete($this->tableStorage);
        $delete->where($where);

        $sql       = new Sql($this->db);
        $statement = $sql->prepareStatementForSqlObject($delete);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface) {
            throw new RuntimeException(
                'Database error occurred during update operation'
            );
        }
    }

    public function updateDownloadCount(int $mediaId): void
    {
        $update = new Update($this->tableStorage);
        $update->set(['download_count' => new Expression('download_count + 1')]);
        $update->where(['id' => $mediaId]);

        $sql       = new Sql($this->db);
        $statement = $sql->prepareStatementForSqlObject($update);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface) {
            throw new RuntimeException(
                'Database error occurred during update operation'
            );
        }
    }

    public function duplicatedMedia(array $params = []): int
    {
        // Set where
        $columns = ['count' => new Expression('count(*)')];
        $where   = ['slug' => $params['slug']];
        if (isset($params['id']) && (int)$params['id'] > 0) {
            $where['id <> ?'] = $params['id'];
        }

        $sql       = new Sql($this->db);
        $select    = $sql->select($this->tableStorage)->columns($columns)->where($where);
        $statement = $sql->prepareStatementForSqlObject($select);
        $row       = $statement->execute()->current();

        return (int)$row['count'];
    }

    public function addMediaRelation(array $params = []): array|Relation
    {
        $insert = new Insert($this->tableRelation);
        $insert->values($params);

        $sql       = new Sql($this->db);
        $statement = $sql->prepareStatementForSqlObject($insert);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface) {
            throw new RuntimeException(
                'Database error occurred during blog post insert operation'
            );
        }

        $id = $result->getGeneratedValue();

        return $this->getMediaRelation(['id' => $id]);
    }

    public function getMediaRelation(array $params = []): array|Relation
    {
        // Set
        $where = ['id' => (int)$params['id']];

        $sql       = new Sql($this->db);
        $select    = $sql->select($this->tableRelation)->where($where);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
            throw new RuntimeException(
                'Failed retrieving row with identifier; unknown database error.',
            );
        }

        $resultSet = new HydratingResultSet($this->hydrator, $this->relationPrototype);
        $resultSet->initialize($result);
        $relation = $resultSet->current();

        if (!$relation) {
            return [];
        }

        return $relation;
    }

    public function getMediaRelationList($params = []): HydratingResultSet
    {
        $where = [];
        if (isset($params['storage_id']) && !empty($params['storage_id'])) {
            $where['storage_id'] = $params['storage_id'];
        }
        if (isset($params['status']) && !empty($params['status'])) {
            $where['status'] = $params['status'];
        }
        if (isset($params['company_id']) && !empty($params['company_id'])) {
            $where['company_id'] = $params['company_id'];
        }
        if (isset($params['user_id']) && !empty($params['user_id'])) {
            $where['user_id'] = $params['user_id'];
        }
        if (isset($params['id']) && !empty($params['id'])) {
            $where['id'] = $params['id'];
        }

        $sql       = new Sql($this->db);
        $select    = $sql->select($this->tableRelation)->where($where);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
            return [];
        }

        $resultSet = new HydratingResultSet($this->hydrator, $this->relationPrototype);
        $resultSet->initialize($result);

        return $resultSet;
    }

    public function deleteMediaRelation(int $mediaId): void
    {
        // Set where
        $where = ['storage_id' => $mediaId];

        // Delete from role table
        $delete = new Delete($this->tableRelation);
        $delete->where($where);

        $sql       = new Sql($this->db);
        $statement = $sql->prepareStatementForSqlObject($delete);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface) {
            throw new RuntimeException(
                'Database error occurred during update operation'
            );
        }
    }

    public function analytic($params): array|ResultInterface
    {
        $columns = [
            'type',
            'count' => new Expression('count(*)'),
        ];

        $where = [
            'company_id' => $params['company_id'],
            'status'     => 1,
        ];

        $sql       = new Sql($this->db);
        $select    = $sql->select($this->tableStorage)->columns($columns)->where($where)->group('type');
        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();

        if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
            return [];
        }

        return $result;
    }

    public function calculateStorage(array $params = []): int
    {
        $columns = ['sum' => new Expression('SUM(size)')];
        $where   = [];
        if (isset($params['company_id']) && !empty($params['company_id'])) {
            $where['company_id'] = $params['company_id'];
        }
        if (isset($params['user_id']) && !empty($params['user_id'])) {
            $where['user_id'] = $params['user_id'];
        }

        $sql       = new Sql($this->db);
        $select    = $sql->select($this->tableStorage)->columns($columns)->where($where);
        $statement = $sql->prepareStatementForSqlObject($select);
        $row       = $statement->execute()->current();

        return $row['sum'];
    }
}