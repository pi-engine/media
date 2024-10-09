<?php

namespace Media\Download;

interface DownloadInterface
{
    public function makePublicUri($params): string;

    public function makePrivateUrl($params): string;

    public function stream($source, array $options = []): bool;
}
