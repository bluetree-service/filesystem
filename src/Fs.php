<?php

namespace BlueFilesystem;

use DirectoryIterator;

class Fs
{
    /**
     * restricted characters for file and directory names
     * @var string
     */
    const RESTRICTED_SYMBOLS = '#[:?*<>"|\\\]#';

//    public function __construct($path, $register = null)
//    {
//        if (is_null($register)) {
//            $this->register = new Register;
//        }
//        
//        $this->path = $path;
//    }

    /**
     * remove file or directory with all content
     *
     * @param string $path
     * @return boolean|array information that operation was successfully, or NULL if path incorrect
     */
    public static function delete($path)
    {
//        Loader::callEvent('delete_path_content_before', [&$path]);

        $bool = [];

        if (!self::exist($path)) {
            return null;
        }

        @chmod($path, 0777);

        if (is_dir($path)) {
            $list = self::readDirectory($path, true);
            $paths = self::returnPaths($list, true);

            if (isset($paths['file'])) {
                foreach ($paths['file'] as $val) {
                    $bool[] = unlink($val);
                }
            }

            if (isset($paths['dir'])) {
                foreach ($paths['dir'] as $val) {
                    $bool[] = rmdir($val);
                }
            }

            rmdir($path);
        } else {
            $bool[] = @unlink($path);
        }

        if (in_array(false, $bool)) {
//            Loader::callEvent('delete_path_content_error', [$bool, $path]);
            return false;
        }

//        Loader::callEvent('delete_path_content_after', $path);

        return $bool;
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
//        Loader::callEvent('copy_path_content_before', [&$path, &$target]);

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
////                    Loader::callEvent('copy_path_content_error', [$path, $target]);
//                    return null;
//                }
//            }

            $bool[] = copy($path, $target);
        }

        if (in_array(false, $bool)) {
//            Loader::callEvent('copy_path_content_error', [$bool, $path, $target]);
            return false;
        }

//        Loader::callEvent('copy_path_content_after', [$path, $target]);

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
//        Loader::callEvent('create_directory_before', [&$path]);

        $bool = preg_match(self::RESTRICTED_SYMBOLS, $path);

        if (!$bool) {
            $bool = mkdir($path);
//            Loader::callEvent('create_directory_after', $path);
            return $bool;
        }

//        Loader::callEvent('create_directory_error', $path);

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
//        Loader::callEvent('create_file_before', [&$path, &$fileName, &$data]);

        if (!self::exist($path)) {
            self::mkdir($path);
        }

        $bool = preg_match(self::RESTRICTED_SYMBOLS, $fileName);

        if (!$bool) {
            $fileResource = @fopen("$path/$fileName", 'w');
            fclose($fileResource);

            if ($data) {
                $bool = file_put_contents("$path/$fileName", $data);
//                Loader::callEvent('create_file_after', [$path, $fileName]);
                return $bool;
            }
        }

//        Loader::callEvent('create_file_error', [$path, $fileName]);

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
//        Loader::callEvent('rename_file_or_directory_before', [&$source, &$target]);

        if (!self::exist($source)) {
//            Loader::callEvent('rename_file_or_directory_error', [$source, 'source']);
            return null;
        }

        if (self::exist($target)) {
//            Loader::callEvent('rename_file_or_directory_error', [$target, 'target']);
            return false;
        }

        $bool = preg_match(self::RESTRICTED_SYMBOLS, $target);

        if (!$bool) {
            $bool = rename($source, $target);
//            Loader::callEvent('rename_file_or_directory_after', [$source, $target]);
            return $bool;
        }

//        Loader::callEvent('rename_file_or_directory_error', [$source, $target]);

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
//        Loader::callEvent('move_file_or_directory_before', [&$source, &$target]);
        $bool = self::copy($source, $target);

        if (!$bool) {
//            Loader::callEvent('move_file_or_directory_error', [$source, $target]);
            return false;
        }

        $bool = self::delete($source);
//        Loader::callEvent('move_file_or_directory_after', [$source, $target, $bool]);

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
