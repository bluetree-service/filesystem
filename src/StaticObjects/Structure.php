<?php

namespace BlueFilesystem\StaticObjects;

class Structure implements FsInterface
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
     * @param string $path
     * @param bool $recursive
     * @example new Structure('dir/some_dir')
     * @example new Structure('dir/some_dir', true)
     */
    public function __construct(string $path, bool $recursive = false)
    {
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
            if (\is_array($fileInfo) && \is_dir($path)) {
                $list = $this->returnPathsRecursive($fileInfo);

                /** @var string $element */
                foreach ($list as $element => $value) {
                    $pathList = $this->setPath($pathList, $element, $value, 'file');
                    $pathList = $this->setPath($pathList, $element, $value, 'dir');
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
