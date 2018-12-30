<?php

namespace BlueFilesystem\StaticObjects;

class Structure implements FsInterface
{
    /**
     * read directory content, (optionally all sub folders)
     *
     * @param string $path
     * @param boolean $recursive
     * @return array
     * @example readDirectory('dir/some_dir')
     * @example readDirectory('dir/some_dir', true)
     * @example readDirectory(); - read MAIN_PATH destination
     */
    public static function readDirectory(string $path, bool $recursive = false): array
    {
        $list = [];

        if (!self::exist($path)) {
            return [];
        }

        $iterator = new \DirectoryIterator($path);

        /** @var \DirectoryIterator $element */
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
     * @internal param string $path base path for elements, if empty use paths from transformed structure
     * @return array array with path list for files and directories
     * @example returnPaths($array, '')
     * @example returnPaths($array, '', true)
     * @example returnPaths($array, 'some_dir/dir', true)
     */
    public static function returnPaths(array $array, bool $reverse = false): array
    {
        if ($reverse) {
            $array = array_reverse($array);
        }

        $pathList = [
            'dir' => [],
            'file' => [],
        ];

        foreach ($array as $path => $fileInfo) {
            if (\is_array($fileInfo) && \is_dir($path)) {
                $list = self::returnPaths($fileInfo);

                /** @var string $element */
                foreach ($list as $element => $value) {
                    $pathList = self::setPath($pathList, $element, $value, 'file');
                    $pathList = self::setPath($pathList, $element, $value, 'dir');
                }

                $pathList['dir'][] = $path;
            } elseif ($fileInfo instanceof \SplFileInfo && \is_dir($path)) {
                $pathList['dir'][] = $fileInfo->getRealPath();
            } else {
                /** @var \DirectoryIterator $fileInfo */
                $pathList['file'][] = $fileInfo->getRealPath();
            }
        }

        return $pathList;
    }

    /**
     * check that file exists
     *
     * @param string $path
     * @return boolean true if exists, false if not
     */
    public static function exist(string $path): bool
    {
        return \file_exists($path);
    }

    /**
     * @param array $pathList
     * @param string $key
     * @param array $value
     * @param string $type
     * @return array
     */
    protected static function setPath(array $pathList, string $key, array $value, string $type): array
    {
        if ($key === $type) {
            foreach ($value as $path) {
                $pathList[$type][] = $path;
            }
        }

        return $pathList;
    }
}
