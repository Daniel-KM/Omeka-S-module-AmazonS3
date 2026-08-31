<?php declare(strict_types=1);

namespace AmazonS3Test;

use AmazonS3\File\Store\AwsS3;
use Aws\S3\S3Client;

/**
 * Give access to the internals of the store, without any call to Amazon.
 */
class MockAwsS3 extends AwsS3
{
    /**
     * Replace the client by one built with a mock handler.
     */
    public function setClient(S3Client $client): self
    {
        $this->client = $client;
        return $this;
    }

    public function mediaTypeBypassProtectedMethod($storagePath, $source): string
    {
        return $this->mediaType($storagePath, $source);
    }
}
