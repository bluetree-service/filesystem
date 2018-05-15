<?php

namespace BlueFilesystem;

use Exception;
use DirectoryIterator;
use BlueContainer\Container;

class Directory extends Container implements ModelInterface
{
    /**
     * base configuration for directory
     *
     * @var array
     */
    protected $directoryData = [
        'main_path' => '',
        'child_files' => [],
        'child_directories' => [],
        'size' => 0,
        'file_count' => 0,
        'directory_count' => 0,
        'to_delete' => [],
        'permissions' => 0755,
        'at_time' => null,
        'ct_time' => null,
        'mt_time' => null,
    ];

    /**
     * create or read given directory
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
//        Loader::callEvent('directory_object_instance_before', [&$data]);

        $data = array_merge($this->directoryData, $data);
        parent::__construct($data);

//        Loader::callEvent('directory_object_instance_after', [&$data]);
    }

    /**
     * load directory structure into object
     *
     * @return $this
     * @throws Exception
     */
    public function load()
    {
//        Loader::callEvent('load_directory_object_instance_before', $this);

        if (!Fs::exist($this->getMainPath())) {
//            Loader::callEvent('load_directory_object_instance_error', $this);
            throw new Exception('directory not exists: ' . $this->getMainPath());
        }

        $iterator = new DirectoryIterator($this->getMainPath());
        $files = 0;
        $directories = 0;
        $totalSize = 0;

        /** @var DirectoryIterator $element */
        foreach ($iterator as $element) {
            if ($element->isDot()) {
                continue;
            }

            if ($element->isDir()) {
                $this->createDirectoryInstance($element, $directories, $files, $totalSize);
            } else {
                $this->createFileInstance($element, $totalSize, $files);
            }
        }

        $this->setFileCount($this->getFileCount() + $files);
        $this->setDirectoryCount($directories);
        $this->setSize($totalSize);

//        Loader::callEvent('load_directory_object_instance_after', $this);
        return $this;
    }

    /**
     * create file object instance
     *
     * @param DirectoryIterator $element
     * @param int $totalSize
     * @param int $files
     * @return $this
     * @throws Exception
     */
    protected function createFileInstance(DirectoryIterator $element, &$totalSize, &$files)
    {
        $name = str_replace(
            '.' . $element->getExtension(),
            '',
            $element->getBasename()
        );
        $fileList = $this->getChildFiles();

        /** @var File $newFile */
        $newFile = new File([
            'main_path' => $this->getMainPath(),
            'size' => $element->getSize(),
            'permissions' => $element->getPerms(),
            'at_time' => $element->getATime(),
            'ct_time' => $element->getCTime(),
            'mt_time' => $element->getMTime(),
            'name' => $name,
            'extension' => $element->getExtension(),
        ]);

        $newFile->load();
        $totalSize += $element->getSize();
        $fileList[] = $newFile;
        $this->setChildFiles($fileList);
        $files++;

        return $this;
    }

    /**
     * create directory object instance
     *
     * @param DirectoryIterator $element
     * @param int $directories
     * @param int $files
     * @param int $totalSize
     * @return $this
     * @throws Exception
     */
    protected function createDirectoryInstance(
        DirectoryIterator $element,
        &$directories,
        &$files,
        &$totalSize
    ) {
        $directoryList = $this->getChildDirectories();

        /** @var Directory $newDirectory */
        $newDirectory = new Directory([
            'main_path' => $element->getRealPath(),
        ]);

        $newDirectory->load();
        $directoryList[] = $newDirectory;
        $this->setChildDirectories($directoryList);

        $directories += $newDirectory->getDirectoryCount() +1;
        $files += $newDirectory->getFileCount();
        $totalSize += $newDirectory->getSize();

        return $this;
    }

    /**
     * remove directory, or object data if directory not exists
     *
     * @return $this
     * @throws Exception
     */
    public function delete()
    {
//        Loader::callEvent('delete_directory_object_instance_before', $this);

        if (Fs::exist($this->getMainPath())) {
            $bool = Fs::delete($this->getMainPath());

            if (!$bool) {
//                Loader::callEvent('delete_directory_object_instance_error', $this);
                throw new Exception('unable to remove directory: ' . $this->getMainPath());
            }
        }

        $this->destroy();
//        Loader::callEvent('delete_directory_object_instance_after', $this);

        return $this;
    }

    /**
     * save all files and directories from object
     *
     * @return $this
     * @throws Exception
     */
    public function save()
    {
//        Loader::callEvent('save_directory_object_instance_before', $this);

        if (empty($this->_DATA)) {
            return $this;
        }

        if ($this->getToDelete()) {
            $this->_errorsList[] = 'directory must be removed, cannot be saved: ' . $this->getMainPath();
//            Loader::callEvent('save_directory_object_instance_error', $this);
            return $this;
        }

        $files = 0;
        $directories  = 0;
        $totalSize = 0;

        $this->saveMe();
        $this->saveDirectories($directories, $files, $totalSize);
        $this->saveFiles($files, $totalSize);

        $this->setFileCount($files);
        $this->setDirectoryCount($directories);
        $this->setSize($totalSize);

        if ($this->hasErrors()) {
//            Loader::log('exception', $this->getObjectError(), 'directory io operation');

            $message = '';
            foreach ($this->getObjectError() as $error) {
                $message .= $error['message'] . ',';
            }
            throw new Exception(rtrim($message, ','));
        }

//        Loader::callEvent('save_directory_object_instance_after', $this);
        return $this;
    }

    /**
     * create main directory if not exists
     *
     * @return $this
     * @throws Exception
     */
    protected function saveMe()
    {
        if (!Fs::exist($this->getMainPath())) {
            $bool = Fs::mkdir($this->getMainPath());

            if (!$bool) {
                throw new Exception('unable to save main directory: ' . $this->getMainPath());
            }
        }

        return $this;
    }

    /**
     * save directories from list
     *
     * @param int $directories
     * @param int $files
     * @param int $totalSize
     * @return $this
     */
    protected function saveDirectories(&$directories, &$files, &$totalSize)
    {
        /** @var Directory $child */
        foreach ($this->getChildDirectories() as $child) {
            try {
                //replace mainpath, usunie jesli juz istnieje
                //str_replace();

                $child->setMainPath($this->getMainPath() . $child->getMainPath());
                $child->save();
                $directories++;
                $directories += $child->getDirectoryCount();
                $files += $child->getFileCount();
                $totalSize += $child->getSize();
            } catch (Exception $e) {
                $this->_errorsList[$e->getCode()] = [
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                    'trace' => $e->getTraceAsString(),
                ];
            }
        }

        return $this;
    }

    /**
     * save files from list
     *
     * @param int $files
     * @param int $totalSize
     * @return $this
     */
    protected function saveFiles(&$files, &$totalSize)
    {
        /** @var File $child */
        foreach ($this->getChildFiles() as $child) {
            try {
                $child->setMainPath($this->getMainPath());
                $child->save();
                $files++;
                $totalSize += $child->getSize();
            } catch (Exception $e) {
                $this->_errorsList[$e->getCode()] = [
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                    'trace' => $e->getTraceAsString(),
                ];
            }
        }

        return $this;
    }

    /**
     * create child in object
     * as parameter give Disc object or configuration array
     * if in array name will be set up, will create file
     *
     * @param File|Directory|array $child
     * @return $this
     */
    public function addChild($child)
    {
        if (is_array($child)) {
            if (isset($child['name'])) {
                $child = new File($child);
            } else {
                $child = new Directory($child);
            }
        }

        $this->addChildDirectory($child);
        $this->addChildFile($child);

        return $this;
    }

    /**
     * create child file in object
     * as parameter give Disc object or configuration array
     * if in array name will be set up, will create file
     *
     * @param File|array $child
     * @return Directory
     */
    public function addChildFile($child)
    {
        if (is_array($child)) {
            $child = new File($child);
        }

        if (!$child instanceof File) {
            return $this;
        }

        $child->setMainPath($this->getMainPath());
        $children = $this->getChildFiles();
        $children[] = $child;
        $this->setChildFiles($children);
        $this->setFileCount($this->getFileCount() +1);
        $this->setSize($this->getSize() + $child->getSize());

        return $this;
    }

    /**
     * create child directory in object
     * as parameter give Disc object or configuration array
     * if in array name will be set up, will create file
     *
     * @param Directory|array $child
     * @return Directory
     */
    public function addChildDirectory($child)
    {
        if (is_array($child)) {
            $child = new Directory($child);
        }

        if (!$child instanceof Directory) {
            return $this;
        }

        $child->setMainPath($this->getMainPath() . $child->getMainPath());
        $children = $this->getChildDirectories();
        $children[] = $child;
        $this->setChildDirectories($children);
        $this->setDirectoryCount($this->getDirectoryCount() + $child->getDirectoryCount() +1);
        $this->setFileCount($this->getFileCount() + $child->getFileCount());
        $this->setSize($this->getSize() + $child->getSize());

        return $this;
    }

    /**
     *
     */
    public function move($destination)
    {
        //wykonac move fizyczne dla dira
        //rekursywnie zastapic wszystkim elementom mainPath
        //po wszystkim wywolanie remove
    }

    /**
     *
     */
    public function rename($newName, $prefix = null, $level = 0)
    {
        //zmienic namepath wszystkim dzieciom (i tu jest rebus :/)
        //albo wywolac rename dla kazdego dira?
    }

    public function copy($destination)
    {
        
    }
}
