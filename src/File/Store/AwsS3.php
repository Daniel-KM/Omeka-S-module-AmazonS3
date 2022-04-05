<?php declare(strict_types=1);
namespace AmazonS3\File\Store;

use Aws\Credentials\Credentials;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Laminas\Log\Logger;
use Omeka\File\Exception\RuntimeException;
use Omeka\File\Store\StoreInterface;

/**
 * Cloud storage adapter for Amazon S3, using AWS SDK.
 */
class AwsS3 implements StoreInterface
{
    const OPTION_AWS_KEY = 'amazons3_access_key_id';
    const OPTION_AWS_SECRET_KEY = 'amazons3_secret_access_key';
    const OPTION_REGION = 'amazons3_region';
    const OPTION_BUCKET = 'amazons3_bucket';
    const OPTION_EXPIRATION = 'amazons3_expiration';

    const STREAM_WRAPPER_NAME = 's3';

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var S3Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $bucket;

    /**
     * @var int
     */
    protected $expiration;

    /**
     * @var string
     */
    protected $lastError;

    /**
     * @param Logger $logger
     * @param array $parameters
     */
    public function __construct(Logger $logger, array $parameters)
    {
        $this->logger = $logger;
        $this->bucket = $parameters['bucket'];
        $this->expiration = $parameters['expiration'];

        $this->client = new S3Client([
            'version' => 'latest',
            'region' => $parameters['region'],
            'credentials' => new Credentials($parameters['key'], $parameters['secretKey']),
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
     * @return S3Client
     */
    public function getClient()
    {
        return $this->client;
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
     * @return int
     */
    protected function getExpiration()
    {
        return $this->expiration;
    }

    /**
     * Get the name of the bucket files should be stored in.
     *
     * @return string Bucket name
     */
    protected function getBucketName()
    {
        return $this->bucket;
    }

    /**
     * Get path compatible with stream wrapper.
     *
     * @param $storagePath
     * @return string
     */
    public function getStreamWrapperObjectStoragePath($storagePath = '')
    {
        return sprintf('s3://%s/%s', $this->getBucketName(), $storagePath);
    }

    /**
     * Check if provided bucket exists.
     *
     * @return bool
     */
    public function canStore()
    {
        $bucket = $this->getBucketName();
        return $this->getClient()->doesBucketExist($bucket);
    }

    /**
     * Determine bucket region.
     *
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
     * Return list of available buckets or false on exception.
     *
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
    public function put($source, $storagePath): void
    {
        $bucket = $this->getBucketName();
        $mime = mime_content_type($source);
        $args = [
            'Bucket' => $bucket,
            'Key' => $storagePath,
            'SourceFile' => $source,
            'ACL' => 'public-read',
            'ContentType' => $mime,
        ];
        if ($this->getExpiration()) {
            $args['ACL'] = 'private';
        }

        try {
            $this->getClient()->putObject($args);
        } catch (S3Exception $e) {
            throw new RuntimeException(
                sprintf('Failed to copy "%s" to "%s" on bucket "%s". %s', $source, $storagePath, $bucket, $e->getMessage()) // @translate
            );
        }

        $this->getLogger()->info(
            sprintf("%s: Stored '%s' as '%s' on bucket '%s'.", self::class, $source, $storagePath, $bucket) // @translate
        );
    }

    /**
     * Move a file between two "storage" locations.
     *
     * @param string $source Original stored path.
     * @param string $dest Destination stored path.
     */
    public function move($source, $dest): void
    {
        $bucket = $this->getBucketName();
        $args = [
            'SourceBucket' => $bucket,
            'SourceKey' => $source,
            'DestinationBucket' => $bucket,
            'DestinationKey' => $dest,
            'ACL' => 'public-read',
        ];
        if ($this->getExpiration()) {
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
                sprintf('Failed to copy "%s" to "%s" on bucket "%s". %s', $source, $dest, $bucket, $e->getMessage()) // @translate
            );
        }

        $this->getLogger()->info(sprintf("%s: Moved '%s' to '%s'.", self::class, $source, $dest)); // @translate
    }

    /**
     * Remove a "stored" file.
     *
     * @param string $storagePath
     */
    public function delete($storagePath): void
    {
        $bucket = $this->getBucketName();

        try {
            if (!$this->getClient()->doesObjectExist($bucket, $storagePath)) {
                $this->getLogger()->warn(
                    sprintf("%s: Tried to delete missing object '%s'.", self::class, $storagePath)); // @translate
            }
            $this->getClient()->deleteObject([
                'Bucket' => $bucket,
                'Key' => $storagePath,
            ]);
        } catch (S3Exception $e) {
            $this->setLastError($e->getMessage());
            throw new RuntimeException('Unable to delete file. ' . $e->getMessage());
        }

        $this->getLogger()->info(sprintf("%s: Removed object '%s'.", self::class, $storagePath)); // @translate
    }

    /**
     * Remove a "stored" directory.
     *
     * This is not part of the Omeka storage api, but used in modules
     * ImageServer and ArchiveRepertory.
     *
     * @param string $storagePath
     */
    public function deleteDir($storagePath): void
    {
        $bucket = $this->getBucketName();

        $storagePathClean = trim($storagePath, '/');
        $regex = '~^' . preg_quote($storagePathClean . '/', '~') . '~';
        $storagePath = $storagePathClean;

        try {
            if (!$this->getClient()->doesObjectExist($bucket, $storagePath)) {
                $this->getLogger()->warn(
                    sprintf("%s: Tried to delete missing object '%s'.", self::class, $storagePath)); // @translate
            }
            $this->getClient()->deleteMatchingObjects($bucket, $storagePath, $regex);
        } catch (S3Exception $e) {
            $this->setLastError($e->getMessage());
            throw new RuntimeException('Unable to delete file. ' . $e->getMessage());
        }

        $this->getLogger()->info(sprintf("%s: Removed object '%s'.", self::class, $storagePath)); // @translate
    }

    /**
     * Check a "stored" file.
     *
     * This is not part of the Omeka storage api, but used in modules
     * ImageServer and ArchiveRepertory.
     *
     * @param string $storagePath
     */
    public function hasFile($storagePath)
    {
        $bucket = $this->getBucketName();

        try {
            return $this->getClient()->doesObjectExist($bucket, $storagePath);
        } catch (S3Exception $e) {
            $this->setLastError($e->getMessage());
            throw new RuntimeException('Unable to check file. ' . $e->getMessage());
        }
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
            $uri = $endpoint . '/' . $bucket . '/' . $path;
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
