<?php declare(strict_types=1);

namespace AmazonS3Test;

use Aws\Command;
use Aws\CommandInterface;
use Aws\Middleware;
use Aws\MockHandler;
use Aws\Result;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Laminas\Log\Logger;
use Laminas\Log\Writer\Noop;
use Omeka\File\Exception\ExceptionInterface;
use Omeka\Test\TestCase;

class AwsS3Test extends TestCase
{
    const BUCKET = 'bucket-test';

    /**
     * @var MockAwsS3
     */
    protected $store;

    /**
     * Commands sent to Amazon, when a mock handler is set.
     *
     * @var CommandInterface[]
     */
    protected $commands = [];

    public function setUp(): void
    {
        $logger = new Logger();
        $logger->addWriter(new Noop());
        $this->store = new MockAwsS3($logger, [
            'bucket' => self::BUCKET,
            'expiration' => 0,
            'region' => 'us-east-2',
            'key' => 'key-test',
            'secretKey' => 'secret-test',
        ], [
            'image/jpeg' => ['jpg', 'jpeg'],
            'text/csv' => ['csv'],
        ]);
    }

    /**
     * Build a client that answers without any network call and keeps the
     * commands, so the arguments sent to Amazon can be checked.
     */
    protected function mockClient($queue = 2): S3Client
    {
        if (is_int($queue)) {
            $queue = array_fill(0, $queue, new Result([]));
        }
        $this->commands = [];
        $client = new S3Client([
            'version' => 'latest',
            'region' => 'us-east-2',
            'credentials' => ['key' => 'key-test', 'secret' => 'secret-test'],
            'handler' => new MockHandler($queue),
        ]);
        $commands = &$this->commands;
        $client->getHandlerList()->appendInit(
            Middleware::tap(function (CommandInterface $command) use (&$commands): void {
                $commands[] = $command;
            }),
            'test-capture'
        );
        return $client;
    }

    public function testMediaTypeFromTheExtensionOfTheMedia(): void
    {
        $this->assertSame(
            'image/jpeg',
            $this->store->mediaTypeBypassProtectedMethod('original/1706/photo.jpg', '')
        );
        // The extension wins over the detection of the content: an empty file
        // is "application/x-empty" and a csv without separator "text/plain".
        $source = tempnam(sys_get_temp_dir(), 'omk_s3_');
        $this->assertSame(
            'text/csv',
            $this->store->mediaTypeBypassProtectedMethod('original/list.csv', $source)
        );
        unlink($source);
    }

    public function testMediaTypeFallbacks(): void
    {
        $source = tempnam(sys_get_temp_dir(), 'omk_s3_');
        file_put_contents($source, "%PDF-1.4\n");
        // Unknown extension: the content is used.
        $this->assertSame(
            'application/pdf',
            $this->store->mediaTypeBypassProtectedMethod('original/file.unknown', $source)
        );
        unlink($source);
        // Neither extension nor readable file.
        $this->assertSame(
            'application/octet-stream',
            $this->store->mediaTypeBypassProtectedMethod('original/file.unknown', '/nonexistent')
        );
    }

    public function testUriEncodesEachSegment(): void
    {
        $uri = $this->store->getUri('original/dossier accentué/été 1.jpg');
        $this->assertStringContainsString('/original/dossier%20accentu%C3%A9/%C3%A9t%C3%A9%201.jpg', $uri);
        // The separators are kept, so the path is not a single segment.
        $this->assertStringNotContainsString('%2F', $uri);
    }

    /**
     * The arguments of CopyObject are validated by the sdk before any call, so
     * this test fails when they do not match the S3 api.
     */
    public function testMoveSendsValidArguments(): void
    {
        $this->store->setClient($this->mockClient());
        $this->store->move('original/1706/alpha beta.jpg', 'original/1707/alpha.jpg');

        $this->assertCount(2, $this->commands);
        $this->assertSame('CopyObject', $this->commands[0]->getName());
        $this->assertSame(self::BUCKET, $this->commands[0]['Bucket']);
        $this->assertSame('original/1707/alpha.jpg', $this->commands[0]['Key']);
        // The key of the source is encoded, but the separators are kept.
        $this->assertSame(
            self::BUCKET . '/original/1706/alpha%20beta.jpg',
            $this->commands[0]['CopySource']
        );
        $this->assertSame('DeleteObject', $this->commands[1]->getName());
        $this->assertSame('original/1706/alpha beta.jpg', $this->commands[1]['Key']);
    }

    public function testPutSendsTheMediaTypeOfTheMedia(): void
    {
        $source = tempnam(sys_get_temp_dir(), 'omk_s3_');
        file_put_contents($source, 'a,b');
        $this->store->setClient($this->mockClient(1));
        $this->store->put($source, 'original/1706/list.csv');

        $this->assertCount(1, $this->commands);
        $this->assertSame('PutObject', $this->commands[0]->getName());
        $this->assertSame('text/csv', $this->commands[0]['ContentType']);
        unlink($source);
    }

    /**
     * A user dedicated to Omeka has rights on its own bucket only, so the
     * config must be checkable when listing all the buckets is denied.
     */
    public function testBucketIsCheckedWithoutListingAllBuckets(): void
    {
        $this->store->setClient($this->mockClient([
            // HeadBucket, sent by canStore().
            new Result([]),
            // ListBuckets, denied without the permission ListAllMyBuckets.
            new S3Exception('Access Denied', new Command('ListBuckets')),
        ]));

        $this->assertTrue($this->store->canStore());
        // Listing the buckets fails, but without exception and without hiding
        // the fact that the bucket itself is usable.
        $this->assertFalse($this->store->getBuckets());
        $this->assertNotEmpty($this->store->getLastError());
    }

    /**
     * An exception that is not an S3Exception must be managed too.
     */
    public function testAnyExceptionIsConvertedForOmeka(): void
    {
        try {
            $this->store->put('/nonexistent/file.jpg', 'original/x.jpg');
            $this->fail('An exception should be thrown when the source is missing.');
        } catch (\Throwable $e) {
            $this->assertInstanceOf(ExceptionInterface::class, $e);
            $this->assertNotNull($e->getPrevious());
            $this->assertStringContainsString('original/x.jpg', $e->getMessage());
            $this->assertNotEmpty($this->store->getLastError());
        }
    }
}
