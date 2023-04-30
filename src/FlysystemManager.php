<?php

namespace Danhunsaker\Laravel\Flysystem;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class FlysystemManager extends FilesystemManager
{
    /**
     * {@inheritdoc}
     */
    public function __construct($app)
    {
        parent::__construct($app);

        if (class_exists('\League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter')) {
            $this->extend('async-s3', function ($app, $config) {
                $client = new \AsyncAws\S3\S3Client(Arr::except($config, ['driver', 'bucket']));
                $adapter = new \League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter($client, Arr::get($config, 'bucket'));

                return new FilesystemAdapter($this->createFlysystem($adapter, $config), $adapter, $config);
            });
        }

        if (class_exists('\League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter')) {
            $this->extend('azure', function ($app, $config) {
                $endpoint = sprintf('DefaultEndpointsProtocol=https;AccountName=%s;AccountKey=%s', $config['accountName'], $config['apiKey']);
                $client = \MicrosoftAzure\Storage\Blob\BlobRestProxy::createBlobService($endpoint);
                $adapter = new \League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter($client, $config['container']);

                return new FilesystemAdapter($this->createFlysystem($adapter, $config), $adapter, $config);
            });
        }

        if (class_exists('\League\Flysystem\GoogleCloudStorage\GoogleCloudStorageAdapter')) {
            $this->extend('google', function ($app, $config) {
                $gcsClient = new \Google\Cloud\Storage\StorageClient(Arr::except($config, ['driver', 'bucket', 'prefix']));
                $bucket = $gcsClient->bucket(Arr::get($config, 'bucket'));
                $adapter = new \League\Flysystem\GoogleCloudStorage\GoogleCloudStorageAdapter($bucket, Arr::get($config, 'prefix'));

                return new FilesystemAdapter($this->createFlysystem($adapter, $config), $adapter, $config);
            });
        }

        if (class_exists('\League\Flysystem\InMemory\InMemoryFilesystemAdapter')) {
            $this->extend('memory', function ($app, $config) {
                $adapter = new \League\Flysystem\InMemory\InMemoryFilesystemAdapter();

                return new FilesystemAdapter($this->createFlysystem($adapter, $config), $adapter, $config);
            });
        }

        if (class_exists('\League\Flysystem\WebDAV\WebDAVAdapter')) {
            $this->extend('webdav', function ($app, $config) {
                if ( ! empty($config['authType'])) {
                    if (is_string($config['authType']) && strtolower($config['authType']) == 'ntlm') {
                        $config['authType'] = \Sabre\DAV\Client::AUTH_NTLM;
                    } elseif (is_string($config['authType']) && strtolower($config['authType']) == 'digest') {
                        $config['authType'] = \Sabre\DAV\Client::AUTH_DIGEST;
                    } else {
                        $config['authType'] = \Sabre\DAV\Client::AUTH_BASIC;
                    }
                }

                if ( ! empty($config['encoding'])) {
                    if (is_string($config['encoding']) && strtolower($config['encoding']) == 'all') {
                        $config['encoding'] = \Sabre\DAV\Client::ENCODING_ALL;
                    } else {
                        if (is_string($config['encoding'])) {
                            $encList = explode(',', $config['encoding']);
                        } elseif (is_array($config['encoding'])) {
                            $encList = $config['encoding'];
                        } elseif ( ! is_numeric($config['encoding'])) {
                            $encList = (array) $config['encoding'];
                        }

                        if (isset($encList)) {
                            $config['encoding'] = 0;

                            foreach ($encList as $encoding) {
                                switch ($encoding) {
                                    case 'deflate':
                                        $config['encoding'] &= \Sabre\DAV\Client::ENCODING_DEFLATE;
                                        break;
                                    case 'gzip':
                                        $config['encoding'] &= \Sabre\DAV\Client::ENCODING_GZIP;
                                        break;
                                    case 'identity':
                                    default:
                                        $config['encoding'] &= \Sabre\DAV\Client::ENCODING_IDENTITY;
                                        break;
                                }
                            }
                        }
                    }
                }

                $adapter = new \League\Flysystem\WebDAV\WebDAVAdapter(new \Sabre\DAV\Client($config));

                return new FilesystemAdapter($this->createFlysystem($adapter, $config), $adapter, $config);
            });
        }

        if (class_exists('\League\Flysystem\ZipArchive\ZipArchiveAdapter')) {
            $this->extend('zip', function ($app, $config) {
                $adapter = new \League\Flysystem\ZipArchive\ZipArchiveAdapter($config['path']);

                return new FilesystemAdapter($this->createFlysystem($adapter, $config), $adapter, $config);
            });
        }
    }
}
