<?php

namespace Pi\Media\Service;

use Google\Cloud\Core\Exception\GoogleException;
use Google\Cloud\Storage\StorageClient;

class GCSService implements ServiceInterface
{
    protected StorageClient $storageClient;
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->storageClient = new StorageClient([
            'keyFilePath' => $config['gcs']['keyFilePath'],
            'projectId'   => $config['gcs']['projectId'],
        ]);
    }

    public function putFile(array $params): array
    {
        try {
            $bucket = $this->storageClient->bucket($params['Bucket']);

            if (!$bucket->exists()) {
                return ['result' => false, 'error' => ['message' => 'Bucket does not exist']];
            }

            $object = $bucket->upload(
                $params['Body'],
                [
                    'name' => $params['Key'],
                    'metadata' => $params['Metadata'] ?? [],
                    'predefinedAcl' => ($params['ACL'] ?? 'private') === 'public' ? 'publicRead' : 'private',
                ]
            );

            return [
                'result' => true,
                'data' => $object->info(),
                'error' => [],
            ];
        } catch (GoogleException $e) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'code'    => $e->getCode(),
                    'message' => $e->getMessage(),
                ],
            ];
        }
    }

    public function getFile(array $params): array
    {
        try {
            $bucket = $this->storageClient->bucket($params['Bucket']);
            $object = $bucket->object($params['Key']);

            if (!$object->exists()) {
                return ['result' => false, 'error' => ['message' => 'Object not found']];
            }

            return [
                'result' => true,
                'data'   => ['Body' => $object->downloadAsString()],
                'error'  => [],
            ];
        } catch (GoogleException $e) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'code'    => $e->getCode(),
                    'message' => $e->getMessage(),
                ],
            ];
        }
    }

    public function deleteFile(array $params): array
    {
        try {
            $bucket = $this->storageClient->bucket($params['Bucket']);
            $object = $bucket->object($params['Key']);

            if ($object->exists()) {
                $object->delete();
            }

            return ['result' => true, 'data' => [], 'error' => []];
        } catch (GoogleException $e) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'code'    => $e->getCode(),
                    'message' => $e->getMessage(),
                ],
            ];
        }
    }

    public function setOrGetBucket(string $bucketName, array $policy = []): array
    {
        try {
            $bucket = $this->storageClient->bucket($bucketName);
            if (!$bucket->exists()) {
                $bucket = $this->storageClient->createBucket($bucketName, [
                    'location' => $this->config['gcs']['location'] ?? 'US',
                    'storageClass' => $this->config['gcs']['storageClass'] ?? 'STANDARD',
                ]);

                if (!empty($policy)) {
                    // Note: Google doesn't use stringified policy like S3. Policies are managed via IAM.
                    // This is just a placeholder.
                }
            }

            $objects = [];
            foreach ($bucket->objects() as $object) {
                $objects[] = $object->name();
            }

            return [
                'result' => true,
                'data'   => $objects,
                'error'  => [],
            ];
        } catch (GoogleException $e) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'code'    => $e->getCode(),
                    'message' => $e->getMessage(),
                ],
            ];
        }
    }

    public function deleteBucket(string $bucketName): array
    {
        try {
            $bucket = $this->storageClient->bucket($bucketName);

            if (!$bucket->exists()) {
                return [
                    'result' => true,
                    'data'   => ['message' => 'Bucket does not exist, no deletion needed'],
                    'error'  => [],
                ];
            }

            foreach ($bucket->objects() as $object) {
                $object->delete();
            }

            $bucket->delete();

            return [
                'result' => true,
                'data'   => ['message' => 'Bucket and its contents deleted'],
                'error'  => [],
            ];
        } catch (GoogleException $e) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'code'    => $e->getCode(),
                    'message' => $e->getMessage(),
                ],
            ];
        }
    }

    public function getFilesFromBucket(string $bucketName): array
    {
        try {
            $bucket = $this->storageClient->bucket($bucketName);

            if (!$bucket->exists()) {
                return [
                    'result' => true,
                    'data'   => ['message' => 'Bucket does not exist'],
                    'error'  => [],
                ];
            }

            $objectList = [];
            foreach ($bucket->objects() as $object) {
                $info = $object->info();
                $objectList[] = [
                    'Bucket' => $bucketName,
                    'Key'    => $object->name(),
                    'Size'   => $info['size'] ?? 0,
                ];
            }

            return [
                'result' => true,
                'data'   => $objectList,
                'error'  => [],
            ];
        } catch (GoogleException $e) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'code'    => $e->getCode(),
                    'message' => $e->getMessage(),
                ],
            ];
        }
    }
}
