<?php

namespace Danhunsaker\Laravel\Flysystem;

use Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

class FlysystemServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if (class_exists('\League\Flysystem\EventableFilesystem\EventableFilesystem'))
        {
            $fsClass = \League\Flysystem\EventableFilesystem\EventableFilesystem::class;
            
            Storage::extend('local', function($app, $config) {
                $permissions = isset($config['permissions']) ? $config['permissions'] : [];

                return new $fsClass(new \League\Flysystem\Adapter\Local($config['root'], LOCK_EX, \League\Flysystem\Adapter\Local::DISALLOW_LINKS, $permissions));
            });

            Storage::extend('ftp', function($app, $config) {
                $ftpConfig = Arr::only($config, [
                    'host', 'username', 'password', 'port', 'root', 'passive', 'ssl', 'timeout',
                ]);

                return new $fsClass(new \League\Flysystem\Adapter\Ftp($ftpConfig));
            });

            if (class_exists('\League\Flysystem\AwsS3v3\AwsS3Adapter'))
            {
                Storage::extend('s3', function($app, $config) {
                    $config += ['version' => 'latest'];

                    if ($config['key'] && $config['secret']) {
                        $config['credentials'] = Arr::only($config, ['key', 'secret']);
                    }

                    $root = isset($config['root']) ? $config['root'] : null;

                    return $this->adapt(
                        new $fsClass(new \League\Flysystem\AwsS3v3\AwsS3Adapter(new \Aws\S3\S3Client($config), $config['bucket'], $root))
                    );
                });
            }
            
            if (class_exists('\League\Flysystem\Rackspace\RackspaceAdapter'))
            {
                Storage::extend('rackspace', function($app, $config) {
                    $client = new \OpenCloud\Rackspace($config['endpoint'], [
                        'username' => $config['username'], 'apiKey' => $config['key'],
                    ]);

                    $urlType = Arr::get($config, 'url_type');

                    $store = $client->objectStoreService('cloudFiles', $config['region'], $urlType);

                    return new $fsClass(new \League\Flysystem\Rackspace\RackspaceAdapter($store->getContainer($config['container'])));
                });
            }
        }
        else
        {
            $fsClass = \League\Flysystem\Filesystem::class;
        }
        
        if (class_exists('\League\Flysystem\Azure\AzureAdapter'))
        {
            Storage::extend('azure', function($app, $config) {
                $endpoint = sprintf('DefaultEndpointsProtocol=https;AccountName=%s;AccountKey=%s', $config['accountName'], $config['apiKey']);
                $client = \WindowsAzure\Common\ServicesBuilder::getInstance()->createBlobService($endpoint);

                return new $fsClass(new \League\Flysystem\Azure\AzureAdapter($client, $config['container']));
            });
        }
        
        if (class_exists('\League\Flysystem\Copy\CopyAdapter'))
        {
            Storage::extend('copy', function($app, $config) {
                $client = new \Barracuda\Copy\API($config['consumerKey'], $config['consumerSecret'], $config['accessToken'], $config['tokenSecret']);
                return new $fsClass(new \League\Flysystem\Copy\CopyAdapter($client));
            });
        }
        
        if (class_exists('\League\Flysystem\Dropbox\DropboxAdapter'))
        {
            Storage::extend('dropbox', function($app, $config) {
                $client = new \Dropbox\Client($config['accessToken'], $config['clientIdentifier']);
                return new $fsClass(new \League\Flysystem\Dropbox\DropboxAdapter($client));
            });
        }
        
        if (class_exists('\League\Flysystem\GridFS\GridFSAdapter'))
        {
            Storage::extend('gridfs', function($app, $config) {
                $mongoClient = new \MongoClient($config['server'], Arr::except($config, ['driver', 'server', 'context', 'dbName']), Arr::get($config, 'context', null));
                $gridFs = $mongoClient->selectDB($config['dbName'])->getGridFS();

                return new $fsClass(new \League\Flysystem\GridFS\GridFSAdapter($gridFs));
            });
        }
        
        if (class_exists('\League\Flysystem\Memory\MemoryAdapter'))
        {
            Storage::extend('memory', function($app, $config) {
                return new $fsClass(new \League\Flysystem\Memory\MemoryAdapter());
            });
        }
        
        if (class_exists('\League\Flysystem\Phpcr\PhpcrAdapter'))
        {
            Storage::extend('replicate', function($app, $config) {
                $credentials = new \PHPCR\SimpleCredentials(null, null);
                $logger = new \Jackalope\Transport\LoggingPsr3Logger(Log::getMonolog());
                
                if (class_exists('\Jackalope\RepositoryFactoryJackrabbit'))
                {
                    $repository = new \Jackalope\RepositoryFactoryJackrabbit()->getRepository([
                        "jackalope.jackrabbit_uri" => $config['jackrabbit_url'],
                        'jackalope.logger' => $logger,
                    ]);
                    $credentials = new \PHPCR\SimpleCredentials($config['user'], $config['pass']);
                }
                elseif (class_exists('Jackalope\RepositoryFactoryDoctrineDBAL'))
                {
                    $repository = new \Jackalope\RepositoryFactoryDoctrineDBAL()->getRepository([
                        'jackalope.doctrine_dbal_connection' => \Doctrine\DBAL\DriverManager::getConnection(
                                Arr::only($config, ['driver', 'host', 'user', 'password', 'dbname', 'path'])
                            ),
                        'jackalope.logger' => $logger,
                    ]);
                }
                elseif (class_exists('\Jackalope\RepositoryFactoryPrismic'))
                {
                    $repository = new \Jackalope\RepositoryFactoryPrismic()->getRepository([
                        'jackalope.prismic_uri' => $config['prismic_uri'],
                        'jackalope.logger' => $logger,
                    ]);
                }
                else
                {
                    throw new \League\Flysystem\NotSupportedException("Couldn't find supported PHPCR Repository implementation.  Install and configure one of Jackalope's JackRabbit, Doctiren DBAL, or Prismic.io implementations and try again.");
                }
                
                $session = $repository->login($credentials, $config['workspace']);
                
                return new $fsClass(new \League\Flysystem\Phpcr\PhpcrAdapter($session, $config['root']));
            });
        }
        
        if (class_exists('\League\Flysystem\Replicate\ReplicateAdapter'))
        {
            Storage::extend('replicate', function($app, $config) {
                return new $fsClass(new \League\Flysystem\Replicate\ReplicateAdapter(Storage::disk($config['master'])->getAdapter(), Storage::disk($config['replica'])->getAdapter()));
            });
        }
        
        if (class_exists('\League\Flysystem\Sftp\SftpAdapter'))
        {
            Storage::extend('sftp', function($app, $config) {
                return new $fsClass(new \League\Flysystem\Sftp\SftpAdapter($config));
            });
        }
        
        if (class_exists('\League\Flysystem\Vfs\VfsAdapter'))
        {
            Storage::extend('sftp', function($app, $config) {
                return new $fsClass(new \League\Flysystem\Vfs\VfsAdapter(new VirtualFileSystem\FileSystem()));
            });
        }
        
        if (class_exists('\League\Flysystem\WebDAV\WebDAVAdapter'))
        {
            Storage::extend('webdav', function($app, $config) {
                if ( ! empty($config['authType']))
                {
                    if (is_string($config['authType']) && strtolower($config['authType']) == 'digest')
                    {
                        $config['authType'] = \Sabre\DAV\Client::AUTH_DIGEST;
                    }
                    else
                    {
                        $config['authType'] = \Sabre\DAV\Client::AUTH_BASIC;
                    }
                }

                if ( ! empty($config['encoding']))
                {
                    if (is_string($config['encoding']) && strtolower($config['encoding']) == 'all')
                    {
                        $config['encoding'] = \Sabre\DAV\Client::ENCODING_ALL;
                    }
                    else
                    {
                        if (is_string($config['encoding']))
                        {
                            $encList = explode(',', $config['encoding']);
                        }
                        elseif (is_array($config['encoding']))
                        {
                            $encList = $config['encoding'];
                        }
                        elseif ( ! is_numeric($config['encoding']))
                        {
                            $encList = (array) $config['encoding'];
                        }
                        
                        if (isset($encList))
                        {
                            $config['encoding'] = 0;

                            foreach ($encList as $encoding)
                            {
                                switch ($encoding)
                                {
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

                return new $fsClass(new \League\Flysystem\WebDAV\WebDAVAdapter(new \Sabre\DAV\Client($config)));
            });
        }
        
        if (class_exists('\League\Flysystem\ZipArchive\ZipArchiveAdapter'))
        {
            Storage::extend('zip', function($app, $config) {
                return new $fsClass(new \League\Flysystem\ZipArchive\ZipArchiveAdapter($config['path']));
            });
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
