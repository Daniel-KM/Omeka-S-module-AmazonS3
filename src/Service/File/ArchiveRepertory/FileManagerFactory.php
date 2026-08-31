<?php declare(strict_types=1);
namespace AmazonS3\Service\File\ArchiveRepertory;

use AmazonS3\File\ArchiveRepertory\FileManager;
use AmazonS3\File\Store\AwsS3;
use ArchiveRepertory\Service\FileManagerFactory as ArchiveRepertoryFileManagerFactory;
use Psr\Container\ContainerInterface;
use Omeka\Service\Exception\ConfigException;

class FileManagerFactory extends ArchiveRepertoryFileManagerFactory
{
    public function __invoke(ContainerInterface $services, $requestedName, ?array $options = null)
    {
        $config = $services->get('Config');

        if (!isset($config['thumbnails']['types'])) {
            throw new ConfigException('Missing thumbnails configuration'); // @translate
        }

        if (!isset($config['archiverepertory']['ingesters'])) {
            throw new ConfigException('Missing Archive Repertory ingesters configuration'); // @translate
        }

        /** @var AwsS3 $store */
        $store = $services->get(AwsS3::class);
        $basePath = $store->getStreamWrapperObjectStoragePath();
        $thumbnailTypes = $config['thumbnails']['types'];
        $ingesters = $config['archiverepertory']['ingesters'];

        return new FileManager(
            $services->get('ArchiveRepertory\FileWriter'),
            $services->get('ControllerPluginManager')->get('messenger'),
            $services->get('Omeka\Settings'),
            $services->get('MvcTranslator'),
            $services->get('Omeka\Logger'),
            $basePath,
            $ingesters,
            $thumbnailTypes
        );
    }
}
