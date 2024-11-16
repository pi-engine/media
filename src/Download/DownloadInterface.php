<?php

namespace Pi\Media\Download;

interface DownloadInterface
{
    public function makePublicUri(array $params): string;

    public function makePrivateUrl(array $params): string;

    public function stream(array $params): bool;
}
