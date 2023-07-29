<?php

namespace Media\Service;

class LocalStorageService implements ServiceInterface
{
    /* @var array */
    protected array $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function storeMedia($uploadFile, $params): array
    {
        $storagePath = sprintf('%s/%s', $this->config['protected_path'], $params['local_path']);
        $fileInfo    = pathinfo($uploadFile->getClientFilename());
        $fileName    = strtolower(sprintf('%s-%s-%s.%s', $fileInfo['filename'], date('Y-m-d-H-i-s'), rand(1000, 9999), $fileInfo['extension']));
        $filePath    = sprintf('%s/%s', $storagePath, $fileName);

        if (!$this->makeDir($storagePath)) {
            echo 'error';
            die;
        }

        $uploadFile->moveTo($filePath);

        return [
            'file_title'   => $fileInfo['filename'],
            'file_name'    => $fileName,
            'file_path'    => $filePath,
            'storage_path' => $storagePath,
            'main_path'    => $this->config['protected_path'],
            'local_path'   => $params['local_path'],
        ];
    }

    public function makeDir($path): bool
    {
        return is_dir($path) || mkdir($path);
    }
}