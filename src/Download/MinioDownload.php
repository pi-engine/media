<?php

namespace Media\Download;

class MinioDownload implements DownloadInterface
{
    protected bool $exit = true;

    protected string $tmp = '';

    /* @var array */
    protected array $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function makePublicUri($params): string
    {
        return sprintf('%s/%s/%s', $this->config['download_uri'], $params['local_path'], $params['file_name']);
    }

    public function makePrivateUrl($params): string
    {
        return sprintf('%s/media/%s/stream', $this->config['stream_uri'], $params['access']);
    }

    public function stream($source, array $options = []): bool
    {
        return true;
    }
}