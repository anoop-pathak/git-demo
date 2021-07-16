<?php

namespace App\Services\FileSystem;

use Aws\S3\S3Client;
use Carbon\Carbon;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use FlySystem as FlySystemFacade;
use Illuminate\Support\Facades\App;

class FlySystem extends Filesystem
{

    protected static $connection;
    protected $secondConnectionName = null;

    protected $connectionChanged = false;

    function __construct($adapter = null)
    {
        if (!$adapter) {
            $this->setAdapter();
        } else {
            $this->adapter = $adapter;
        }

        // Set storage drives for mount manager..
        $local = new \League\Flysystem\Filesystem($this->localAdaptor());
        $s3 = new \League\Flysystem\Filesystem($this->awss3Adapter());
        $s3Attachments = new \League\Flysystem\Filesystem($this->awss3Adapter('s3_attachments'));

        // Add them in the constructor
        $this->mountManager = new \League\Flysystem\MountManager([
            'local' => $local,
            's3' => $s3,
            's3_attachments' => $s3Attachments,
        ]);
    }

    /**
     * Set connection
     * @param  String $connectionName | local, s3
     * @return class instance
     */
    public function connection($connectionName = null)
    {
        $this->setAdapter($connectionName);
        return new self($this->adapter);
    }

    /**
     * Get Public Url
     * @param  String $key | relative path
     * @return string | url
     */
    public function publicUrl($key)
    {
        /**
         * Added a condition to remove the urlencoding when flysystem is set to local; Was creating an issue
         */
        // if(config('flysystem.default') == 'local') {
        //     $key = dirname($key) . '/' . basename($key);
        // } else {
        //     $key = dirname($key) . '/' . urlencode(basename($key));
        // }

        if(!App::environment('local')) {
            $key = urlencode($key);
        }
        $url = $this->buildPublicUrl($key);
        $this->resetConnection();
        return $url;
        // return config('flysystem.public_url').base64_encode($key);
    }

    /**
     * Get Signed Url for Awss3 or local public url
     * @param  String $key | relative path
     * @return string | url
     */
    public function getUrl($key, $signed = true)
    {
        // $this->switchConnection($key);
        // $key = urlencode($key);
        switch (self::$connection) {
            case 'local':
                $url = $this->localPublicUrl($key);
                break;
            case 's3':
                $url = $this->getAwss3SignedUrl($key, $signed);
                break;
            case 's3_attachments':
                $url = $this->getS3AttachmentUrl($key);
                break;
            default:
                $url = $this->localPublicUrl($key);
                break;
        }
        $this->resetConnection();
        return $url;
    }

    /**
     * File Read Stream
     * @param  String $path | Path to file
     * @return file contents
     */
    public function readStream($path)
    {
        // $this->switchConnection($path);
        $stream = parent::readStream($path);
        $contents = stream_get_contents($stream);
        fclose($stream);
        $this->resetConnection();
        return $stream;
    }

    /**
     * Create a file or update if exists.
     *
     * @param string $path The path to the file.
     * @param string $contents The file contents.
     * @param array $config An optional configuration array.
     *
     * @return bool True on success, false on failure.
     */
    public function put($path, $contents, array $config = [])
    {
        $ret = parent::put($path, $contents, $config);
        $this->resetConnection();

        return $ret;
    }

    /**
     * File Read
     * @param  String $path | Path to file
     * @return file contents
     */
    public function read($path)
    {
        // $this->switchConnection($path);
        $ret = parent::read($path);
        $this->resetConnection();
        return $ret;
    }

    /**
     * Get the mimetype of a file.
     *
     * @param string $path
     *
     * @return false|array
     */
    public function getMimetype($path)
    {
        // $this->switchConnection($path);
        $ret = parent::getMimetype($path);
        $this->resetConnection();
        return $ret;
    }

    /**
     * Copy File From one path to other
     * @param  [type] $path    [description]
     * @param  [type] $newpath [description]
     * @param  [type] $public [description]
     * @return [type]          [description]
     */
    public function copy($path, $newpath, $config = [])
    {

        // $this->switchConnection($path);
        $connection = self::$connection;

        if ($this->secondConnectionName) {
            $secondStorage = $this->secondConnectionName;
        } else {
            $secondStorage = config('flysystem.default');
        }

        $ret = $this->mountManager->copy("$connection://$path", "$secondStorage://$newpath", $config);
        // $ret = parent::copy($path, $newpath);
        $this->resetConnection();
        $this->secondConnectionName = null;
        return $ret;
    }

    /**
     * Move File From one path to other
     * @param  [type] $path    [description]
     * @param  [type] $newpath [description]
     * @param  [type] $public [description]
     * @return [type]          [description]
     */
    public function move($path, $newpath, $public = false)
    {
        $config = [];

        if ($public) {
            // change file permissions..
            $config['ACL'] = 'public-read';
        }

        // $this->switchConnection($path);
        $connection = self::$connection;

        if ($this->secondConnectionName) {
            $secondStorage = $this->secondConnectionName;
        } else {
            $secondStorage = config('flysystem.default');
        }

        $ret = $this->mountManager->move("$connection://$path", "$secondStorage://$newpath", $config);
        // $ret = parent::copy($path, $newpath);
        $this->resetConnection();
        $this->secondConnectionName = null;

        return $ret;
    }

    /**
     * File Write Stream
     * @param  String $writePath | Write Path
     * @param  Object $file | File resource
     * @param  Array $options | Options
     * @return bool
     */
    public function writeStream($writePath, $file, array $options = [])
    {
        $stream = fopen($file->getRealPath(), 'r+');
        parent::writeStream($writePath, $stream, $options);
        fclose($stream);
        return true;
    }

    /**
     * Write a new file.
     *
     * @param string $path The path of the new file.
     * @param string $contents The file contents.
     * @param array $config An optional configuration array.
     *
     * @throws FileExistsException
     *
     * @return bool True on success, false on failure.
     */
    public function write($path, $contents, array $config = [])
    {
        $ret = parent::write($path, $contents, $config);
        $this->resetConnection();

        return $ret;
    }

    /**
     * File Delete
     * @param  String $path | Path to file
     * @return file contents
     */
    public function delete($path)
    {
        $ret = true;
        // $this->switchConnection($path);
        if ($this->has($path)) {
            $ret = parent::delete($path);
        }

        $this->resetConnection();
        return $ret;
    }


    /**
     * Upload public object
     * @param  string $path | Path to file
     * @param  file $contents | File content
     * @return public url
     */
    public function uploadPublicaly($path, $contents)
    {
        // $this->setConfig(['ACL' => 'public-read']);
        $this->put($path, $contents, ['ACL' => 'public-read']);
        return $this->getUrl($path, false);
    }

    /**
     * Determine if file exist
     * @param  URL $path file
     * @return Bool
     */
    public function exists($path)
    {
        $ret = false;
        // $this->switchConnection($path);
        if ($this->has($path)) {
            $ret = true;
        }

        $this->resetConnection();

        return $ret;
    }

    /**
     * Set Second connection for copy and move functions as a desctination path storage
     * @param  String $connectionName | local, s3, 's3_attachment'
     * @return class instance
     */
    public function setSecondConnection($connectionName)
    {
        $this->secondConnectionName = $connectionName;

        return $this;
    }

    /**
     * download img and pdf files
     */
    public function download($filePath, $fileName, $options = [])
    {
        $options['Content-Disposition'] = 'attachment; filename="' . $fileName . '"';

        $newPath = 'download_files/' . uniqueTimestamp() . '_' . $fileName;
        $newFile = FlySystemFacade::copy($filePath, $newPath, $options);

        return \redirect(FlySystemFacade::publicUrl($newPath));
    }

    /***************** Private Section ********************/

    private function setAdapter($connection = null)
    {
        if (!$connection) {
            $connection = config('flysystem.default');
        }

        self::$connection = $connection;

        switch ($connection) {
            case 'local':
                $adapter = $this->localAdaptor();
                break;
            case 's3':
                $adapter = $this->awss3Adapter();
                break;
            case 's3_attachments':
                $adapter = $this->awss3Adapter('s3_attachments');
                break;
            default:
                $adapter = $this->localAdaptor();
                break;
        }
        $this->adapter = $adapter;
        return $adapter;
    }

    private function localAdaptor()
    {
        $config = config('flysystem.connections.local');
        return new Local(
            $config['base_path'],
            LOCK_EX,
            Local::DISALLOW_LINKS,
            $config['permissions']
        );

        return new Local(config('resources.BASE_PATH'));
    }

    private function awss3Adapter($connection = 's3')
    {
        $config = config("flysystem.connections.$connection");
        $client = S3Client::factory($config['client']);

        return new AwsS3Adapter($client, $config['bucket']);
    }

    /**
     * Build Public url according to connection
     * @param  String $key | Relative path of resource
     * @return String  Url
     */
    private function buildPublicUrl($key)
    {

        switch (self::$connection) {
            case 'local':
                $url = $this->localPublicUrl($key);
                break;
            case 's3':
                $url = $this->awss3PublicUrl($key);
                break;
            case 's3_attachments':
                $url = $this->getS3AttachmentUrl($key);
                break;
            default:
                $url = $this->localPublicUrl($key);
                break;
        }
        return $url;
    }

    /**
     * Build Public URL for local connection
     * @param  String $key | Relative path of resource
     * @return String  Url
     */
    private function localPublicUrl($key)
    {
        return config('jp.app_url_for_flysystem') . $key . "?file=" . Carbon::now()->timestamp;
    }

    /**
     * Build Public URL for awss3 connection
     * @param  String $key | Relative path of resource
     * @return String  Url
     */
    private function awss3PublicUrl($key)
    {
        /* aws public url */
        // $config = config('flysystem.connections.s3');
        // $client = S3Client::factory($config['client']);
        // return $client->getObjectUrl($config['bucket'], $key);

        /* cloudfront url for signed cookies */
        return 'https://' . config('cloud-front.CDN_HOST') . '/' . $key;

        /* cloudfront signed url */
        // $cloudFront = new \Aws\CloudFront\CloudFrontClient([
        //     'region'  => 'us-west-2',
        //     'version' => 'latest'
        // ]);

        // // Setup parameter values for the resource
        // $streamHostUrl = 'https://'.config('cloud-front.CDN_HOST');
        // $expires = time() + 300;

        // // Create a signed URL for the resource using the canned policy
        // return $signedUrlCannedPolicy = $cloudFront->getSignedUrl([
        //     'url'         => $streamHostUrl . '/' . $key,
        //     'expires'     => $expires,
        //     'private_key' => config('cloud-front.CLOUDFRONT_KEY_PATH'),
        //     'key_pair_id' => config('cloud-front.CLOUDFRONT_KEY_PAIR_ID'),
        // ]);
    }

    /**
     * Get Signed Url for Aws3 files
     * @param  String $key | relative path
     * @return string | url
     */
    public function getAwss3SignedUrl($key, $signed = true)
    {
        $config = config('flysystem.connections.s3');
        $client = S3Client::factory($config['client']);

        if (!$signed) {
            return $client->getObjectUrl($config['bucket'], $key);
        }

        $command = $client->getCommand('GetObject', [
            'Bucket' => $config['bucket'],
            'Key' => $key,
            // 'ResponseContentDisposition' => 'attachment; filename="abc.jpg"'
        ]);

        // Create a signed URL from the command object that will last for
        // 10 minutes from the current time
        $signedUrl = $client->createPresignedRequest($command, '+20 minutes')->getUri()->__toString();
        return $signedUrl;
    }

    private function getS3AttachmentUrl($key)
    {
        $cdnHost = config('cloud-front.ATTACHMENTS_CDN_HOST');

        if ($cdnHost) {
            return 'https://' . $cdnHost . '/' . $key;

            /* cloudfront signed url */
            // $cloudFront = new \Aws\CloudFront\CloudFrontClient([
            //     'region'  => 'us-west-2',
            //     'version' => 'latest'
            // ]);

            // // Setup parameter values for the resource
            // $streamHostUrl = 'https://'.$cdnHost;
            // $expires = time() + 3600; // appx 15 years validity..
            // Log::info($expires);
            // // Create a signed URL for the resource using the canned policy
            // return $signedUrlCannedPolicy = $cloudFront->getSignedUrl([
            //     'url'         => $streamHostUrl . '/' . $key,
            //     'expires'     => $expires,
            //     'private_key' => config('cloud-front.CLOUDFRONT_KEY_PATH'),
            //     'key_pair_id' => config('cloud-front.CLOUDFRONT_KEY_PAIR_ID'),
            // ]);
        }

        $config = config('flysystem.connections.s3_attachments');
        $client = S3Client::factory($config['client']);

        return $client->getObjectUrl($config['bucket'], $key);
    }

    /**
     * Shift Connection local to s3
     * @param  String $key | File path key
     * @return void
     */
    private function switchConnection($key)
    {
        if (!$this->connection('local')->has($key)) {
            $this->resetConnection();
        }
    }

    /**
     * Reset connection to default after switching.
     * @return void
     */
    private function resetConnection()
    {
        // if(self::$connection != config('flysystem.default')) {
        $this->connection(null);
        // }
    }
}
