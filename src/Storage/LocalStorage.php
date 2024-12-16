<?php

namespace Pi\Media\Storage;

use ArrayObject;
use Closure;
use DirectoryIterator;
use Exception;
use FilesystemIterator;
use Laminas\Filter\FilterChain;
use Laminas\Filter\PregReplace;
use Laminas\Filter\StringToLower;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Traversable;

class LocalStorage implements StorageInterface
{
    /* @var array */
    protected array $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * @throws Exception
     */
    public function storeMedia($uploadFile, $params): array
    {
        // Set file name
        $fileName = $this->makeFileName($uploadFile->getClientFilename());
        $fileInfo = pathinfo($uploadFile->getClientFilename());

        // Set path
        $mainPath = ($params['access'] == 'public') ? $this->config['public_path'] : $this->config['private_path'];
        $fullPath = sprintf('%s/%s', $mainPath, $params['local_path']);
        $filePath = sprintf('%s/%s', $fullPath, $fileName);

        // Check and make path
        $this->mkdir($fullPath);

        // Save file to storage
        $uploadFile->moveTo($filePath);

        // Set name
        $originalName = $uploadFile->getClientFilename();
        if (isset($params['random_name']) && (int)$params['random_name'] === 1) {
            $originalName = sprintf('%s-%s-%s', $originalName, time(), bin2hex(random_bytes(4)));
        }

        return [
            'status' => true,
            'data'   => [
                'local'          => [
                    'file_path'  => $filePath,
                    'full_path'  => $fullPath,
                    'main_path'  => $mainPath,
                    'local_path' => $params['local_path'],
                ],
                'original_name'  => $originalName,
                'file_name'      => $fileName,
                'file_title'     => $fileInfo['filename'],
                'file_extension' => strtolower($fileInfo['extension']),
                'file_size'      => $uploadFile->getSize(),
                'file_type'      => $this->makeFileType($fileInfo['extension']),
                'file_size_view' => $this->transformSize($uploadFile->getSize()),
            ],
            'error'  => [],
        ];
    }

    public function readMedia($params): string
    {
        return $params['information']['storage']['local']['file_path'];
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
        $randomString = bin2hex(random_bytes(4));

        return sprintf('%s-%s-%s.%s', $fileName, $timestamp, $randomString, $fileInfo['extension']);
    }

    /**
     * Creates a directory recursively.
     *
     * @param string|iterable $dirs The directory path
     * @param int             $mode The directory mode
     *
     * @return $this
     *
     * @throws Exception On any directory creation failure
     */
    public function mkdir(string|iterable $dirs, int $mode = 0777)
    {
        foreach ($this->toIterator($dirs) as $dir) {
            if (is_dir($dir)) {
                continue;
            }

            if (true !== @mkdir($dir, $mode, true)) {
                throw new Exception(sprintf('Failed to create %s', $dir));
            }
        }

        return $this;
    }

    /**
     * Transform array to iterator
     *
     * @param mixed $files
     *
     * @return Traversable|ArrayObject
     */
    protected function toIterator(mixed $files): Traversable|ArrayObject
    {
        if (!$files instanceof Traversable) {
            $files = new ArrayObject(
                is_array($files)
                    ? $files : [$files]
            );
        }

        return $files;
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

    public function createMedia($params): array
    {
        // Set file name
        $fileName = strtolower(sprintf('%s-%s-%s.%s', $params['filename'], date('Y-m-d-H-i-s'), rand(1000, 9999), $params['extension']));

        // Set path
        $mainPath = ($params['access'] == 'public') ? $this->config['public_path'] : $this->config['private_path'];
        $fullPath = sprintf('%s/%s', $mainPath, $params['local_path']);
        $filePath = sprintf('%s/%s', $fullPath, $fileName);

        // Check and make path
        $this->mkdir($fullPath);

        return [
            'local'          => [
                'file_path'  => $filePath,
                'full_path'  => $fullPath,
                'main_path'  => $mainPath,
                'local_path' => $params['local_path'],
            ],
            'original_name'  => $params['original_name'] ?? $fileName,
            'file_name'      => $fileName,
            'file_title'     => $params['filename'],
            'file_extension' => strtolower($params['extension']),
            'file_size'      => 0,
            'file_type'      => $this->makeFileType(strtolower($params['extension'])),
            'file_size_view' => $this->transformSize(0),
        ];
    }

    /**
     * Checks the existence of files or directories.
     *
     * @param string|iterable $files          A filename,
     *                                        an array of files, or a Traversable instance to check
     *
     * @return Bool
     */
    public function exists(string|iterable $files): bool
    {
        foreach ($this->toIterator($files) as $file) {
            if (!file_exists($file)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Sets access and modification time of file.
     *
     * @param string|iterable $files
     *      A filename, an array of files, or a Traversable instance to create
     * @param int|null        $time
     *      The touch time as a unix timestamp
     * @param int|null        $atime
     *      The access time as a unix timestamp
     *
     * @return $this
     *
     * @throws Exception When touch fails
     */
    public function touch(string|iterable $files, int $time = null, int $atime = null): static
    {
        if (null === $time) {
            $time = time();
        }

        foreach ($this->toIterator($files) as $file) {
            if (true !== @touch($file, $time, $atime)) {
                throw new Exception(sprintf('Failed to touch %s', $file));
            }
        }

        return $this;
    }

    /**
     * Empties directories.
     *
     * @param string|iterable $dirs The directory path
     *
     * @return $this
     * @throws Exception
     */
    public function flush(string|iterable $dirs): static
    {
        $dirs = iterator_to_array($this->toIterator($dirs));
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            $this->remove(new FilesystemIterator($dir));
        }

        return $this;
    }

    /**
     * Removes files or directories.
     *
     * @param string|iterable $files
     *      A filename, an array of files, or a Traversable instance to remove
     *
     * @return $this
     *
     * @throws Exception When removal fails
     */
    public function remove(string|iterable $files): void
    {
        $files = iterator_to_array($this->toIterator($files));
        $files = array_reverse($files);
        foreach ($files as $file) {
            if (!file_exists($file) && !is_link($file)) {
                throw new Exception(
                    sprintf('File "%s" not found', $file)
                );
                //continue;
            }

            // hard directory
            if (is_dir($file) && !is_link($file)) {
                $this->remove(new FilesystemIterator($file));

                if (true !== @rmdir($file)) {
                    throw new Exception(
                        sprintf('Failed to remove directory "%s"', $file)
                    );
                }
            } else {
                // symbolic directory on Windows
                // https://bugs.php.net/bug.php?id=52176
                if (defined('PHP_WINDOWS_VERSION_MAJOR') && is_dir($file)) {
                    if (true !== @rmdir($file)) {
                        throw new Exception(
                            sprintf('Failed to remove file "%s"', $file)
                        );
                    }
                    // symbolic directory or file
                } else {
                    if (true !== @unlink($file)) {
                        throw new Exception(
                            sprintf('Failed to remove file "%s"', $file)
                        );
                    }
                }
            }
        }
    }

    /**
     * Change mode for an array of files or directories.
     *
     * @param string|iterable $files
     *                                        A filename, an array of files,
     *                                        or a Traversable instance to change mode
     * @param int             $mode           The new mode (octal)
     * @param int             $umask          The mode mask (octal)
     * @param Bool            $recursive
     *                                        Whether change the mod recursively or not
     *
     * @return $this
     *
     * @throws Exception When the change fail
     */
    public function chmod(string|iterable $files, int $mode, int $umask = 0000, bool $recursive = false): static
    {
        foreach ($this->toIterator($files) as $file) {
            if ($recursive && is_dir($file) && !is_link($file)) {
                $this->chmod(
                    new FilesystemIterator($file),
                    $mode,
                    $umask,
                    true
                );
            }
            if (true !== @chmod($file, $mode & ~$umask)) {
                throw new Exception(sprintf('Failed to chmod file %s', $file));
            }
        }

        return $this;
    }

    /**
     * Change the owner of an array of files or directories
     *
     * @param string|iterable $files
     *                                       A filename, an array of files,
     *                                       or a \Traversable instance to change owner
     * @param string          $user          The new owner user name
     * @param Bool            $recursive
     *                                       Whether change the owner recursively or not
     *
     * @return $this
     *
     * @throws Exception When the change fail
     */
    public function chown(string|iterable $files, string $user, bool $recursive = false): static
    {
        foreach ($this->toIterator($files) as $file) {
            if ($recursive && is_dir($file) && !is_link($file)) {
                $this->chown(new FilesystemIterator($file), $user, true);
            }
            if (is_link($file) && function_exists('lchown')) {
                if (true !== @lchown($file, $user)) {
                    throw new Exception(
                        sprintf('Failed to chown file %s', $file)
                    );
                }
            } else {
                if (true !== @chown($file, $user)) {
                    throw new Exception(
                        sprintf('Failed to chown file %s', $file)
                    );
                }
            }
        }

        return $this;
    }

    /**
     * Change the group of an array of files or directories
     *
     * @param Traversable|array|string $files
     *                                        A filename, an array of files,
     *                                        or a Traversable instance to change group
     * @param string                   $group The group name
     * @param Bool                     $recursive
     *                                        Whether change the group recursively or not
     *
     * @return $this
     *
     * @throws Exception When the change fail
     */
    public function chgrp(Traversable|array|string $files, string $group, bool $recursive = false): static
    {
        foreach ($this->toIterator($files) as $file) {
            if ($recursive && is_dir($file) && !is_link($file)) {
                $this->chgrp(new FilesystemIterator($file), $group, true);
            }
            if (is_link($file) && function_exists('lchgrp')) {
                if (true !== @lchgrp($file, $group)) {
                    throw new Exception(
                        sprintf('Failed to chgrp file %s', $file)
                    );
                }
            } else {
                if (true !== @chgrp($file, $group)) {
                    throw new Exception(
                        sprintf('Failed to chgrp file %s', $file)
                    );
                }
            }
        }

        return $this;
    }

    /**
     * Renames a file.
     *
     * @param string $origin The origin filename
     * @param string $target The new filename
     *
     * @return $this
     *
     * @throws Exception When target file already exists
     * @throws Exception When origin cannot be renamed
     */
    public function rename(string $origin, string $target): static
    {
        // we check that target does not exist
        if (is_readable($target)) {
            throw new Exception(
                sprintf(
                    'Cannot rename because the target "%s" already exist.',
                    $target
                )
            );
        }

        if (true !== @rename($origin, $target)) {
            throw new Exception(
                sprintf('Cannot rename "%s" to "%s".', $origin, $target)
            );
        }

        return $this;
    }

    /**
     * Creates a symbolic link or copy a directory.
     *
     * @param string $originDir     The origin directory path
     * @param string $targetDir     The symbolic link name
     * @param Bool   $copyOnWindows Whether to copy files if on Windows
     * @param Bool   $override
     *                              Whether to override existing files
     *
     * @return $this
     *
     * @throws Exception When symlink fails
     */
    public function symlink(
        string $originDir,
        string $targetDir,
        bool   $copyOnWindows = true,
        bool   $override = false
    ): static {
        if (!function_exists('symlink')
            || (defined('PHP_WINDOWS_VERSION_MAJOR') && $copyOnWindows)
        ) {
            $this->mirror(
                $originDir,
                $targetDir,
                null,
                [
                    'copy_on_windows' => $copyOnWindows,
                    'override'        => $override,
                ]
            );

            return $this;
        }

        $this->mkdir(dirname($targetDir));

        $ok = false;
        if (is_link($targetDir)) {
            if ($override || readlink($targetDir) != $originDir) {
                $this->remove($targetDir);
            } else {
                $ok = true;
            }
        }

        if (!$ok) {
            if (true !== symlink($originDir, $targetDir)) {
                $report = error_get_last();
                if (is_array($report)) {
                    if (defined('PHP_WINDOWS_VERSION_MAJOR')
                        && false !== strpos(
                            $report['message'],
                            'error code(1314)'
                        )
                    ) {
                        throw new Exception(
                            'Unable to create symlink due to error code 1314: '
                            . '\'A required privilege is not held '
                            . 'by the client\'. '
                            . 'Do you have the required Administrator-rights?'
                        );
                    }
                }
                throw new Exception(
                    sprintf(
                        'Failed to create symbolic link from %s to %s',
                        $originDir,
                        $targetDir
                    )
                );
            }
        }

        return $this;
    }

    /**
     * Mirrors a directory to another.
     *
     * With options
     *
     *  - override: Whether to override an existing file on copy
     *      {@see copy()};
     *  - copy_on_windows: Whether to copy files instead of links on Windows
     *      {@see symlink()}.
     *
     * @param string           $originDir The origin directory
     * @param string           $targetDir The target directory
     * @param Traversable|null $iterator  A Traversable instance
     * @param array            $options   An array of bool options
     *
     * @return $this
     *
     * @throws Exception When file type is unknown
     */
    public function mirror(
        string      $originDir,
        string      $targetDir,
        Traversable $iterator = null,
        array       $options = []
    ): static {
        $copyOnWindows = true;
        if (isset($options['copy_on_windows'])
            && defined('PHP_WINDOWS_VERSION_MAJOR')
        ) {
            $copyOnWindows = $options['copy_on_windows'];
        }

        if (null === $iterator) {
            $flags    = $copyOnWindows
                ? FilesystemIterator::SKIP_DOTS
                  | FilesystemIterator::FOLLOW_SYMLINKS
                : FilesystemIterator::SKIP_DOTS;
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($originDir, $flags),
                RecursiveIteratorIterator::SELF_FIRST
            );
        }

        $targetDir = rtrim($targetDir, '/\\');
        $originDir = rtrim($originDir, '/\\');

        foreach ($iterator as $file) {
            $target = str_replace(
                $originDir,
                $targetDir,
                $file->getPathname()
            );

            if (is_dir($file)) {
                $this->mkdir($target);
            } elseif (!$copyOnWindows && is_link($file)) {
                $this->symlink($file, $target);
            } elseif (is_file($file) || ($copyOnWindows && is_link($file))) {
                $this->copy(
                    $file,
                    $target,
                    isset($options['override']) ? $options['override'] : false
                );
            } else {
                throw new Exception(
                    sprintf('Unable to guess "%s" file type.', $file)
                );
            }
        }

        return $this;
    }

    /**
     * Copies a file.
     *
     * This method only copies the file if the origin file is newer
     * than the target file.
     *
     * By default, if the target already exists, it is not overridden.
     *
     * @param string $originFile The original filename
     * @param string $targetFile The target filename
     * @param bool   $override   Whether to override an existing file
     *
     * @return $this
     *
     * @throws Exception When copy fails
     */
    public function copy(string $originFile, string $targetFile, bool $override = false): static
    {
        $this->mkdir(dirname($targetFile));

        if (!$override && is_file($targetFile)) {
            $doCopy = filemtime($originFile) > filemtime($targetFile);
        } else {
            $doCopy = true;
        }

        if ($doCopy) {
            if (true !== @copy($originFile, $targetFile)) {
                throw new Exception(
                    sprintf(
                        'Failed to copy %s to %s',
                        $originFile,
                        $targetFile
                    )
                );
            }
        }

        return $this;
    }

    /**
     * Given an existing path,
     * convert it to a path relative to a given starting path
     *
     * @param string $endPath   Absolute path of target
     * @param string $startPath Absolute path where traversal begins
     *
     * @return string Path of target relative to starting path
     */
    public function makePathRelative(string $endPath, string $startPath): string
    {
        // Normalize separators on windows
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $endPath   = strtr($endPath, '\\', '/');
            $startPath = strtr($startPath, '\\', '/');
        }

        // Split the paths into arrays
        $startPathArr = explode('/', trim($startPath, '/'));
        $endPathArr   = explode('/', trim($endPath, '/'));

        // Find for which directory the common path stops
        $index = 0;
        while (isset($startPathArr[$index])
               && isset($endPathArr[$index])
               && $startPathArr[$index] === $endPathArr[$index]
        ) {
            $index++;
        }

        // Determine how deep the start path is relative to the common path
        // (ie, "web/bundles" = 2 levels)
        $depth = count($startPathArr) - $index;

        // Repeated "../" for each level need to reach the common path
        $traverser = str_repeat('../', $depth);

        $endPathRemainder = implode('/', array_slice($endPathArr, $index));

        // Construct $endPath from traversing to the common path,
        // then to the remaining $endPath
        $relativePath = $traverser
                        . (strlen($endPathRemainder) > 0
                ? $endPathRemainder . '/' : '');

        return (strlen($relativePath) === 0) ? './' : $relativePath;
    }

    /**
     * Get file list in a directory
     *
     * @param DirectoryIterator|string $path
     * @param Closure|null             $filter
     * @param bool                     $recursive
     *
     * @return array
     */
    public function getList(DirectoryIterator|string $path, Closure $filter = null, bool $recursive = false): array
    {
        $result   = [];
        $iterator = null;
        if ($path instanceof DirectoryIterator) {
            $iterator = $path;
        } else {
            $path = $this->isAbsolutePath($path) ? $path : '';
            if ($recursive) {
                $flags = FilesystemIterator::SKIP_DOTS
                         | FilesystemIterator::FOLLOW_SYMLINKS
                         | FilesystemIterator::UNIX_PATHS;
                try {
                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($path, $flags)
                    );
                } catch (Exception $e) {
                    $iterator = null;
                }
            } else {
                try {
                    $iterator = new DirectoryIterator($path);
                } catch (Exception $e) {
                    $iterator = null;
                }
            }
        }
        $filter = $filter instanceof Closure
            ? $filter
            : function ($fileinfo) {
                if (!$fileinfo->isFile()) {
                    return false;
                }
                return $fileinfo->getPathname();
            };
        if ($iterator instanceof DirectoryIterator) {
            foreach ($iterator as $fileinfo) {
                $filedata = $filter($fileinfo);
                if (!$filedata) {
                    continue;
                }
                $result[] = $filedata;
            }
        }

        return $result;
    }

    /**
     * Returns whether the file path is an absolute path.
     *
     * @param string $file A file path
     *
     * @return Bool
     */
    public function isAbsolutePath(string $file): bool
    {
        //$result = preg_match('|^([a-zA-Z]:)?/|', $path);
        $result = false;
        if (strspn($file, '/\\', 0, 1)
            || (
                strlen($file) > 3 && ctype_alpha($file[0])
                && substr($file, 1, 1) === ':'
                && (strspn($file, '/\\', 2, 1))
            )
            || null !== parse_url($file, PHP_URL_SCHEME)
        ) {
            $result = true;
        }

        return $result;
    }
}