<?php
namespace AmazonS3\Service\File\ArchiveRepertory;

use ArchiveRepertory\Service\FileManagerFactory as ArchiveRepertoryFileManagerFactory;
use AmazonS3\File\ArchiveRepertory\FileManager;
use Interop\Container\ContainerInterface;
use AmazonS3\File\Store\AwsS3;
use Omeka\Service\Exception\ConfigException;

class FileManagerFactory extends ArchiveRepertoryFileManagerFactory
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $config = $services->get('Config');

        if (!isset($config['thumbnails']['types'])) {
            throw new ConfigException('Missing thumbnails configuration');
        }

        if (!isset($config['archiverepertory']['ingesters'])) {
            throw new ConfigException('Missing Archive Repertory ingesters configuration');
        }

        $thumbnailTypes = $config['thumbnails']['types'];

        $ingesters = $config['archiverepertory']['ingesters'];

        $store = $services->get(AwsS3::class);
        $basePath = $store->getStreamWrapperObjectStoragePath();

        return new FileManager(
            $thumbnailTypes,
            $basePath,
            $ingesters,
            $services
        );
    }
}
