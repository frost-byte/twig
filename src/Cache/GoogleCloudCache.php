<?php

namespace Frostbyte\Twig\Cache;

use Twig\Cache\CacheInterface;
use Google\Cloud\Storage\{
    Bucket,
    StorageClient
};

class GoogleCloudCache implements CacheInterface
{
    /**
     * The path to the Service Account key file
     * (not required if app deployed to GCE or GAE)
     * @var string
     */
    private $keyFilePath;

    /**
     * The Project ID for the Service Account
     * (not required if app deployed to GCE or GAE)
     * @var string
     */
    private $projectId;

    /**
     * The directory within the Bucket where files will be cached.
     *
     * @var string
     */
    private $bucketDirectory;

    /**
     * The Cloud Bucket
     *
     * @var Bucket
     */
    private $bucket;

    /**
     * Cloud Storage Client
     *
     * @var StorageClient
     */
    private $storage;

    public const FORCE_BYTECODE_INVALIDATION = 1;
    public const PROTO = "gs://";

    /**
     * @param string $bucketName        The cloud storage bucket name
     * @param string $bucketDirectory   The root cache directory within the bucket
     * @param string $keyFilePath       The path to the Google Cloud Service Account key file
     * @param string $projectId         The projectId for the Google Cloud Service Account
     * @param int    $options           A set of options for Twig
     */
    public function __construct(
        $bucketName,
        $bucketDirectory,
        $keyFilePath = '',
        $projectId = '',
        $options = 0
    ) {
        $this->keyFilePath = $keyFilePath;
        $this->projectId = $projectId;

        if (!empty($this->keyFilePath)) {
            $this->storage = new StorageClient([
                'keyFilePath' => $this->keyFilePath,
                'projectId' => $this->projectId
            ]);
        } else {
            $this->storage = new StorageClient();
        }

        $this->bucket = $this->storage->bucket($bucketName);
        $this->storage->registerStreamWrapper();
        $this->bucketDirectory = rtrim($bucketDirectory, '\/') . '/';
        $this->options = $options;
    }

    /**
    * {@inheritdoc}
    */
    public function generateKey(string $name, string $className): string
    {
        $hash = hash('sha256', $className);

        return $this->bucketDirectory . $hash[0] . $hash[1] . '/' . $hash . '.php';
    }

    /**
    * {@inheritdoc}
    */
    public function write(string $key, string $content): void
    {
        $gsPath = $this->generateUrl($key);
        $dir = \dirname($gsPath);

        if (!is_dir($dir)) {
            if (false === @mkdir($dir, 0777, true)) {
                clearstatcache(true, $dir);
                if (!is_dir($dir)) {
                    throw new \RuntimeException(sprintf('Unable to create the cache directory (%s).', $dir));
                }
            }
        } elseif (!is_writable($dir)) {
            throw new \RuntimeException(sprintf('Unable to write in the cache directory (%s).', $dir));
        }

        // Google App Engine Standard uses the /tmp directory
        // which is a volatile temporary storage
        // https://cloud.google.com/appengine/docs/standard/php7/runtime
        $tmpDir = sys_get_temp_dir();
        $tmpFile = tempnam($tmpDir, basename($key));

        // Upload the File to the Cloud Bucket
        if (
            false !== @file_put_contents($tmpFile, $content) &&
            null !== $this->bucket->upload(
                fopen($tmpFile, 'r'),
                ['name' => $key]
            )
        ) {
            unlink($tmpFile);

            if (self::FORCE_BYTECODE_INVALIDATION == ($this->options & self::FORCE_BYTECODE_INVALIDATION)) {
                // Compile cached file into bytecode cache
                if (
                    function_exists('opcache_invalidate') &&
                    filter_var(
                        ini_get('opcache.enable'),
                        FILTER_VALIDATE_BOOLEAN
                    )
                ) {
                    @opcache_invalidate($gsPath, true);
                } elseif (function_exists('apc_compile_file')) {
                    apc_compile_file($gsPath);
                }
            }

            return;
        }

        throw new \RuntimeException(sprintf('Failed to write cache file "%s".', $gsPath));
    }

    /**
     * {@inheritdoc}
     */
    public function load(string $key): void
    {
        $gsPath = $this->generateUrl($key);

        if (file_exists($gsPath)) {
            @include_once $gsPath;
        }
    }

    /**
    * {@inheritdoc}
    */
    public function getTimestamp(string $key): int
    {
        $gsPath = $this->generateUrl($key);

        if (!file_exists($gsPath)) {
            return 0;
        }

        return (int) @filemtime($gsPath);
    }

    /**
     * Convert the hashed file name into a url, so that the file can be saved
     * to the Cloud Bucket
     *
     * @param string $key The file name to convert
     *
     * @return string The Google Storage file name
     * example: "gs://my-project-id.appspot.com/$bucketName/$key"
     */
    public function generateUrl($key): string
    {
        $bucketName = $this->bucket->name();
        return "gs://$bucketName/$key";
    }
}
