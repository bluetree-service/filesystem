<?php

namespace BlueFilesystem\StaticObjects;

use DirectoryIterator;
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

        if (!self::exist($path)) {
            return [];
        }

        $isDir = \is_dir($path);

        if ($isDir) {
            $list = self::readDirectory($path, true);
            $paths = self::returnPaths($list, true);

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
            chmod($path, 0777);
        }
        foreach ($paths['dir'] as $path) {
            chmod($path, 0777);
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
                $operationList[$path] = rmdir($path);
            } else {
                $operationList[$path] = unlink($path);
            }
        } catch (\Throwable $exception) {
            $operationList[$path] = $exception->getMessage();
            self::triggerEvent(self::DELETE_PATH_CONTENT_EXCEPTION, [&$operationList, $path, $exception]);
        }

        return $operationList;
    }

    /**
     * copy file or directory to given source
     * if source directory not exists, create it
     *
     * @param string $path
     * @param string $target
     * @return boolean information that operation was successfully, or NULL if path incorrect
     */
    public static function copy($path, $target)
    {
        //self::setForceMode($paths, $force);
        self::triggerEvent('copy_path_content_before', [&$path, &$target]);

        $bool = [];

        if (!self::exist($path)) {
            return null;
        }

        if (is_dir($path)) {
            if (!self::exist($target)) {
                $bool[] = mkdir($target);
            }

            $elements = self::readDirectory($path);
            $paths = self::returnPaths($elements);

            foreach ($paths['dir'] as $dir) {
                $bool[] = mkdir($dir);
            }

            foreach ($paths['file'] as $file) {
                $bool[] = copy($path . "/$file", $target . "/$file");
            }

        } else {
            if (!$target) {
                $filename = explode('/', $path);
                $target = end($filename);
            }
//            else {
//                $bool = self::mkdir($target);
//                if ($bool) {
////                    self::triggerEvent('copy_path_content_error', [$path, $target]);
//                    return null;
//                }
//            }

            $bool[] = copy($path, $target);
        }

        if (in_array(false, $bool)) {
//            self::triggerEvent('copy_path_content_error', [$bool, $path, $target]);
            return false;
        }

        self::triggerEvent('copy_path_content_after', [$path, $target]);

        return true;
    }

    /**
     * create new directory in given location
     *
     * @param string $path
     * @return boolean
     * @static
     */
    public static function mkdir($path)
    {
        //self::setForceMode($paths, $force);
        self::triggerEvent('create_directory_before', [&$path]);

        $bool = preg_match(self::RESTRICTED_SYMBOLS, $path);

        if (!$bool) {
            $bool = mkdir($path);
//            self::triggerEvent('create_directory_after', $path);
            return $bool;
        }

        self::triggerEvent('create_directory_error', $path);

        return false;
    }

    /**
     * create empty file, and optionally put in them some data
     *
     * @param string $path
     * @param string $fileName
     * @param mixed $data
     * @return boolean information that operation was successfully, or NULL if path incorrect
     * @example mkfile('directory/inn', 'file.txt')
     * @example mkfile('directory/inn', 'file.txt', 'Lorem ipsum')
     */
    public static function mkfile($path, $fileName, $data = null)
    {
        //self::setForceMode($paths, $force);
        self::triggerEvent('create_file_before', [&$path, &$fileName, &$data]);

        if (!self::exist($path)) {
            self::mkdir($path);
        }

        $bool = preg_match(self::RESTRICTED_SYMBOLS, $fileName);

        if (!$bool) {
            $fileResource = @fopen("$path/$fileName", 'w');
            fclose($fileResource);

            if ($data) {
                $bool = file_put_contents("$path/$fileName", $data);
//                self::triggerEvent('create_file_after', [$path, $fileName]);
                return $bool;
            }
        }

        self::triggerEvent('create_file_error', [$path, $fileName]);

        return false;
    }

    /**
     * change name of file/directory
     * also can be used to copy operation
     *
     * @param string $source original path or name
     * @param string $target new path or name
     * @return boolean information that operation was successfully, or NULL if path incorrect
     */
    public static function rename($source, $target)
    {
        //elf::setForceMode($paths, $force);
        self::triggerEvent('rename_file_or_directory_before', [&$source, &$target]);

        if (!self::exist($source)) {
//            self::triggerEvent('rename_file_or_directory_error', [$source, 'source']);
            return null;
        }

        if (self::exist($target)) {
//            self::triggerEvent('rename_file_or_directory_error', [$target, 'target']);
            return false;
        }

        $bool = preg_match(self::RESTRICTED_SYMBOLS, $target);

        if (!$bool) {
            $bool = rename($source, $target);
//            self::triggerEvent('rename_file_or_directory_after', [$source, $target]);
            return $bool;
        }

        self::triggerEvent('rename_file_or_directory_error', [$source, $target]);

        return false;
    }

    /**
     * move file or directory to given target
     *
     * @param string $source
     * @param string $target
     * @return bool
     */
    public static function move($source, $target)
    {
        //elf::setForceMode($paths, $force);
        self::triggerEvent('move_file_or_directory_before', [&$source, &$target]);
        $bool = self::copy($source, $target);

        if (!$bool) {
//            self::triggerEvent('move_file_or_directory_error', [$source, $target]);
            return false;
        }

        $bool = self::delete($source);
        self::triggerEvent('move_file_or_directory_after', [$source, $target, $bool]);

        return $bool;
    }

    /**
     * read directory content, (optionally all sub folders)
     *
     * @param string $path
     * @param boolean $recursive
     * @return array
     * @example readDirectory('dir/some_dir')
     * @example readDirectory('dir/some_dir', TRUE)
     * @example readDirectory(); - read MAIN_PATH destination
     */
    public static function readDirectory($path, $recursive = false)
    {
        $list = [];

        if (!self::exist($path)) {
            return [];
        }

        $iterator = new DirectoryIterator($path);

        /** @var DirectoryIterator $element */
        foreach ($iterator as $element) {
            if ($element->isDot()) {
                continue;
            }

            if ($recursive && $element->isDir()) {
                $list[$element->getRealPath()] = self::readDirectory($element->getRealPath(), true);
            } else {
                $list[$element->getRealPath()] = $element->getFileInfo();
            }

        }

        return $list;
    }

    /**
     * transform array wit directory/files tree to list of paths grouped on files and directories
     *
     * @param array $array array to transform
     * @param boolean $reverse if TRUE revert array (required for deleting)
     * @internal param string $path base path for elements, if emty use paths from transformed structure
     * @return array array with path list for files and directories
     * @example returnPaths($array, '')
     * @example returnPaths($array, '', TRUE)
     * @example returnPaths($array, 'some_dir/dir', TRUE)
     */
    public static function returnPaths(array $array, $reverse = false)
    {
        if ($reverse) {
            $array = array_reverse($array);
        }

        $pathList = [];

        foreach ($array as $path => $fileInfo) {
            if (is_dir($path)) {
                $list = self::returnPaths($fileInfo);

                foreach ($list as $element => $value) {
                    if ($element === 'file') {
                        foreach ($value as $file) {
                            $pathList['file'][] = $file;
                        }
                    }

                    if ($element === 'dir') {
                        foreach ($value as $dir) {
                            $pathList['dir'][] = $dir;
                        }
                    }

                }
                $pathList['dir'][] = $path;

            } else {
                /** @var DirectoryIterator $fileInfo */
                $pathList['file'][] = $fileInfo->getRealPath();
            }
        }

        return $pathList;
    }

    /**
     * check that file exists
     *
     * @param string $path
     * @return boolean TRUE if exists, FALSE if not
     */
    public static function exist($path)
    {
        return file_exists($path);
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
