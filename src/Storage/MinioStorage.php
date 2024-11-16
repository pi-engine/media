<?php

namespace Pi\Media\Storage;


use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Laminas\Filter\FilterChain;
use Laminas\Filter\PregReplace;
use Laminas\Filter\StringToLower;
use Laminas\Math\Rand;

class MinioStorage implements StorageInterface
{
    /* @var array */
    protected array $config;

    protected S3Client $s3Client;

    public function __construct($config)
    {
        // Set MinIO connection parameters
        $this->s3Client = new S3Client($config['minio']);
        $this->config   = $config;
    }

    public function storeMedia($uploadFile, $params): array
    {
        // Check if the bucket exists and create if not exist
        $bucket = $this->setOrGetBucket($params['bucket']);
        if (!$bucket['status']) {
            return $bucket;
        }

        // Set file name
        $fileName = $this->makeFileName($uploadFile->getClientFilename());
        $fileInfo = pathinfo($uploadFile->getClientFilename());

        // Set name
        $originalName = $uploadFile->getClientFilename();
        if (isset($params['random_name']) && (int)$params['random_name'] === 1) {
            $originalName = sprintf('%s-%s-%s', $originalName, time(), Rand::getString('8', 'abcdefghijklmnopqrstuvwxyz0123456789'));
        }

        // Upload file
        try {
            // Upload the file stream to MinIO
            $response = $this->s3Client->putObject([
                'Bucket'   => $params['bucket'],
                'Key'      => $fileName,
                'Body'     => $uploadFile->getStream(),
                'ACL'      => 'private',
                'Metadata' => [
                    'company_id' => $params['company_id'],
                    'user_id'    => $params['company_id'],
                    'access'     => $params['access'],
                ],
            ]);

            return [
                'status' => true,
                'data'   => [
                    'minio'          => [
                        'key'          => $fileName,
                        'bucket'       => $params['bucket'],
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
        } catch (AwsException $e) {
            return [
                'status' => false,
                'data'   => [],
                'error'  => [
                    'code'    => $e->getStatusCode(),
                    'message' => "Error uploading file: " . $e->getMessage(),
                ],
            ];
        }
    }

    public function setOrGetBucket($bucketName): array
    {
        try {
            $this->s3Client->headBucket([
                'Bucket' => $bucketName,
            ]);
        } catch (AwsException $e) {
            if ((int)$e->getStatusCode() === 404) {
                try {
                    // Create the bucket
                    $this->s3Client->createBucket([
                        'Bucket' => $bucketName,
                        'ACL'    => 'private', // Ensure bucket is private
                    ]);

                    // Wait until the bucket is created
                    $this->s3Client->waitUntil('BucketExists', [
                        'Bucket' => $bucketName,
                    ]);
                } catch (AwsException $createException) {
                    return [
                        'status' => false,
                        'data'   => [],
                        'error'  => [
                            'code'    => $createException->getStatusCode(),
                            'message' => "Error creating bucket: " . $createException->getMessage(),
                        ],
                    ];
                }
            } else {
                return [
                    'status' => false,
                    'data'   => [],
                    'error'  => [
                        'code'    => $e->getStatusCode(),
                        'message' => "An error occurred: " . $e->getMessage(),
                    ],
                ];
            }
        }

        // Get data (objects) from the bucket
        try {
            $result = $this->s3Client->listObjects([
                'Bucket' => $bucketName,
            ]);

            return [
                'status' => true,
                'data'   => $result->toArray(),
                'error'  => [],
            ];
        } catch (AwsException $e) {
            return [
                'status' => false,
                'data'   => [],
                'error'  => [
                    'code'    => $e->getStatusCode(),
                    'message' => "Error retrieving objects from the bucket: " . $e->getMessage(),
                ],
            ];
        }
    }

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
        $randomString = Rand::getString('8', 'abcdefghijklmnopqrstuvwxyz0123456789');

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