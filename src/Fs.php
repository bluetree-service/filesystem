<?php

namespace BlueFilesystem;

class Fs
{
    public function __construct($path, $register = null)
    {
        if (is_null($register)) {
            $this->register = new Register;
        }

        $this->path = $path;
    }

    /**
     * remove file or directory with all content
     *
     * @param string $path
     * @return boolean|array information that operation was successfully, or NULL if path incorrect
     */
    public function delete($path, $force = false)
    {
        
    }

    /**
     * copy file or directory to given source
     * if source directory not exists, create it
     *
     * @param string $path
     * @param string $target
     * @return boolean information that operation was successfully, or NULL if path incorrect
     */
    public function copy($path, $target)
    {
        
    }

    /**
     * create new directory in given location
     *
     * @param string $path
     * @return boolean
     * @static
     */
    public function mkdir($path)
    {
        
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
    public function mkfile($path, $fileName, $data = null)
    {
        
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
    public function exist($path)
    {
        return file_exists($path);
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
