<?php

namespace Pi\Media\Storage;

use Laminas\Filter\FilterChain;
use Laminas\Filter\PregReplace;
use Laminas\Filter\StringToLower;
use Pi\Media\Service\S3Service;
use Random\RandomException;

class S3Storage implements StorageInterface
{
    protected S3Service $s3Service;

    /* @var array */
    protected array $config;

    public function __construct(
        S3Service $s3Service,
                  $config
    ) {
        $this->s3Service = $s3Service;
        $this->config    = $config;
    }

    /**
     * @throws RandomException
     */
    public function storeMedia($uploadFile, $params, $acl = 'private'): array
    {
        // Check if the bucket exists and create if not exist
        $bucket = $this->s3Service->setOrGetBucket($params['Bucket']);
        if (!$bucket['result']) {
            return $bucket;
        }

        // Set file name
        $fileName = $this->makeFileName($uploadFile->getClientFilename());
        $fileInfo = pathinfo($uploadFile->getClientFilename());

        // Set name
        $originalName = $uploadFile->getClientFilename();
        if (isset($params['random_name']) && (int)$params['random_name'] === 1) {
            $originalName = sprintf('%s-%s-%s', $originalName, time(), bin2hex(random_bytes(4)));
        }

        // Upload the file stream to s3
        $response = $this->s3Service->putFile([
            'Bucket'   => $params['Bucket'],
            'Key'      => $fileName,
            'Body'     => $uploadFile->getStream(),
            'ACL'      => $acl,
            'Metadata' => [
                'company_id' => $params['company_id'],
                'user_id'    => $params['company_id'],
                'access'     => $params['access'],
            ],
        ]);

        // Check result
        if (!$response['result']) {
            return $response;
        }

        // Set result
        return [
            'result' => true,
            'data'   => [
                's3'             => [
                    'Key'          => $fileName,
                    'Bucket'       => $params['Bucket'],
                    'fileRequest'  => $uploadFile->getClientFilename(),
                    'effectiveUri' => $response['@metadata']['effectiveUri'],
                ],
                'original_name'  => $originalName,
                'file_name'      => $fileName,
                'file_title'     => $fileInfo['filename'],
                'file_extension' => strtolower($fileInfo['extension']),
                'file_size'      => $uploadFile->getSize(),
                'file_type'      => $this->makeFileType(strtolower($fileInfo['extension'])),
                'file_size_view' => $this->transformSize($uploadFile->getSize()),
            ],
            'error'  => [],
        ];
    }

    public function getFilePath($params): string
    {
        // Download the private file from s3
        $result = $this->s3Service->getFile([
            'Bucket' => $params['Bucket'],
            'Key'    => $params['Key'],
        ]);

        // Download the file to a temporary location
        if ($result['result']) {
            $tempFilePath =  sys_get_temp_dir() . '/' .  basename($params['Key']);
            file_put_contents($tempFilePath, $result['data']['Body']);
            return $tempFilePath;
        }

        return '';
    }

    /**
     * @throws RandomException
     */
    public function makeFileName($file): string
    {
        // Extract the file information
        $fileInfo = pathinfo($file);

        // Initialize the filter chain
        $filterChain = new FilterChain();
        $filterChain->attach(new StringToLower()) // Convert to lowercase
        ->attach(new PregReplace('/\s+/', '-')) // Replace spaces with a single dash
        ->attach(new PregReplace('/[^a-z0-9-]/', '-')) // Replace non-alphanumeric characters with dashes
        ->attach(new PregReplace('/--+/', '-')); // Replace consecutive single dashes with double dashes

        // Filter the filename
        $fileName = $filterChain->filter($fileInfo['filename']);

        // Format the new filename
        $timestamp    = date('Y-m-d-H-i-s');
        $randomString = bin2hex(random_bytes(4));

        return sprintf('%s-%s-%s.%s', $fileName, $timestamp, $randomString, $fileInfo['extension']);
    }

    public function makeFileType($extension): string
    {
        $typeMappings = [
            // Images
            'jpg'     => 'image',
            'jpeg'    => 'image',
            'png'     => 'image',
            'gif'     => 'image',
            'bmp'     => 'image',
            'svg'     => 'image',
            'webp'    => 'image',
            'ico'     => 'image',
            'tif'     => 'image',
            'tiff'    => 'image',
            'eps'     => 'image',
            'raw'     => 'image',
            'psd'     => 'image',
            'ai'      => 'image',

            // Videos
            'mp4'     => 'video',
            'avi'     => 'video',
            'mkv'     => 'video',
            'wmv'     => 'video',
            'mov'     => 'video',
            'flv'     => 'video',
            '3gp'     => 'video',
            'webm'    => 'video',
            'ogv'     => 'video',
            'mpeg'    => 'video',
            'mpg'     => 'video',

            // Audio
            'mp3'     => 'audio',
            'wav'     => 'audio',
            'aac'     => 'audio',
            'ogg'     => 'audio',
            'wma'     => 'audio',
            'flac'    => 'audio',
            'm4a'     => 'audio',
            'amr'     => 'audio',
            'mid'     => 'audio',

            // Archives
            'zip'     => 'archive',
            'rar'     => 'archive',
            'tar'     => 'archive',
            'gz'      => 'archive',
            '7z'      => 'archive',
            'iso'     => 'archive',
            'tar.gz'  => 'archive',
            'tgz'     => 'archive',
            'bz2'     => 'archive',
            'xz'      => 'archive',

            // Microsoft Word documents
            'doc'     => 'document',
            'docx'    => 'document',
            'txt'     => 'document',
            'odt'     => 'document',
            'pages'   => 'document',

            // Spreadsheet
            'xls'     => 'spreadsheet',
            'xlsx'    => 'spreadsheet',
            'csv'     => 'spreadsheet',
            'ods'     => 'spreadsheet',
            'numbers' => 'spreadsheet',

            // Presentation
            'ppt'     => 'presentation',
            'pptx'    => 'presentation',
            'odp'     => 'presentation',
            'keynote' => 'presentation',

            // Scripting languages (combined category)
            'js'      => 'script',
            'json'    => 'script',
            'html'    => 'script',
            'css'     => 'script',
            'rtf'     => 'script',
            'xml'     => 'script',
            'py'      => 'script',
            'php'     => 'script',
            'rb'      => 'script', // Ruby script
            'pl'      => 'script', // Perl script

            // PDF (separate category)
            'pdf'     => 'pdf',

            // Executables
            'exe'     => 'executable',
            'msi'     => 'executable',
            'bat'     => 'executable',
            'sh'      => 'executable',
            'jar'     => 'executable',

            // Fonts
            'ttf'     => 'font',
            'otf'     => 'font',
            'woff'    => 'font',
            'woff2'   => 'font',

            // System configuration files (example)
            'conf'    => 'config',
            'ini'     => 'config',
        ];

        // Check if the extension exists in the mappings
        if (array_key_exists($extension, $typeMappings)) {
            return $typeMappings[$extension];
        } else {
            return 'unknown';
        }
    }

    /**
     * Transform file size
     *
     * @param int|string $value
     *
     * @return float|bool|int|string
     */
    public function transformSize(int|string $value): float|bool|int|string
    {
        $result = false;
        $sizes  = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        if (is_numeric($value)) {
            $value = (int)$value;
            for ($i = 0; $value >= 1024 && $i < 9; $i++) {
                $value /= 1024;
            }

            $result = round($value, 2) . $sizes[$i];
        } else {
            $value   = trim($value);
            $pattern = '/^([0-9]+)[\s]?(' . implode('|', $sizes) . ')$/i';
            if (preg_match($pattern, $value, $matches)) {
                $value = (int)$matches[1];
                $unit  = strtoupper($matches[2]);
                $idx   = array_search($unit, $sizes);
                if (false !== $idx) {
                    $result = $value * pow(1024, $idx);
                }
            }
        }

        return $result;
    }

    public function remove(string|iterable $files): void
    {

    }
}