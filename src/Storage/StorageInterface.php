<?php

namespace Media\Storage;

interface StorageInterface
{
    public function storeMedia($uploadFile, $params): array;
}
