<?php

namespace BlueFilesystem\StaticObjects;

class Structure
{
    /**
     * @var array
     */
    protected $dirTree = [];

    /**
     * @var array
     */
    protected $paths = [
        'dir' => [],
        'file' => [],
    ];

    /**
     * @var string
     */
    protected $path;

    /**
     * @var bool
     */
    protected $recursive;

    /**
     * @param string $path
     * @param bool $recursive
     * @example new Structure('dir/some_dir')
     * @example new Structure('dir/some_dir', true)
     */
    public function __construct(string $path, bool $recursive = false)
    {
        $this->path = $path;
        $this->recursive = $recursive;

        $this->readDirectory($path, $recursive);
    }

    /**
     * re read directory content, (optionally all sub folders)
     *
     * @param string $path
     * @param boolean $recursive
     * @return array
     * @example readDirectory('dir/some_dir')
     * @example readDirectory('dir/some_dir', true)
     */
    public function readDirectory(string $path, bool $recursive = false): array
    {
        $this->dirTree = $this->readDirectoryRecursive($path, $recursive);

        return $this->dirTree;
    }

    /**
     * @param string $path
     * @param boolean $recursive
     * @return array
     */
    protected function readDirectoryRecursive(string $path, bool $recursive): array
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

            //@todo save dir asSplFileInfo
            if ($recursive && $element->isDir()) {
                $list[$element->getRealPath()] = $this->readDirectoryRecursive($element->getRealPath(), true);
            } else {
                $list[$element->getRealPath()] = $element->getFileInfo();
            }
        }

        return $list;
    }

    /**
     * @return array
     */
    public function getReadDirectory(): array
    {
        return $this->dirTree;
    }

    /**
     * @return array
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * transform array wit directory/files tree to list of paths grouped on files and directories
     *
     * @param boolean $reverse if TRUE revert array (required for deleting)
     * @internal param string $path base path for elements, if empty use paths from transformed structure
     * @return array array with path list for files and directories
     * @example returnPaths(true)
     * @example returnPaths()
     */
    public function returnPaths(bool $reverse = false): array
    {
        $array = $this->dirTree;

        if ($reverse) {
            $array = \array_reverse($array);
        }

        $this->paths = $this->returnPathsRecursive($array);

        return $this->paths;
    }

    /**
     * @param callable $callback
     * @param bool $reload
     * @param array $params (argument unpacking by ...)
     */
    public function processSplObjects(callable $callback, bool $reload = true, ...$params): void
    {
        $this->processSplObjectsStructure($callback, $this->dirTree, ...$params);

        if ($reload) {
            $this->dirTree = $this->readDirectory($this->path, $this->recursive);
        }
    }

    /**
     * @param callable $callback
     * @param array $array
     * @param array $params (argument unpacking by ...)
     */
    protected function processSplObjectsStructure(callable $callback, array $array, ...$params): void
    {
        foreach ($array as $path => $fileInfo) {
            $isDir = \is_dir($path);
            if (\is_array($fileInfo) && $isDir) {
                $this->processSplObjectsStructure($callback, $fileInfo, ...$params);
                $callback(new \SplFileInfo($path), $path, ...$params);
            } else {
                $callback($fileInfo, $path, ...$params);
            }
        }
    }

    /**
     * transform array wit directory/files tree to list of paths grouped on files and directories
     *
     * @param array $array array to transform
     * @internal param string $path base path for elements, if empty use paths from transformed structure
     * @return array array with path list for files and directories
     * @example returnPaths(true)
     * @example returnPaths()
     */
    protected function returnPathsRecursive(array $array): array
    {
        $pathList = [
            'dir' => [],
            'file' => [],
        ];

        foreach ($array as $path => $fileInfo) {
            $pathList = $this->processNodeInfo($pathList, $fileInfo, $path);
        }

        return $pathList;
    }

    /**
     * @param array $pathList
     * @param array|\DirectoryIterator $fileInfo
     * @param string $path
     * @return array
     */
    protected function processNodeInfo(array $pathList, $fileInfo, string $path): array
    {
        $isDir = \is_dir($path);

        if (\is_array($fileInfo) && $isDir) {
            $list = $this->returnPathsRecursive($fileInfo);

            /** @var string $element */
            foreach ($list as $element => $value) {
                $pathList = $this->setPath($pathList, $element, $value, 'file');
                $pathList = $this->setPath($pathList, $element, $value, 'dir');
            }

            $pathList['dir'][] = $path;
        } else {
            /** @var \DirectoryIterator $fileInfo */
            $pathList['file'][] = $fileInfo->getRealPath();
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
    protected function setPath(array $pathList, string $key, array $value, string $type): array
    {
        if ($key === $type) {
            foreach ($value as $path) {
                $pathList[$type][] = $path;
            }
        }

        return $pathList;
    }
}
