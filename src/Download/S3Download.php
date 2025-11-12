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

            // Check and set error
            if (!isset($result['data']['Body']) || $result['data']['Body'] === null) {
                echo 'Empty body stream from S3';
                exit;
            }

            // Set proper headers for download
            header('Content-Description: File Transfer');
            header('Content-Type: ' . ($result['data']['ContentType'] ?? 'application/octet-stream'));
            header('Content-Disposition: attachment; filename="' . basename($params['Key']) . '"');
            header('Content-Length: ' . $result['data']['ContentLength']);
            header('Cache-Control: public, must-revalidate, max-age=0');
            header('Pragma: public');
            // If you need range support, that's extra work (see note below)

            // $result['Body'] is a Psr7 stream — stream it out in chunks
            $body = $result['data']['Body'];
            while (!$body->eof()) {
                echo $body->read(1024 * 8); // 8KB chunks
                flush();
            }
            exit;
        } catch (AwsException $e) {
            echo "Error downloading file: " . $e->getMessage() . "\n";
        }

        return true;
    }
}