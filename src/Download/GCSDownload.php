<?php

namespace Pi\Media\Download;

use Exception;
use Pi\Media\Service\GCSService;

// Replace with your actual GCS service class

class GCSDownload implements DownloadInterface
{
    protected GCSService $gcsService;

    protected array $config;

    public function __construct(
        GCSService $gcsService,
                   $config
    ) {
        $this->gcsService = $gcsService;
        $this->config     = $config;
    }

    public function makePublicUri($params): string
    {
        // Optional: implement public URL if objects are public
        return '';
    }

    public function makePrivateUrl($params): string
    {
        return sprintf('%s/media/%s/stream', $this->config['stream_uri'], $params['access']);
    }

    public function stream(array $params): bool
    {
        try {
            $bucketName = $params['Bucket'];
            $objectName = $params['Key'];

            $object = $this->gcsService->getObject($bucketName, $objectName);

            $stream   = $object->downloadAsStream();
            $metadata = $object->info();

            header("Content-Type: " . ($metadata['contentType'] ?? 'application/octet-stream'));
            header("Content-Disposition: inline; filename=\"" . basename($objectName) . "\"");

            fpassthru($stream);

        } catch (Exception $e) {
            echo "Error downloading file: " . $e->getMessage() . "\n";
        }

        return true;
    }
}
