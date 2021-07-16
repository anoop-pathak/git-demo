<?php

namespace App\Console\Commands;

use App\Models\Resource;
use Illuminate\Console\Command;

class MoveChandlersFiles extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:move_chandlers_files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move Chandle Subscribers Files from s3 to database.';

    protected $count = 0;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        // s3 client..
        // $config = config('flysystem.connections.awss3');
        //    $client = \Aws\S3\S3Client::factory($config['client']);
        // $s3Adaptor = new \League\Flysystem\AwsS3v3\AwsS3Adapter($client, $config['bucket']);
        // $this->s3 = new \League\Flysystem\Filesystem($s3Adaptor);

        // $this->validMimeTypes = array_merge(config('resources.docs_types'), config('resources.image_types'));

        // dd($this->validMimeTypes);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // $parentDir = Resource::find(497275); // chandlers subscriber resource ID

        // // $items = $this->s3->listContents("resources/chandler's_roofing_1498764355/Chandlers Files", true);
        // // dd(count($items));

        // $items = $this->s3->listContents("resources/chandler's_roofing_1498764355/Chandlers Files");

        // foreach ($items as $key => $item) {
        // 	if($item['type'] == 'dir') {
        // 		$p2 = $this->createDir($parentDir, $item['basename']);
        // 		$this->goRecursive($p2, $item['path']);
        // 	}else {

        // 		$this->createFile($parentDir, $item);
        // 	}
        // }
    }

    private function goRecursive($parentDir, $path)
    {
        $items = $this->s3->listContents($path);

        foreach ($items as $key => $item) {
            if ($item['type'] == 'dir') {
                $p2 = $this->createDir($parentDir, $item['basename']);
                $this->goRecursive($p2, $item['path']);
            } else {
                $this->createFile($parentDir, $item);
            }
        }
    }

    private function createDir($parentDir, $name)
    {
        $dir = new Resource(
            [
                'name' => $name,
                'company_id' => $parentDir->company_id,
                'size' => 0,
                'thumb_exists' => false,
                'path' => $parentDir->path . '/' . $name,
                'is_dir' => true,
                'mime_type' => null,
                'locked' => false,
                'created_by' => 1
            ]
        );

        $dir->parent_id = $parentDir->id;
        $dir->save();

        return $dir;
    }

    private function createFile($parentDir, $fileData)
    {
        $name = $fileData['basename'];
        $size = $fileData['size'];

        $ext = null;
        $mimeType = null;
        if (ine($fileData, 'extension')) {
            $ext = strtolower($fileData['extension']);
            $mimeType = extToMime($ext);
            if (empty($mimeType)) {
                $mimeType = $this->s3->getMimetype($fileData['path']);
            }
        } else {
            $mimeType = $this->s3->getMimetype($fileData['path']);
        }

        if ($ext == 'mov') {
            // Log::info($fileData);
            return;
        }

        if (!in_array($mimeType, $this->validMimeTypes)) {
            // Log::info($fileData);
            $mimeType = 'application/pdf';
        }

        $file = new Resource(
            [
                'name' => $name,
                'company_id' => $parentDir->company_id,
                'size' => $size,
                'thumb_exists' => false,
                'path' => $parentDir->path . '/' . $name,
                'is_dir' => false,
                'mime_type' => $mimeType,
                'created_by' => 1
            ]
        );

        $file->parent_id = $parentDir->id;
        $file->save();

        return $file;
    }
}
