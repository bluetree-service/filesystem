<?php

namespace Filesystem\Model;

use Core\Blue\Model\Object;
use Core\Disc\Helper\Common;
use Core\Incoming\Model;
use Loader;
use Exception;
use SplFileInfo;

class File extends Object implements ModelInterface
{
    /**
     * base configuration for file
     *
     * @var array
     */
    protected $fileData = [
        'main_path' => '',
        'size' => 0,
        'to_delete' => false,
        'permissions' => 0755,
        'at_time' => null,
        'ct_time' => null,
        'mt_time' => null,
        'content' => '',
        'name' => '',
        'extension' => '',
        'force_save' => false,
    ];

    /**
     * create file instance
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
//        Loader::callEvent('file_object_instance_before', [&$data]);

        $data = array_merge($this->fileData, $data);
        parent::__construct($data);

//        Loader::callEvent('file_object_instance_after', [&$data]);
    }

    /**
     * remove file, or object data if file not exists
     *
     * @return $this
     * @throws Exception
     */
    public function delete()
    {
        Loader::callEvent('delete_file_object_instance_before', $this);

        if (Model\File::exist($this->getFullPath())) {
            $bool = Common::delete($this->getFullPath());

            if (!$bool) {
//                Loader::callEvent('delete_file_object_instance_error', $this);
                throw new Exception('unable to remove file: ' . $this->getFullPath());
            }
        }

        $this->unsetData();
//        Loader::callEvent('delete_file_object_instance_after', $this);

        return $this;
    }

    /**
     * save file object
     *
     * @return $this
     * @throws Exception
     */
    public function save()
    {
//        Loader::callEvent('save_file_object_instance_before', $this);

        if (empty($this->_DATA)) {
            return $this;
        }

        if ($this->getToDelete()) {
            $this->_errorsList[] = 'file must be removed, cannot be saved: ' . $this->getFullPath();
//            Loader::callEvent('save_file_object_instance_error', $this);
            return $this;
        }

        $bool = Common::mkfile(
            $this->getMainPath(),
            $this->getFullName(),
            $this->getContent()
        );

        @chmod($this->getFullPath(), $this->getPermissions());
        $this->updateFileInfo();

        if (!$bool) {
//            Loader::callEvent('save_file_object_instance_error', $this);
            throw new Exception('unable to save file: ' . $this->getFullPath());
        }

//        Loader::callEvent('save_file_object_instance_after', $this);

        return $this;
    }

    /**
     * load file into object
     *
     * @return $this
     * @throws Exception
     */
    public function load()
    {
//        Loader::callEvent('load_file_object_instance_before', $this);

        if (!Model\File::exist($this->getFullPath())) {
//            Loader::callEvent('load_file_object_instance_error', $this);
            throw new Exception('file not exists: ' . $this->getFullPath());
        }

        $content = file_get_contents($this->getFullPath());
        $this->updateFileInfo();
        $this->setContent($content);

//        Loader::callEvent('load_file_object_instance_after', $this);
        return $this;
    }

    /**
     * return full path with file name and extension
     *
     * @return string
     */
    protected function getFullPath()
    {
        $mainPath = rtrim($this->getMainPath(), '/');
        return $mainPath . '/' . $this->getFullName();
    }

    /**
     * return file name with extension
     *
     * @return string
     */
    protected function getFullName()
    {
        if ($this->getExtension()) {
            return $this->getName() . '.' . $this->getExtension();
        }

        return $this->getName();
    }

    /**
     * move file to other location
     *
     * @param string $destination
     * @throws Exception
     * @return $this
     */
    public function move($destination)
    {
//        Loader::callEvent('move_file_object_instance_before', [$this, &$destination]);

        if (Model\File::exist($this->getFullPath())) {
            $targetPath = $destination . $this->getFullName();
            $bool = Common::move(
                $this->getFullPath(),
                $targetPath
            );
        } else {
            $bool = Common::mkfile(
                $destination,
                $this->getFullName(),
                $this->getContent()
            );
        }

        if (!$bool) {
//            Loader::callEvent('move_file_object_instance_error', [$this, $destination]);
            throw new Exception(
                'unable to move file:'
                . $this->getFullPath()
                . ' -> '
                . $destination
            );
        }

        $this->setMainPath($destination);
        $this->updateFileInfo();
        $this->replaceDataArrays();

//        Loader::callEvent('move_file_object_instance_after', $this);

        return $this;
    }

    /**
     * copy file to other location
     * !!!! AFTER COPY RETURN INSTANCE OF COPIED FILE, NOT BASE FILE !!!!
     *
     * @param string $destination
     * @return File
     * @throws Exception
     */
    public function copy($destination)
    {
//        Loader::callEvent('copy_file_object_instance_before', [$this, &$destination]);

        if (Model\File::exist($this->getFullPath())) {
            $targetPath = $destination . $this->getFullName();
            $bool = Common::copy(
                $this->getFullPath(),
                $targetPath
            );
        } else {
            $bool = Common::mkfile(
                $destination,
                $this->getFullName(),
                $this->getContent()
            );
        }

        if (!$bool) {
//            Loader::callEvent('copy_file_object_instance_error', [$this, $destination]);
            throw new Exception(
                'unable to copy file:'
                . $this->getFullPath()
                . ' -> '
                . $destination
            );
        }

        $data = $this->getData();
        $data['main_path'] = $destination;
        $this->updateFileInfo();

//        Loader::callEvent('copy_file_object_instance_after', [$this]);

        return Loader::getClass('Core\Disc\Model\File', $data)->load();
    }

    /**
     * rename file
     *
     * @param string $name
     * @param null|string $extension
     * @return $this
     * @throws Exception
     */
    public function rename($name, $extension = null)
    {
//        Loader::callEvent('rename_file_object_instance_before', [$this, &$name, &$extension]);

        $bool = true;

        if (Model\File::exist($this->getFullPath())) {
            $bool = Common::move(
                $this->getFullPath(),
                $name . '.' . $extension
            );
        }

        $this->setName($name);
        $this->setExtension($extension);

        if (!$bool) {
//            Loader::callEvent('rename_file_object_instance_error', [$this, $name, $extension]);
            throw new Exception(
                'unable to rename file:'
                . $this->getFullPath()
                . ' -> '
                . $name . '.' . $extension
            );
        }

        $this->updateFileInfo();
        $this->replaceDataArrays();

//        Loader::callEvent('rename_file_object_instance_after', $this);

        return $this;
    }

    /**
     * set file information at real existing file
     *
     * @return File
     */
    protected function updateFileInfo()
    {
        $info = new SplFileInfo($this->getFullPath());
        $this->setAtTime($info->getATime());
        $this->setMtTime($info->getMTime());
        $this->setCtTime($info->getCTime());
        $this->setSize($info->getSize());
        $this->setPermissions($info->getPerms());

        return $this;
    }

    /**
     * return content size in bytes
     *
     * @return integer
     */
    public function getSize()
    {
        $size = mb_strlen($this->getContent(), '8bit');
        $this->setSize($size);
        return $size;
    }

    /**
     * destroy file object (can remove or save file)
     */
    public function __destruct()
    {
        try {
            if ($this->getForceSave() && !$this->getToDelete()) {
                $this->save();
            }

            if ($this->getToDelete()) {
                $this->delete();
            }
        } catch (Exception $e) {
            Loader::exceptions($e, 'file io operation');
        }
    }
}
