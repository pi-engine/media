<?php

namespace Pi\Media\Download;

use Aws\Exception\AwsException;
use Pi\Media\Service\S3Service;

class S3Download implements DownloadInterface
{
    protected S3Service $s3Service;

    /* @var array */
    protected array $config;

    public function __construct(
        S3Service $s3Service,
                  $config
    ) {
        $this->s3Service = $s3Service;
        $this->config    = $config;
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
            // Download the private file from s3
            $result = $this->s3Service->getFile([
                'Bucket' => $params['Bucket'],
                'Key'    => $params['Key'],
            ]);

            // Set headers for streaming the file
            header("Content-Type: " . $result['ContentType']);
            header("Content-Disposition: inline; filename=\"{$params['Key']}\"");

            // Stream the file content
            echo $result['Body']; // The file content will be streamed directly

        } catch (AwsException $e) {
            echo "Error downloading file: " . $e->getMessage() . "\n";
        }

        return true;
    }
}