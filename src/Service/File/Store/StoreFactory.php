<?php declare(strict_types=1);
namespace AmazonS3\Service\File\Store;

use AmazonS3\File\Store\AwsS3;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Omeka\File\Exception\ConfigException;

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
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $settings = $services->get('Omeka\Settings');
        $parameters = [
            'key' => $settings->get(AwsS3::OPTION_AWS_KEY),
            'secretKey' => $settings->get(AwsS3::OPTION_AWS_SECRET_KEY),
            'region' => $settings->get(AwsS3::OPTION_REGION, 'us-east-2'),
            'bucket' => $settings->get(AwsS3::OPTION_BUCKET),
            'expiration' => max(0, (int) $settings->get(AwsS3::OPTION_EXPIRATION, 0)),
        ];

        if (empty($parameters['key']) || empty($parameters['secretKey'])) {
            throw new ConfigException('You must specify your AWS access key and secret key to use the AWS S3 storage adapter.'); // @translate
        }

        if (empty($parameters['bucket'])) {
            throw new ConfigException('You must specify an S3 bucket name to use the AWS S3 storage adapter.'); // @translate
        }

        return new AwsS3(
            $services->get('Omeka\Logger'),
            $parameters
        );
    }
}
