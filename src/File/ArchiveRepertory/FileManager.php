<?php declare(strict_types=1);
namespace AmazonS3\File\ArchiveRepertory;

use ArchiveRepertory\File\FileManager as ArchiveRepertoryFileManager;

class FileManager extends ArchiveRepertoryFileManager
{
    /**
     * Removes empty folders in the archive repertory.
     *
     * There is no folder on Amazon S3, so no empty folder to remove.
     * The files are deleted separetly.
     *
     * @param string $archiveFolder Name of folder to delete, without files dir.
     */
    public function removeArchiveFolders($archiveFolder): void
    {
        // Nothing to do.
    }

    protected function createArchiveFolders($archiveFolder, $pathFolder = ''): bool
    {
        // No need to create directories in Amazon: they don't exist (but it is
        // possible to move and to remove them as prefix of files).
        return true;
    }

    protected function createFolder($path): bool
    {
        // No need to create directory in Amazon.
        return true;
    }
}
