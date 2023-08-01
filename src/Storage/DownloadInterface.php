<?php

namespace Media\Storage;

interface DownloadInterface
{
    public function stream($source, array $options = []): bool;

    public function makePublicUri($params): string;
}
