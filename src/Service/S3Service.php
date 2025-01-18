<?php

namespace Pi\Media\Service;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;

class S3Service implements ServiceInterface
{
    protected S3Client $s3Client;

    /* @var array */
    protected array $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function putFile($params): array
    {
        // Set s3 connection parameters
        $this->s3Client = new S3Client($this->config['s3']);

        // Create s3 client
        try {
            // Upload the file stream to s3
            $response = $this->s3Client->putObject([
                'Bucket'     => $params['Bucket'],
                'Key'        => $params['Key'],
                'SourceFile' => $params['SourceFile'],
                'ACL'        => $params['ACL'] ?? 'private',
                'Metadata'   => $params['Metadata'] ?? [],
            ]);

            return [
                'result' => true,
                'data'   => $response->toArray(),
                'error'  => [],
            ];
        } catch (AwsException $e) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'code'    => $e->getStatusCode(),
                    'message' => "Error retrieving objects from the bucket: " . $e->getMessage(),
                ],
            ];
        }
    }

    public function getFile($params): array
    {
        // Set s3 connection parameters
        $this->s3Client = new S3Client($this->config['s3']);

        // Create s3 client
        try {
            // Download the private file from s3
            $result = $this->s3Client->getObject([
                'Bucket' => $params['bucket'],
                'Key'    => $params['key'],
            ]);

            return [
                'result' => true,
                'data'   => $result,
                'error'  => [],
            ];
        } catch (AwsException $e) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'code'    => $e->getStatusCode(),
                    'message' => "Error retrieving objects from the bucket: " . $e->getMessage(),
                ],
            ];
        }
    }

    public function deleteFile($params): array
    {
        // Set s3 connection parameters
        $this->s3Client = new S3Client($this->config['s3']);

        // Create s3 client
        try {
            // Download the private file from s3
            $result = $this->s3Client->deleteObject([
                'Bucket' => $params['bucket'],
                'Key'    => $params['key'],
            ]);

            return [
                'result' => true,
                'data'   => $result,
                'error'  => [],
            ];
        } catch (AwsException $e) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'code'    => $e->getStatusCode(),
                    'message' => "Error retrieving objects from the bucket: " . $e->getMessage(),
                ],
            ];
        }
    }

    public function setOrGetBucket($bucketName, $policy = []): array
    {
        // Set s3 connection parameters
        $this->s3Client = new S3Client($this->config['s3']);

        try {
            $this->s3Client->headBucket([
                'Bucket' => $bucketName,
            ]);
        } catch (AwsException $e) {
            if ((int)$e->getStatusCode() === 404) {
                try {
                    // Create the bucket
                    $this->s3Client->createBucket([
                        'Bucket' => $bucketName,
                    ]);

                    // Wait until the bucket is created
                    $this->s3Client->waitUntil('BucketExists', [
                        'Bucket' => $bucketName,
                    ]);

                    // Configure the policy
                    if (!empty($policy)) {
                        $this->s3Client->putBucketPolicy([
                            'Bucket' => $bucketName,
                            'Policy' => json_encode($policy),
                        ]);
                    }
                } catch (AwsException $createException) {
                    return [
                        'result' => false,
                        'data'   => [],
                        'error'  => [
                            'code'    => $createException->getStatusCode(),
                            'message' => "Error creating bucket: " . $createException->getMessage(),
                        ],
                    ];
                }
            } else {
                return [
                    'result' => false,
                    'data'   => [],
                    'error'  => [
                        'code'    => $e->getStatusCode(),
                        'message' => "An error occurred: " . $e->getMessage(),
                    ],
                ];
            }
        }

        // Get data (objects) from the bucket
        try {
            $result = $this->s3Client->listObjects([
                'Bucket' => $bucketName,
            ]);

            return [
                'result' => true,
                'data'   => $result->toArray(),
                'error'  => [],
            ];
        } catch (AwsException $e) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'code'    => $e->getStatusCode(),
                    'message' => "Error retrieving objects from the bucket: " . $e->getMessage(),
                ],
            ];
        }
    }

    public function deleteBucket($bucketName): array
    {
        // Set s3 connection parameters
        $this->s3Client = new S3Client($this->config['s3']);

        // Check bucket is exist
        try {
            // Check
            $this->s3Client->headBucket([
                'Bucket' => $bucketName,
            ]);
        } catch (AwsException $e) {
            return [
                'result' => true,
                'data'   => [
                    'message' => 'Bucket dose not exist and delete not required !',
                ],
                'error'  => [],
            ];
        }

        // Delete bucket and data if exist
        try {
            // List objects in the bucket
            $objects = $this->s3Client->listObjects([
                'Bucket' => $bucketName,
            ]);

            // Delete objects if they exist
            if (isset($objects['Contents']) && !empty($objects['Contents'])) {
                $deleteObjects = [];
                foreach ($objects['Contents'] as $object) {
                    $deleteObjects['Objects'][] = [
                        'Key' => $object['Key'],
                    ];
                }

                $result = $this->s3Client->deleteObjects([
                    'Bucket' => $bucketName,
                    'Delete' => $deleteObjects,
                ]);

                // Check for any errors during deletion
                if (isset($result['Errors'])) {
                    foreach ($result['Errors'] as $error) {
                        error_log("Error deleting object: " . $error['Key'] . " - " . $error['Message']);
                    }
                }
            }

            // Delete the empty bucket
            $this->s3Client->deleteBucket([
                'Bucket' => $bucketName,
            ]);

            return [
                'result' => true,
                'data'   => [
                    'message' => 'Bucket and its contents deleted successfully',
                ],
                'error'  => [],
            ];
        } catch (AwsException $e) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'code'    => $e->getStatusCode(),
                    'message' => "Error deleting bucket: " . $e->getMessage(),
                ],
            ];
        }
    }
}