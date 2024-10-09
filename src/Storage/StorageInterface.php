<?php

namespace Media\Storage;

interface StorageInterface
{
    public function storeMedia($uploadFile, $params): array;

    public function makeFileName($file): string;

    public function makeFileType($extension): string;

    public function transformSize(int|string $value): float|bool|int|string;

    public function remove(string|iterable $files): void;
}
