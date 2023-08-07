<?php

namespace Media\Download;

interface DownloadInterface
{
    public function stream($source, array $options = []): bool;

    public function makePublicUri($params): string;
}
