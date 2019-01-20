<?php
namespace AmazonS3\Service\File\Store;

use AmazonS3\File\Store\AwsS3;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * Service factory for the Local file store.
 */
class StoreFactory implements FactoryInterface
{
    /**
     * Create and return the Local file store
     *
     * @return AwsS3
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $awsS3 = new AwsS3();
        $awsS3->setServiceLocator($serviceLocator);
        $awsS3->setUp();
        return $awsS3;
    }
}
