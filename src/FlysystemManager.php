<?php

namespace Danhunsaker\Laravel\Flysystem;

use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Arr;
use League\Flysystem\AdapterInterface;
use Log;

class FlysystemManager extends FilesystemManager
{
    protected $cacheDrivers = [
        'adapter'   => 'League\Flysystem\Cached\Storage\Adapter',
        'laravel'   => 'Madewithlove\IlluminatePsrCacheBridge\Laravel\CacheItemPool',
        'memcached' => 'League\Flysystem\Cached\Storage\Memcached',
        'memory'    => 'League\Flysystem\Cached\Storage\Memory',
        'noop'      => 'League\Flysystem\Cached\Storage\Noop',
        'redis'     => 'League\Flysystem\Cached\Storage\Predis',
        'stash'     => 'League\Flysystem\Cached\Storage\Stash',
    ];

    /**
     * {@inheritdoc}
     */
    public function __construct($app)
    {
        parent::__construct($app);

        if (class_exists('\League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter')) {
            $this->extend('azure', function ($app, $config) {
                $endpoint = sprintf('DefaultEndpointsProtocol=https;AccountName=%s;AccountKey=%s', $config['accountName'], $config['apiKey']);
                $client = \MicrosoftAzure\Storage\Blob\BlobRestProxy::createBlobService($endpoint);

                return $this->createFlysystem(new \League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter($client, $config['container']), $config);
            });
        }

        if (class_exists('\League\Flysystem\GridFS\GridFSAdapter')) {
            $this->extend('gridfs', function ($app, $config) {
                $mongoClient = new \MongoClient($config['server'], Arr::except($config, ['driver', 'server', 'context', 'dbName']), Arr::get($config, 'context', null));
                $gridFs = $mongoClient->selectDB($config['dbName'])->getGridFS();

                return $this->createFlysystem(new \League\Flysystem\GridFS\GridFSAdapter($gridFs), $config);
            });
        }

        if (class_exists('\League\Flysystem\Memory\MemoryAdapter')) {
            $this->extend('memory', function ($app, $config) {
                return $this->createFlysystem(new \League\Flysystem\Memory\MemoryAdapter(), $config);
            });
        }

        if (class_exists('\League\Flysystem\Phpcr\PhpcrAdapter')) {
            $this->extend('phpcr', function ($app, $config) {
                $credentials = new \PHPCR\SimpleCredentials(null, null);
                $logger = new \Jackalope\Transport\LoggingPsr3Logger(Log::getMonolog());

                if (class_exists('\Jackalope\RepositoryFactoryJackrabbit')) {
                    $repository = (new \Jackalope\RepositoryFactoryJackrabbit())->getRepository([
                        "jackalope.jackrabbit_uri" => $config['jackrabbit_url'],
                        'jackalope.logger'         => $logger,
                    ]);
                    $credentials = new \PHPCR\SimpleCredentials($config['user'], $config['pass']);
                } elseif (class_exists('Jackalope\RepositoryFactoryDoctrineDBAL')) {
                    $repository = (new \Jackalope\RepositoryFactoryDoctrineDBAL())->getRepository([
                        'jackalope.doctrine_dbal_connection' => \Doctrine\DBAL\DriverManager::getConnection([
                                'pdo'  => DB::connection(Arr::get($config, 'database'))->getPdo(),
                            ]),
                        'jackalope.logger' => $logger,
                    ]);
                } elseif (class_exists('\Jackalope\RepositoryFactoryPrismic')) {
                    $repository = (new \Jackalope\RepositoryFactoryPrismic())->getRepository([
                        'jackalope.prismic_uri' => $config['prismic_uri'],
                        'jackalope.logger'      => $logger,
                    ]);
                } else {
                    throw new \League\Flysystem\NotSupportedException("Couldn't find supported PHPCR Repository implementation.  Install and configure one of Jackalope's JackRabbit, Doctrine DBAL, or Prismic.io implementations and try again.");
                }

                $session = $repository->login($credentials, $config['workspace']);

                return $this->createFlysystem(new \League\Flysystem\Phpcr\PhpcrAdapter($session, $config['root']), $config);
            });
        }

        if (class_exists('\League\Flysystem\Replicate\ReplicateAdapter')) {
            $this->extend('replicate', function ($app, $config) {
                return $this->createFlysystem(new \League\Flysystem\Replicate\ReplicateAdapter($this->disk($config['master'])->getAdapter(), $this->disk($config['replica'])->getAdapter()), $config);
            });
        }

        if (class_exists('\League\Flysystem\Sftp\SftpAdapter')) {
            $this->extend('sftp', function ($app, $config) {
                return $this->createFlysystem(new \League\Flysystem\Sftp\SftpAdapter($config), $config);
            });
        }

        if (class_exists('\League\Flysystem\Vfs\VfsAdapter')) {
            $this->extend('vfs', function ($app, $config) {
                return $this->createFlysystem(new \League\Flysystem\Vfs\VfsAdapter(new VirtualFileSystem\FileSystem()), $config);
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

                return $this->createFlysystem(new \League\Flysystem\WebDAV\WebDAVAdapter(new \Sabre\DAV\Client($config)), $config);
            });
        }

        if (class_exists('\League\Flysystem\ZipArchive\ZipArchiveAdapter')) {
            $this->extend('zip', function ($app, $config) {
                return $this->createFlysystem(new \League\Flysystem\ZipArchive\ZipArchiveAdapter($config['path']), $config);
            });
        }
    }

    protected function skipOverride()
    {
        return method_exists(parent::class, 'createFlysystem');
    }

    /**
     * {@inheritdoc}
     */
    public function createLocalDriver(array $config)
    {
        if ($this->skipOverride()) {
            $adapter = parent::createLocalDriver($config);
        } else {
            $adapter = $this->adapt($this->createFlysystem(parent::createLocalDriver($config)->getAdapter(), $config));
        }

        return $adapter;
    }

    /**
     * {@inheritdoc}
     */
    public function createFtpDriver(array $config)
    {
        if ($this->skipOverride()) {
            $adapter = parent::createFtpDriver($config);
        } else {
            $adapter = $this->adapt($this->createFlysystem(parent::createFtpDriver($config)->getAdapter(), $config));
        }

        return $adapter;
    }

    /**
     * {@inheritdoc}
     */
    public function createS3Driver(array $config)
    {
        if ($this->skipOverride()) {
            $adapter = parent::createS3Driver($config);
        } else {
            $adapter = $this->adapt($this->createFlysystem(parent::createS3Driver($config)->getAdapter(), $config));
        }

        return $adapter;
    }

    /**
     * {@inheritdoc}
     */
    public function createRackspaceDriver(array $config)
    {
        if ($this->skipOverride()) {
            $adapter = parent::createRackspaceDriver($config);
        } else {
            $adapter = $this->adapt($this->createFlysystem(parent::createRackspaceDriver($config)->getAdapter(), $config));
        }

        return $adapter;
    }

    /**
     * Create an instance of the null driver.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public function createNullDriver(array $config)
    {
        return $this->adapt($this->createFlysystem(new \League\Flysystem\Adapter\NullAdapter, $config));
    }

    /**
     * Decorate a Flysystem adapter with a metadata cache.
     *
     * @param  \League\Flysystem\AdapterInterface  $adapter
     * @param  array  $config
     * @return \League\Flysystem\AdapterInterface
     */
    protected function decorateWithCachedAdapter(AdapterInterface $adapter, array $config)
    {
        if (
            ($cacheDriver = Arr::get($config, 'driver', false)) !== false &&
            (is_a($cacheDriver, 'League\Flysystem\Cached\CacheInterface') ||
                class_exists($driverName = Arr::get($this->cacheDrivers, $cacheDriver, $cacheDriver))
            )
        ) {
            if (isset($driverName)) {
                switch ($driverName) {
                    case 'League\Flysystem\Cached\Storage\Adapter':
                        $cacheDriver = new \League\Flysystem\Cached\Storage\Adapter($this->disk(Arr::get($config, 'disk', 'local'))->getAdapter(), Arr::get($config, 'file', 'flysystem.cache'), Arr::get($config, 'expire', null));
                        break;
                    case 'Madewithlove\IlluminatePsrCacheBridge\Laravel\CacheItemPool':
                        $cacheDriver = new \League\Flysystem\Cached\Storage\Psr6Cache(new \Madewithlove\IlluminatePsrCacheBridge\Laravel\CacheItemPool($this->app->make(\Illuminate\Contracts\Cache\Repository::class)), Arr::get($config, 'key', 'flysystem'), Arr::get($config, 'expire', null));
                        break;
                    case 'League\Flysystem\Cached\Storage\Memcached':
                        if (class_exists('Memcached')) {
                            $memcached = new \Memcached;
                            $memcached->addServer(Arr::get($config, 'host', 'localhost'), Arr::get($config, 'port', 11211));

                            $cacheDriver = new \League\Flysystem\Cached\Storage\Memcached($memcached, Arr::get($config, 'key', 'flysystem'), Arr::get($config, 'expire', null));
                        } else {
                            return $adapter;
                        }
                        break;
                    case 'League\Flysystem\Cached\Storage\Memory':
                        $cacheDriver = new \League\Flysystem\Cached\Storage\Memory;
                        break;
                    case 'League\Flysystem\Cached\Storage\Noop':
                        $cacheDriver = new \League\Flysystem\Cached\Storage\Noop;
                        break;
                    case 'League\Flysystem\Cached\Storage\PhpRedis':
                    case 'League\Flysystem\Cached\Storage\Predis':
                        $cacheDriver = new \League\Flysystem\Cached\Storage\Predis(\Illuminate\Support\Facades\Redis::connection(Arr::get($config, 'connection', 'default')), Arr::get($config, 'key', 'flysystem'), Arr::get($config, 'expire', null));
                        break;
                    case 'League\Flysystem\Cached\Storage\Stash':
                        if (class_exists('Stash\Pool')) {
                            if (($backend = Arr::get($config, 'backend', false)) !== false && (is_a($backend, 'Stash\Interfaces\DriverInterface') || class_exists($backend))) {
                                if (is_a($backend, 'Stash\Interfaces\DriverInterface')) {
                                    $backendInstance = $backend;
                                } else {
                                    $backendInstance = new $backend;
                                    $backendInstance->setOptions(Arr::get($config, 'options', []));
                                }

                                $pool = new \Stash\Pool($backendInstance);
                            } else {
                                $pool = new \Stash\Pool;
                            }

                            $cacheDriver = new \League\Flysystem\Cached\Storage\Stash($pool, Arr::get($config, 'key', 'flysystem'), Arr::get($config, 'expire', null));
                        } else {
                            return $adapter;
                        }
                        break;
                    default:
                        return $adapter;
                }
            }

            return new \League\Flysystem\Cached\CachedAdapter($adapter, $cacheDriver);
        } else {
            return $adapter;
        }
    }

    /**
     * Create a Flysystem instance with the given adapter.
     *
     * @param  \League\Flysystem\AdapterInterface  $adapter
     * @param  array  $config
     * @return \League\Flysystem\FlysystemInterface
     */
    protected function createFlysystem(AdapterInterface $adapter, array $config, array $driverConfig = null)
    {
        if (class_exists('League\Flysystem\EventableFilesystem\EventableFilesystem')) {
            $fsClass = \League\Flysystem\EventableFilesystem\EventableFilesystem::class;
        } else {
            $fsClass = \League\Flysystem\Filesystem::class;
        }

        if (class_exists('League\Flysystem\Cached\CachedAdapter')) {
            $adapter = $this->decorateWithCachedAdapter($adapter, Arr::get($config, 'cache', []));
        }

        $config = Arr::only($config, ['visibility']);
        $config = array_merge($config, (array) $driverConfig);

        return new $fsClass($adapter, count($config) > 0 ? $config : null);
    }
}
