<?php

namespace BlueFilesystem\StaticObjects;

use BlueEvent\Event\Base\Interfaces\EventDispatcherInterface;

class Fs implements FsInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected static $event;

    /**
     * remove file or directory with all content
     *
     * @param string $path
     * @param bool $force
     * @return array information that operation was successfully, or NULL if path incorrect
     */
    public static function delete(string $path, bool $force = false): array
    {
        self::triggerEvent(self::DELETE_PATH_CONTENT_BEFORE, [&$path]);

        $operationList = [];

        if (!Structure::exist($path)) {
            return [];
        }

        $isDir = \is_dir($path);

        if ($isDir) {
            $list = new Structure($path, true);
            $paths = $list->returnPaths(true);

            self::setForceMode($paths, $force);

            self::triggerEvent(self::DELETE_PATHS_BEFORE, [&$paths]);

            $operationList = self::processRemoving($operationList, $paths, 'file');
            $operationList = self::processRemoving($operationList, $paths, 'dir');
        }

        $operationList = self::remove($operationList, $path, $isDir);

        self::triggerEvent(self::DELETE_PATH_CONTENT_AFTER, [$operationList, $path]);

        return $operationList;
    }

    /**
     * @param array $paths
     * @param bool $force
     */
    protected static function setForceMode(array $paths, bool $force): void
    {
        if (!$force) {
            return;
        }

        foreach ($paths['file'] as $path) {
            \chmod($path, 0777);
        }
        foreach ($paths['dir'] as $path) {
            \chmod($path, 0777);
        }
    }

    /**
     * @param array $operationList
     * @param array $paths
     * @param string $type
     * @return array
     */
    protected static function processRemoving(array $operationList, array $paths, string $type): array
    {
        $isDir = true;

        if ($type === 'file') {
            $isDir = false;
        }

        if (isset($paths[$type])) {
            foreach ($paths[$type] as $val) {
                $operationList = self::remove($operationList, $val, $isDir);
            }
        }

        return $operationList;
    }

    /**
     * @param array $operationList
     * @param string $path
     * @param bool $isDir
     * @return array
     */
    protected static function remove(array $operationList, string $path, bool $isDir): array
    {
        try {
            if ($isDir) {
                $operationList['delete:' . $path] = rmdir($path);
            } else {
                $operationList['delete:' . $path] = unlink($path);
            }
        } catch (\Throwable $exception) {
            $operationList['delete:' . $path] = $exception->getMessage();
            self::triggerEvent(self::DELETE_PATH_CONTENT_EXCEPTION, [&$operationList, $path, $exception]);
        }

        return $operationList;
    }

    /**
     * copy file or directory to given source
     * if source directory not exists, create it
     *
     * @param string $source
     * @param string $target
     * @param bool $force
     * @return array
     */
    public static function copy(string $source, string $target, bool $force = false): array
    {
        self::triggerEvent(self::COPY_PATH_CONTENT_BEFORE, [&$source, &$target]);

        if (!Structure::exist($source)) {
            return [];
        }

        if (is_dir($source)) {
            $operationList = self::copyDir($source, $target, $force);
        } else {
            $operationList = self::copyFile($source, $target);
        }

        return $operationList;
    }

    /**
     * @param string $source
     * @param string $target
     * @return array
     */
    protected static function copyFile(string $source, string $target): array
    {
        $operationList = [];
        $key = $source . PATH_SEPARATOR . $target;

        try {
            $operationList['copy:' . $key] = \copy($source, $target);
        } catch (\Throwable $exception) {
            $operationList['copy:' . $key] = $exception->getMessage();
            self::triggerEvent(self::COPY_PATH_CONTENT_EXCEPTION, [&$operationList, $key, $exception]);
        }

        self::triggerEvent(self::COPY_PATH_CONTENT_AFTER, [$source, $target]);

        return $operationList;
    }

    /**
     * @param string $source
     * @param string $target
     * @param bool $force
     * @return array
     */
    protected static function copyDir(string $source, string $target, bool $force): array
    {
        $operationList = [];
        $pathParts = \explode(DIRECTORY_SEPARATOR, $source);
        $dirToCopy = DIRECTORY_SEPARATOR . \end($pathParts);

        if (!Structure::exist($target)) {
            $operationList = self::tryMkdir($operationList, $target, self::COPY_CREATE_PATH_EXCEPTION);
            $dirToCopy = '';
        }

        $list = new Structure($source, true);
        $paths = $list->returnPaths(true);

        self::setForceMode($paths, $force);

        self::triggerEvent(self::COPY_PATHS_BEFORE, [&$source, &$target]);

        $operationList = self::buildDirStructure($paths['dir'], $source, $target, $dirToCopy, $operationList);

        foreach ($paths['file'] as $mainFile) {
            $file = \str_replace($source, '', $mainFile);
            $newTarget = $target . $dirToCopy . $file;

            try {
                $operationList['copy:' . $mainFile . ':' . $newTarget] = \copy($mainFile, $newTarget);
            } catch (\Throwable $exception) {
                $operationList['copy:' . $mainFile . ':' . $newTarget] = $exception->getMessage();
                self::triggerEvent(self::COPY_PATH_CONTENT_EXCEPTION, [&$operationList, $exception]);
            }
        }

        self::triggerEvent(self::COPY_PATH_CONTENT_AFTER, [$source, $target]);

        return $operationList;
    }

    /**
     * @param array $dirPaths
     * @param string $source
     * @param string $target
     * @param string $dirToCopy
     * @param array $operationList
     * @return array
     */
    protected static function buildDirStructure(
        array $dirPaths,
        string $source,
        string $target,
        string $dirToCopy,
        array $operationList
    ): array {
        foreach ($dirPaths as $dir) {
            $creationDir[] = $target . $dirToCopy . \str_replace($source, '', $dir);
        }

        $creationDir[] = $target . $dirToCopy;
        $creationDirRevert = \array_reverse($creationDir);

        foreach ($creationDirRevert as $dir) {
            if (!Structure::exist($dir)) {
                $operationList = self::tryMkdir($operationList, $dir, self::COPY_CREATE_PATH_EXCEPTION);
            }
        }

        return $operationList;
    }

    /**
     * @param array $operationList
     * @param string $dir
     * @param string $eventName
     * @return array
     */
    protected static function tryMkdir(array $operationList, string $dir, string $eventName): array
    {
        try {
            $operationList['mkdir:' . $dir] = \mkdir($dir);
        } catch (\Throwable $exception) {
            $operationList['mkdir:' . $dir] = $exception->getMessage();
            self::triggerEvent($eventName, [&$operationList, $dir, $exception]);
        }

        return $operationList;
    }

    /**
     * create new directory in given location
     *
     * @param string $path
     * @return array
     * @static
     */
    public static function mkdir(string $path): array
    {
        $newPath = '';
        $operationList = [];
        $bool = \preg_match(self::RESTRICTED_SYMBOLS, $path);

        if ($bool) {
            self::triggerEvent(self::CREATE_PATH_EXCEPTION, [$path]);
            return [];
        }

        self::triggerEvent(self::CREATE_PATH_BEFORE, [&$path]);
        $pathDirs = \explode(DIRECTORY_SEPARATOR, $path);

        foreach ($pathDirs as $dir) {
            $newPath .= $dir . DIRECTORY_SEPARATOR;

            if (Structure::exist($newPath)) {
                continue;
            }

            try {
                $operationList[$newPath] = \mkdir($newPath);
                self::triggerEvent(self::CREATE_PATH_AFTER, [$newPath]);
            } catch (\Throwable $exception) {
                self::triggerEvent(self::CREATE_PATH_EXCEPTION, [$newPath]);
                return [
                    $newPath => $exception->getMessage(),
                ];
            }
        }

        return $operationList;
    }

    /**
     * create empty file, and optionally put in them some data
     *
     * @param string $path
     * @param string $fileName
     * @param mixed $data
     * @return boolean information that operation was successfully
     * @example mkfile('directory/inn', 'file.txt')
     * @example mkfile('directory/inn', 'file.txt', 'Lorem ipsum')
     */
    public static function mkfile(string $path, string $fileName, $data = null): bool
    {
        $status = false;

        self::triggerEvent(self::CREATE_FILE_BEFORE, [&$path, &$fileName, &$data]);

        if (!Structure::exist($path)) {
            $list = self::mkdir($path);
            if ($list === []) {
                return $status;
            }
        }

        if (\preg_match(self::RESTRICTED_SYMBOLS, $fileName)) {
            self::triggerEvent(self::CREATE_FILE_EXCEPTION, [$fileName]);
            return $status;
        }

        try {
            $fileResource = \fopen($path . DIRECTORY_SEPARATOR . $fileName, 'wb');

            if ($data) {
                $status = \fwrite($fileResource, $data);
            }

            self::triggerEvent(self::CREATE_FILE_AFTER, [$path, $fileName]);
            fclose($fileResource);
            $status = true;
        } catch (\Throwable $exception) {
            self::triggerEvent(self::CREATE_FILE_EXCEPTION, [$path, $fileName, $exception]);
        }

        return (bool)$status;
    }

    /**
     * change name of file/directory
     * also can be used to copy operation
     *
     * @param string $source original path or name
     * @param string $target new path or name
     * @param bool $force
     * @return boolean information that operation was successfully
     */
    public static function rename(string $source, string $target, bool $force = false): bool
    {
        $status = true;

        self::setForceMode([$source], $force);
        self::triggerEvent(self::RENAME_FILE_OR_DIR_BEFORE, [&$source, &$target]);

        if (!Structure::exist($source)) {
            self::triggerEvent(self::RENAME_FILE_OR_DIR_EXCEPTION, [$source, 'source']);
            $status = false;
        }

        if (Structure::exist($target)) {
            self::triggerEvent(self::RENAME_FILE_OR_DIR_EXCEPTION, [$target, 'target']);
            $status = false;
        }

        if (\preg_match(self::RESTRICTED_SYMBOLS, $target)) {
            self::triggerEvent(self::RENAME_FILE_OR_DIR_EXCEPTION, [$target]);
            $status = false;
        }

        if ($status) {
            try {
                $status = \rename($source, $target);
                self::triggerEvent(self::RENAME_FILE_OR_DIR_AFTER, [$source, $target]);
            } catch (\Throwable $exception) {
                $status = false;
                self::triggerEvent(self::RENAME_FILE_OR_DIR_EXCEPTION, [$source, $target, $exception]);
            }
        }

        return $status;
    }

    /**
     * move file or directory to given target
     *
     * @param string $source
     * @param string $target
     * @param bool $force
     * @return array
     */
    public static function move(string $source, string $target, bool $force = false): array
    {
        self::triggerEvent('move_file_or_directory_before', [&$source, &$target]);

        $status = self::copy($source, $target, $force);

        if (!self::validateComplexOutput($status)) {
            self::triggerEvent('move_file_or_directory_error', [$source, $target, $status]);
            return [];
        }

        $status = \array_merge(self::delete($source, $force), $status);

        if (!self::validateComplexOutput($status)) {
            self::triggerEvent('move_file_or_directory_error', [$source, $target, $status]);
            return [];
        }

        self::triggerEvent('move_file_or_directory_after', [$source, $target, $status]);

        return $status;
    }

    /**
     * @param EventDispatcherInterface $eventHandler
     */
    public static function configureEventHandler(EventDispatcherInterface $eventHandler): void
    {
        if ($eventHandler) {
            self::$event = $eventHandler;
        }
    }

    /**
     * disable event handler by removing it from protected variable
     */
    public static function removeEventHandler(): void
    {
        self::$event = null;
    }

    /**
     * Check that complex output array (like from Fs::copy) is finished with full success or with some fail
     * If $output is empty, also return false
     *
     * @param array $output
     * @return bool
     */
    public static function validateComplexOutput(array $output): bool
    {
        if (empty($output)) {
            return false;
        }

        $status = true;

        foreach ($output as $execStatus) {
            $status &= $execStatus === true;
        }

        return $status;
    }

    /**
     * @param string $name
     * @param array $data
     */
    protected static function triggerEvent(string $name, array $data): void
    {
        if (self::$event instanceof EventDispatcherInterface) {
            self::$event->triggerEvent($name, $data);
        }
    }
}
