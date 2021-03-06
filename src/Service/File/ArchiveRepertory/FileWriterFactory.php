<?php declare(strict_types=1);

namespace AmazonS3\Service\File\ArchiveRepertory;

use AmazonS3\File\ArchiveRepertory\FileWriter;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class FileWriterFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        return new FileWriter;
    }
}
