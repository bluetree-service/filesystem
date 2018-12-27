<?php

namespace BlueFilesystem\StaticObjects;

interface FsInterface
{
    /**
     * restricted characters for file and directory names
     * @var string
     */
    public const RESTRICTED_SYMBOLS = '#[:?*<>"|\\\]#';

    public const DELETE_PATH_CONTENT_BEFORE = 'delete_path_content_before';
    public const DELETE_PATHS_BEFORE = 'delete_paths_before';
    public const DELETE_PATH_CONTENT_AFTER = 'delete_path_content_after';
    public const DELETE_PATH_CONTENT_EXCEPTION = 'delete_path_content_exception';
}
