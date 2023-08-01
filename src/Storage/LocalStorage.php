<?php

namespace Media\Storage;

class LocalStorage implements StorageInterface
{
    /* @var array */
    protected array $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function storeMedia($uploadFile, $params): array
    {
        $mainPath = $params['access'] == 'public' ? $this->config['public_path'] : $this->config['protected_path'];
        $fullPath = sprintf('%s/%s', $this->config['protected_path'], $params['local_path']);
        $fileInfo = pathinfo($uploadFile->getClientFilename());
        $fileName = strtolower(sprintf('%s-%s-%s.%s', $fileInfo['filename'], date('Y-m-d-H-i-s'), rand(1000, 9999), $fileInfo['extension']));
        $filePath = sprintf('%s/%s', $fullPath, $fileName);

        if (!$this->makeDir($fullPath)) {
            echo 'error';
            die;
        }

        // Save file to storage
        $uploadFile->moveTo($filePath);

        return [
            'file_title' => $fileInfo['filename'],
            'file_name'  => $fileName,
            'file_path'  => $filePath,
            'full_path'  => $fullPath,
            'main_path'  => $mainPath,
            'local_path' => $params['local_path'],
        ];
    }

    public function makeDir($path): bool
    {
        return is_dir($path) || mkdir($path);
    }
}