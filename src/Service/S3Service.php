<?php

namespace Pi\Media\Service;

use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;

class S3Service implements ServiceInterface
{
    protected S3Client $s3Client;

    /* @var array */
    protected array $config;

    public function __construct($config)
    {
        $this->s3Client = new S3Client($config['s3']);
        $this->config   = $config;
    }

    public function putFile($params): array
    {
        // Create s3 client
        try {
            // Upload the file stream to s3
            $response = $this->s3Client->putObject($params);

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
        // Create s3 client
        try {
            // Download the private file from s3
            $result = $this->s3Client->getObject([
                'Bucket' => $params['Bucket'],
                'Key'    => $params['Key'],
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
        // Create s3 client
        try {
            // Download the private file from s3
            $result = $this->s3Client->deleteObject([
                'Bucket' => $params['Bucket'],
                'Key'    => $params['Key'],
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
        $result = [];
        try {
            $result = $this->s3Client->listObjectsV2([
                'Bucket'  => $bucketName,
                'MaxKeys' => 1,
            ]);

            $result = $result->toArray();
        } catch (S3Exception $e) {
            $status = (int)$e->getStatusCode();

            if ($status === 404) {
                // Bucket does not exist → create it
                try {
                    $this->s3Client->createBucket(['Bucket' => $bucketName]);
                    if (!empty($policy)) {
                        $this->s3Client->putBucketPolicy([
                            'Bucket' => $bucketName,
                            'Policy' => json_encode($policy),
                        ]);
                    }
                } catch (S3Exception $createException) {
                    return [
                        'result' => false,
                        'data'   => [],
                        'error'  => [
                            'code'    => $createException->getStatusCode(),
                            'message' => "Error creating bucket: " . $createException->getMessage(),
                        ],
                    ];
                }
            } elseif ($status === 403) {
                // Access denied → user does not have permission
                return [
                    'result' => false,
                    'data'   => [],
                    'error'  => [
                        'code'    => 403,
                        'message' => "Access denied to bucket '{$bucketName}'.",
                    ],
                ];
            } else {
                return [
                    'result' => false,
                    'data'   => [],
                    'error'  => [
                        'code'    => $status,
                        'message' => $e->getMessage(),
                    ],
                ];
            }
        }

        // Return objects if bucket exists and is accessible
        return [
            'result' => true,
            'data'   => $result,
            'error'  => [],
        ];
    }


    public function deleteBucket($bucketName): array
    {
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

        // Delete bucket data if exist
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

                try {
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
                } catch (AwsException $e) {
                    return [
                        'result' => true,
                        'data'   => [
                            'message' => 'Problem to delete some objects in bucket, but go forward !',
                        ],
                        'error'  => [],
                    ];
                }
            }

            try {
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
                    'result' => true,
                    'data'   => [
                        'message' => 'Problem to delete bucket, but go forward !',
                    ],
                    'error'  => [],
                ];
            }
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

    public function getFilesFromBucket($bucketName): array
    {
        $objetList = [];

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

        // Delete bucket data if exist
        try {
            // List objects in the bucket
            $objects = $this->s3Client->listObjects([
                'Bucket' => $bucketName,
            ]);

            // Delete objects if they exist
            if (isset($objects['Contents']) && !empty($objects['Contents'])) {
                foreach ($objects['Contents'] as $object) {
                    $objetList[] = [
                        'Bucket' => $bucketName,
                        'Key'    => $object['Key'],
                        'Size'   => $object['Size'],
                    ];
                }
            }
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

        return [
            'result' => true,
            'data'   => $objetList,
            'error'  => [],
        ];
    }
}