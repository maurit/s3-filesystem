<?php

namespace Maurit\S3filesystem;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Finder\Finder;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

/**
 * Class S3Storage
 * @package Maurit\S3Storage
 */
class S3Storage
{
    /**
     * @var FilesystemAdapter
     */
    private FilesystemAdapter $cache;

    /**
     * @var mixed|string
     */
    private string $cacheDirectory = 'cache';

    /**
     * @var string
     */
    private string $cacheNamespace = 's3storage';

    /**
     * @var float|int|mixed
     */
    private int $cacheLifetime = 60 * 60;

    /**
     * @var S3Client
     */
    private S3Client $s3Client;

    /**
     * @var mixed|string
     */
    private string $version = '2006-03-01';

    /**
     * @var mixed|string|null
     */
    private ?string $bucket;

    /**
     * S3Storage constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->cacheLifetime  = $config['cacheLifetime'] ?? $this->cacheLifetime;
        $this->cacheDirectory = $config['cacheDirectory'] ?? $this->cacheDirectory;
        $this->version        = $config['version'] ?? $this->version;
        $this->bucket         = $config['bucket'] ?? null;

        $this->cache = new FilesystemAdapter($this->cacheNamespace, $this->cacheLifetime, $this->cacheDirectory);

        $this->s3Client = new S3Client([
            'region'                  => $config['region'],
            'version'                 => $this->version,
            'endpoint'                => $config['endpoint'],
            'credentials'             => [
                'key'    => $config['key'],
                'secret' => $config['secret']
            ],
            'use_path_style_endpoint' => true,
        ]);
    }

    /**
     * @param string $bucket
     * @return $this
     */
    public function setBucket(string $bucket): self
    {
        $this->bucket = $bucket;

        return $this;
    }

    /**
     * @param string $key
     * @return array
     * @throws FileNotExistsException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getFile(string $key): array
    {
        $cache = $this->cache->getItem(MD5($key));

        if (!($data = $cache->get())) {
            $file = $this->getS3File($key);
            $cache->set($file);
            $this->cache->save($cache);
        } else {
            $cache->expiresAfter($this->cacheLifetime);
        }

        return $cache->get();
    }

    /**
     * @param string $key
     * @return Response
     * @throws FileNotExistsException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function get(string $key): Response
    {
        $file = $this->getFile($key);

        return new Response($file['body'], Response::HTTP_OK, [
            'Content-type'  => $file['contentType'],
            'Cache-Control' => 'private'
        ]);
    }

    /**
     * @param string $key
     * @throws FileNotExistsException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function render(string $key): void
    {
        $this->get($key)->send();
    }

    /**
     * @param string $key
     * @param string|null $fileName
     * @throws FileNotExistsException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getDownload(string $key, ?string $fileName = null): void
    {
        $file = $this->get($key);
        $name = $fileName ?? basename($key);

        $file->headers->set('Content-Disposition', "attachment; filename=\"{$name}\";");
        $file->send();
    }

    /**
     * @param string $key
     * @return array
     * @throws FileNotExistsException
     */
    protected function getS3File(string $key): array
    {
        try {
            $object = $this->s3Client->getObject([
                'Bucket' => $this->bucket,
                'Key'    => $key
            ]);

            return [
                'contentType' => $object['ContentType'],
                'body'        => $object['Body']->getContents(),
            ];
        } catch (S3Exception $e) {
            switch ($e->getAwsErrorCode()) {
                case 'NoSuchKey':
                    throw new FileNotExistsException("{$e->getAwsErrorMessage()} Key: {$key}", 404);
                    break;
                default:
                    throw $e;
                    break;
            }
        }
    }

    /**
     * @param string $key
     * @param string $value
     * @param bool $public
     * @param bool $fromFile
     * @return $this
     */
    public function put(string $key, string $value, bool $public = false, bool $fromFile = false): self
    {
        $data = [
            'Bucket' => $this->bucket,
            'Key'    => $key,
        ];

        if ($fromFile) {
            $data['SourceFile'] = $value;
        } else {
            $data['Body'] = $value;
        }

        $data['Acl'] = $public ? 'public-read' : 'authenticated-read';

        $this->s3Client->putObject($data);

        return $this;
    }

    /**
     * @param array $values
     * @param bool $public
     * @param bool $fromFile
     * @return $this
     */
    public function putMany(array $values, bool $public = false, bool $fromFile = false): self
    {
        foreach ($values as $key => $value) {
            $this->put($key, $value, $public, $fromFile);
        }

        return $this;
    }

    /**
     * @param string $path
     * @param string $destination
     */
    public function putFromFolder(string $path, string $destination)
    {
        $finder = new Finder();
        $finder->files()->in($path);

        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                $this->put("{$destination}/{$file->getFilename()}", $file->getRealPath(), false, true);
            }
        }
    }

    /**
     * @param string $key
     * @return bool
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function forget(string $key): bool
    {
        return $this->cache->delete(MD5($key));
    }

    /**
     * @param string $key
     * @return bool
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function delete(string $key): bool
    {
        $this->forget($key);

        $this->s3Client->deleteObject([
            'Bucket' => $this->bucket,
            'Key'    => $key
        ]);

        return true;
    }
}