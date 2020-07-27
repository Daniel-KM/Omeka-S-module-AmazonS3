<?php

namespace AmazonS3\Service\File\ArchiveRepertory;

use AmazonS3\File\ArchiveRepertory\FileWriter;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class FileWriterFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        return new FileWriter;
    }
}
