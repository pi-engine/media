<?php

namespace Media\Download;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;

class MinioDownload implements DownloadInterface
{
    /* @var array */
    protected array $config;

    protected S3Client $s3Client;

    public function __construct($config)
    {
        // Set MinIO connection parameters
        $this->s3Client = new S3Client($config['minio']);
        $this->config   = $config;
    }

    public function makePublicUri($params): string
    {
        return '';
    }

    public function makePrivateUrl($params): string
    {
        return sprintf('%s/media/%s/stream', $this->config['stream_uri'], $params['access']);
    }

    public function stream(array $params): bool
    {
        try {
            // Download the private file from MinIO
            $result = $this->s3Client->getObject([
                'Bucket' => $params['bucket'],
                'Key'    => $params['key'],
            ]);

            // Set headers for streaming the file
            header("Content-Type: " . $result['ContentType']);
            header("Content-Disposition: inline; filename=\"{$params['key']}\"");

            // Stream the file content
            echo $result['Body']; // The file content will be streamed directly

        } catch (AwsException $e) {
            echo "Error downloading file: " . $e->getMessage() . "\n";
        }

        return true;
    }
}