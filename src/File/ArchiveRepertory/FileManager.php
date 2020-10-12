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

    protected function createArchiveFolders($archiveFolder, $pathFolder = '')
    {
        // No need to create directories in Amazon: they don't exist (but it is
        // possible to move and to remove them as prefix of files).
        return true;
    }

    protected function createFolder($path)
    {
        // No need to create directory in Amazon.
        return true;
    }

    protected function moveFile($source, $destination, $path = '')
    {
        $fileWriter = $this->getFileWriter();
        $realSource = $this->concatWithSeparator($path, $source);
        $realDestination = $this->concatWithSeparator($path, $destination);
        if ($fileWriter->fileExists($realDestination)) {
            return true;
        }

        if (!$fileWriter->fileExists($realSource)) {
            $msg = sprintf(
                $this->translate('Error during move of a file from "%s" to "%s" (local dir: "%s"): source does not exist.'),
                $source,
                $destination,
                $path
            );
            $this->addError($msg);
            return false;
        }

        try {
            // No need to create directory in Amazon.
            $result = $fileWriter->rename($realSource, $realDestination);
        } catch (\Exception $e) {
            $msg = sprintf(
                $this->translate('Error during move of a file from "%s" to "%s" (local dir: "%s").'),
                $source,
                $destination,
                $path
            );
            $this->addError($msg);
            return false;
        }

        return $result;
    }
}
