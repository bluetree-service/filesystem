<?php

namespace BlueFilesystem;

use BlueFilesystem\StaticObjects\{
    FsInterface,
    Fs as StaticFs,
};

class Fs
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var null|FsInterface
     */
    protected $fileSystem;

    /**
     * @var array
     */
    protected $operationList = [];

    /**
     * @param string $path must be a directory or future directory
     * @param FsInterface|null $fileSystem
     */
    public function __construct(string $path, ?FsInterface $fileSystem = null)
    {
        $this->path = $path;

        if ($fileSystem) {
            $this->fileSystem = $fileSystem;
        } else {
            $this->fileSystem = new StaticFs;
        }

        if (!$this->isExists()) {
            $this->create();
        }
    }

    /**
     * remove directory with all content
     *
     * @param string|null $path
     * @param bool $force
     * @return bool
     */
    public function delete(?string $path = null, bool $force = false): bool
    {
        $this->operationList = $this->fileSystem::delete($path ?? $this->path, $force);

        return $this->fileSystem::validateComplexOutput($this->operationList);
    }

    /**
     * @return array
     */
    public function getLatestOperationList(): array
    {
        return $this->operationList;
    }

    /**
     * copy file or directory to given source
     * if source directory not exists, create it
     *
     * @param string $target
     * @param string|null $path
     * @param bool $force
     * @return bool
     */
    public function copy(string $target, ?string $path, bool $force = false): bool
    {
        $this->operationList = $this->fileSystem::copy($path ?? $this->path, $target, $force);

        return $this->fileSystem::validateComplexOutput($this->operationList);
    }

    /**
     * create new directory in given location
     *
     * @param string $path
     * @return boolean
     * @static
     */
    public function mkdir(?string $path = null): bool
    {
        $this->operationList = $this->fileSystem::mkdir($path ?? $this->path);

        return $this->fileSystem::validateComplexOutput($this->operationList);
    }

    public function mkdirs()
    {
        
    }

    /**
     * create empty file, and optionally put in them some data
     *
     * @param string $path
     * @param string $fileName
     * @param mixed $data
     * @return bool
     * @example mkfile('file.txt', 'directory/inn')
     * @example mkfile('file.txt', 'directory/inn', 'Lorem ipsum')
     */
    public function mkfile(string $fileName, ?string $path, $data = null)
    {
        $this->operationList = $this->fileSystem::mkfile($path ?? $this->path, $fileName, $data);

        return $this->fileSystem::validateComplexOutput($this->operationList);
    }

    /**
     * @param array $files
     * @return bool
     * @example mkfiles([['name' => 'bar.txt', 'data' => 'lorem ipsum']])
     * @example mkfiles([['path' => 'foo/', 'name' => 'bar.txt', 'data' => 'lorem ipsum']])
     * @example mkfiles([['name' => 'bar.txt']])
     */
    public function mkfiles(array $files): bool
    {
        foreach ($files as $file) {
            $this->mkfile();
        }
    }

    public function create()
    {
        //create directory with given path
        //throw exception if unable to create
    }

    /**
     * change name of file/directory
     * also can be used to copy operation
     *
     * @param string $source original path or name
     * @param string $target new path or name
     * @return boolean information that operation was successfully, or NULL if path incorrect
     */
    public function rename($source, $target)
    {
        
    }

    /**
     * move file or directory to given target
     *
     * @param string $source
     * @param string $target
     * @return bool
     */
    public function move($source, $target)
    {
        
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
    public function readDirectory($path, $recursive = false)
    {
        
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
    public function returnPaths(array $array, $reverse = false)
    {
        
    }

    /**
     * check that file exists
     *
     * @param string $path
     * @return boolean TRUE if exists, FALSE if not
     */
    public function isExists(): bool
    {
        return file_exists($this->path);
    }

    /**
     * @param string $name
     * @param array $data
     * @return $this
     */
    protected function callEvent($name, array $data)
    {
        if (!is_null($this->event)) {
            $this->event->callEvent($name, $data);
        }

        return $this;
    }
}
