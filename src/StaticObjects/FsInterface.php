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

    public const COPY_PATH_CONTENT_EXCEPTION = 'copy_path_content_exception';
    public const COPY_PATH_CONTENT_AFTER = 'copy_path_content_after';
    public const COPY_PATH_CONTENT_BEFORE = 'copy_path_content_before';
    public const COPY_PATHS_BEFORE = 'copy_paths_before';
    public const COPY_CREATE_PATH_EXCEPTION = 'copy_create_path_exception';
}
