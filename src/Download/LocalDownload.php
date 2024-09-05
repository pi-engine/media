<?php

namespace Media\Download;

use Exception;
use ZipArchive;

class LocalDownload implements DownloadInterface
{
    protected bool $exit = true;

    protected string $tmp = '';

    /* @var array */
    protected array $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function makePublicUri($params): string
    {
        return sprintf('%s/%s/%s', $this->config['download_uri'], $params['local_path'], $params['file_name']);
    }

    public function makePrivateUrl($params): string
    {
        return sprintf('%s/media/%s/stream', $this->config['stream_uri'], $params['access']);
    }

    /**
     * Stream the file to the client (Download)
     *
     * @param string|array $source  File or file meta to download
     * @param array        $options Options for the file(s) to send
     *
     * @return bool
     * @throws Exception
     */
    public function stream($source, array $options = []): bool
    {
        $error = '';

        // Canonize download options
        $source = $this->canonizeDownload($source, $options);
        if (!$source) {
            $error = 'Invalid source';
        } elseif ('raw' == $options['type']) {
            $source = $options['source'];
        } elseif (file_exists($source)) {
            $source = fopen($source, 'rb');
        } else {
            $error = 'Source not found';
        }

        if (!$error) {
            // Send the content to client
            $this->download(
                $source,
                $options['filename'],
                $options['content_type'],
                $options['content_length']
            );

            // Close resource handler
            if (is_resource($source)) {
                fclose($source);
            }

            // Remove tmp zip file
            if ('zip' == $options['type']) {
                @unlink($options['source']);
            }
        } else {
            throw new \Exception($error);
        }

        if ($this->exit) {
            // Exit request to avoid extra output
            exit;
        }

        return true;
    }

    /**
     * Canonize download options
     *
     * @param array|string $source  File or file meta to download
     * @param array        $options Options for the file(s) to send
     *
     * @return string
     */
    protected function canonizeDownload(array|string $source, array &$options = []): array|string
    {
        if (!isset($options['type'])) {
            $options['type'] = 'file';
        }
        if (is_array($source)) {
            array_walk(
                $source,
                function (&$item) {
                    if (!is_array($item)) {
                        $item = ['filename' => $item];
                        if (empty($item['localname'])) {
                            $item['localname'] = basename($item['filename']);
                        }
                    }
                }
            );
            $zipFile = tempnam($this->tmp, 'zip');
            $zip     = new ZipArchive;
            if ($zip->open($zipFile, ZipArchive::CREATE) !== true) {
                return [];
            }

            foreach ($source as $item) {
                $zip->addFile($item['filename'], $item['localname']);
            }
            $zip->close();
            $source            = $zipFile;
            $options['source'] = $zipFile;

            $options['type'] = 'zip';
            if (!empty($options['filename'])) {
                if (strtolower(substr($options['filename'], -4)) != '.zip') {
                    $options['filename'] .= '.zip';
                }
            } else {
                $options['filename'] = 'archive.zip';
            }
            $options['content_type'] = 'application/zip';
            $this->canonizeDownload($source, $options);
        } elseif ('raw' == $options['type']) {
            if (!isset($options['content_length'])) {
                $options['content_length'] = strlen($source);
            }
            $options['source'] = $source;
        } else {
            if (!isset($options['filename'])) {
                $filename            = str_replace('\\\\', '/', $source);
                $segs                = explode('/', $filename);
                $options['filename'] = array_pop($segs);
            }
            if (!isset($options['content_length'])) {
                $options['content_length'] = filesize($source);
            }
        }
        if (empty($options['filename'])) {
            $options['filename'] = 'pi-download';
        }
        if (empty($options['content_type'])) {
            $options['content_type'] = 'application/octet-stream';
        }

        return $source;
    }

    /**
     * Send content to client
     *
     * @param        $source
     * @param string $filename
     * @param string $contentType
     * @param int    $contentLength
     *
     * @return bool
     */
    protected function download(
        $source,
        string $filename,
        string $contentType,
        int $contentLength = 0
    ): bool {
        //$isIe = Pi::service('browser')->isIe();
        /* $isIe = false;
        if ($isIe) {
            $contentType = $contentType ?: 'application/octet-stream';
            $filename    = urlencode($filename);
        } else {
            $contentType = $contentType ?: 'application/force-download';
        } */
        $contentType = $contentType ?: 'application/force-download';

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: chunked');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        // Specify domains from which requests are allowed
        header('Access-Control-Allow-Origin: *');
        // Specify which request methods are allowed
        header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
        // Additional headers which may be sent along with the CORS request
        header('Access-Control-Allow-Headers: X-Requested-With,Authorization,Content-Type');
        // Set the age to 1 day to improve speed/caching.
        header('Access-Control-Max-Age: 86400');
        if ($contentLength) {
            header('Content-Length: ' . $contentLength);
        }

        ob_clean();
        flush();
        if (is_resource($source)) {
            // Send the content in chunks
            $buffer     = 1024;
            $readLength = 0;
            while (false !== ($chunk = fread($source, $buffer))
                   && $readLength < $contentLength
            ) {
                $readLength += $buffer;
                echo $chunk;
            }
        } elseif (is_string($source)) {
            echo $source;
        }

        return true;
    }
}