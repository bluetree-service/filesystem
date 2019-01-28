<?php

namespace BlueFilesystem\StaticObjects;

interface FsInterface
{
    /**
     * restricted characters for file and directory names
     *
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

    public const CREATE_PATH_AFTER = 'create_directory_after';
    public const CREATE_PATH_BEFORE = 'create_directory_before';
    public const CREATE_PATH_EXCEPTION = 'create_directory_exception';

    public const CREATE_FILE_AFTER = 'create_file_after';
    public const CREATE_FILE_BEFORE = 'create_file_before';
    public const CREATE_FILE_EXCEPTION = 'create_file_exception';

    public const RENAME_FILE_OR_DIR_EXCEPTION = 'rename_file_or_directory_exception';
    public const RENAME_FILE_OR_DIR_BEFORE = 'rename_file_or_directory_before';
    public const RENAME_FILE_OR_DIR_AFTER = 'rename_file_or_directory_after';
}
