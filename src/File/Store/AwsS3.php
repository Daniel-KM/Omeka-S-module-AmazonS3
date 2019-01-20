<?php
namespace AmazonS3\File\Store;

use AmazonS3\Traits\ServiceLocatorAwareTrait;
use Aws\Credentials\Credentials;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Omeka\File\Store\StoreInterface;
use Omeka\File\Exception\ConfigException;
use Omeka\File\Exception\RuntimeException;
use Zend\Log\Logger;

/**
 * Cloud storage adapter for Amazon S3, using AWS SDK.
 */
class AwsS3 implements StoreInterface
{
    use ServiceLocatorAwareTrait;

    const OPTION_AWS_KEY = 'amazons3_access_key_id';
    const OPTION_AWS_SECRET_KEY = 'amazons3_secret_access_key';
    const OPTION_REGION = 'amazons3_region';
    const OPTION_BUCKET = 'amazons3_bucket';
    const OPTION_EXPIRATION = 'amazons3_expiration';

    const STREAM_WRAPPER_NAME = 's3';

    /** @var  Logger */
    private $logger;
    /** @var  S3Client */
    private $client;
    /** @var  string */
    private $lastError;

    public function setUp()
    {
        $this->logger = $this->getServiceLocator()->get('Omeka\Logger');

        //get config
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $awsRegion = $settings->get(self::OPTION_REGION, 'us-east-2');
        $awsKey = $settings->get(self::OPTION_AWS_KEY);
        $awsSecretKey = $settings->get(self::OPTION_AWS_SECRET_KEY);

        if (empty($awsKey) || empty($awsSecretKey)) {
            throw new ConfigException('You must specify your AWS access key and secret key to use the AWS S3 storage adapter.');
        }

        $bucket = $this->getBucketName();
        if (empty($bucket)) {
            throw new ConfigException('You must specify an S3 bucket name to use the AWS S3 storage adapter.');
        }

        //init client
        $this->client = new S3Client([
            'version' => 'latest',
            'region' => $awsRegion,
            'credentials' => new Credentials($awsKey, $awsSecretKey),
        ]);

        $this->client->registerStreamWrapper();
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param $error
     * @return $this
     */
    public function setLastError($error)
    {
        $this->lastError = $error;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * @return S3Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Normalizes and returns the expiration time.
     *
     * Converts to integer and returns zero for all non-positive numbers.
     *
     * @return int
     */
    private function getExpiration()
    {
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $expiration = (int) $settings->get(self::OPTION_EXPIRATION);
        return $expiration > 0 ? $expiration : 0;
    }

    /**
     * Get the name of the bucket files should be stored in.
     * @return string Bucket name
     */
    private function getBucketName()
    {
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        return $settings->get(self::OPTION_BUCKET);
    }

    /**
     * Get path compatible with stream wrapper
     * @param $storagePath
     * @return string
     */
    public function getStreamWrapperObjectStoragePath($storagePath = '')
    {
        return sprintf('s3://%s/%s', $this->getBucketName(), $storagePath);
    }

    /**
     * Check if provided bucket exists
     * @return bool
     */
    public function canStore()
    {
        $bucket = $this->getBucketName();
        return $this->getClient()->doesBucketExist($bucket);
    }

    /**
     * Determine Bucket Region
     * @return bool|mixed
     */
    public function determineBucketRegion()
    {
        $result = false;
        try {
            $result = $this->getClient()->determineBucketRegion($this->getBucketName());
        } catch (S3Exception $e) {
            $this->setLastError($e->getMessage());
        }
        return $result;
    }

    /**
     * Return list of available buckets or false on exception
     * @return array|bool
     */
    public function getBuckets()
    {
        $result = [];
        try {
            /** @var \Aws\Result $response */
            $response = $this->getClient()->listBuckets();
            foreach ($response->get('Buckets') as $bucket) {
                $result[] = $bucket['Name'];
            }
        } catch (S3Exception $e) {
            $this->setLastError($e->getMessage());
            return false;
        }
        return $result;
    }

    /**
     * Move a local file to S3 storage.
     *
     * @param string $source Local path to the file to store
     * @param string $storagePath Storage path to store at
     */
    public function put($source, $storagePath)
    {
        $bucket = $this->getBucketName();
        $args = [
            'Bucket' => $bucket,
            'Key' => $storagePath,
            'SourceFile' => $source,
            'ACL' => 'public-read',
        ];
        if (($expiration = $this->getExpiration())) {
            $args['ACL'] = 'private';
        }

        try {
            $this->getClient()->putObject($args);
        } catch (S3Exception $e) {
            throw new RuntimeException(
                sprintf('Failed to copy "%s" to "%s" on bucket "%s". %s', $source, $storagePath, $bucket, $e->getMessage())
            );
        }

        $this->getLogger()->log(Logger::INFO, sprintf("%s: Stored '%s' as '%s' on bucket '%s'.", self::class, $source, $storagePath, $bucket));
    }

    /**
     * Move a file between two "storage" locations.
     *
     * @param string $source Original stored path.
     * @param string $dest Destination stored path.
     */
    public function move($source, $dest)
    {
        $bucket = $this->getBucketName();
        $args = [
            'SourceBucket' => $bucket,
            'SourceKey' => $source,
            'DestinationBucket' => $bucket,
            'DestinationKey' => $dest,
            'ACL' => 'public-read',
        ];
        if (($expiration = $this->getExpiration())) {
            $args['ACL'] = 'private';
        }

        try {
            $this->getClient()->copyObject($args);
            $this->getClient()->deleteObject([
                'Bucket' => $bucket,
                'Key' => $source,
            ]);
        } catch (S3Exception $e) {
            throw new RuntimeException(
                sprintf('Failed to copy "%s" to "%s" on bucket "%s". %s', $source, $dest, $bucket, $e->getMessage())
            );
        }

        $this->getLogger()->log(Logger::INFO, sprintf("%s: Moved '%s' to '%s'.", self::class, $source, $dest));
    }

    /**
     * Remove a "stored" file.
     * @param string $storagePath
     */
    public function delete($storagePath)
    {
        $bucket = $this->getBucketName();

        try {
            if (!$this->getClient()->doesObjectExist($bucket, $storagePath)) {
                $this->getLogger()->log(Logger::WARN, sprintf("%s: Tried to delete missing object '%s'.", self::class, $storagePath));
            }
            $this->getClient()->deleteObject([
                'Bucket' => $bucket,
                'Key' => $storagePath,
            ]);
        } catch (S3Exception $e) {
            $this->setLastError($e->getMessage());
            throw new RuntimeException('Unable to delete file. '.$e->getMessage());
        }

        $this->getLogger()->log(Logger::INFO, sprintf("%s: Removed object '%s'.", self::class, $storagePath));
    }

    /**
     * Get a URI for a "stored" file.
     *
     * @see http://docs.amazonwebservices.com/AmazonS3/latest/dev/index.html?RESTAuthentication.html#RESTAuthenticationQueryStringAuth
     * @param string $path
     * @return string URI
     */
    public function getUri($path)
    {
        $bucket = urlencode($this->getBucketName());
        $expiration = $this->getExpiration();

        if (!$expiration) {
            $endpoint = $this->getClient()->getEndpoint();
            $uri = $endpoint.'/'.$bucket.'/'.$path;
        } else {
            $cmd = $this->getClient()->getCommand('GetObject', [
                'Bucket' => $bucket,
                'Key' => $path,
            ]);
            $request = $this->getClient()->createPresignedRequest($cmd, sprintf('+%d minutes', $expiration));
            $uri = (string) $request->getUri();
        }

        return $uri;
    }
}
