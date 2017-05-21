<?php

namespace Filesystem\Model;

interface ModelInterface
{
    public function load();
    public function save();
    public function delete();
    public function move($destination);
    public function copy($destination);
    public function rename($newName);
}
