<?php

namespace App\Console\Commands;

use App\Models\Resource;
use FlySystem;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CreateResourcesThumb extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:create_resources_thumb';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create resources thumb for chandler 11 gb data';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->bucket = config('flysystem.connections.awss3.bucket');

        $this->request = new Client([
            'base_uri' => 'https://tvmwznbdlk.execute-api.us-west-2.amazonaws.com/prod/'
        ]);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $exclude = [];

        //get thumb resource ids
        if (File::exists('resources_thumb2.txt')) {
            $exclude = explode(',', rtrim(File::get('resources_thumb2.txt'), ','));
        }

        // $parentDir = Resource::find(497275);//chandler
        // // $parentDir = Resource::find(704317);//local
        // if(!$parentDir) return;
        // $resources = $parentDir->descendants()
        // 	->whereIn('mime_type', config('resources.image_types'))
        // 	->whereNotIn('id', $exclude)
        $resources = Resource::whereIn('mime_type', ['image/jpeg', 'image/jpg', 'image/png'])
            ->whereRaw("(CAST(path AS BINARY) LIKE '%.PNG%' or CAST(path AS BINARY) LIKE '%.JPG%' or CAST(path AS BINARY) LIKE '%.jpeg%' or CAST(path AS BINARY) LIKE '%.JPEG%') AND created_at > '2017-02-16 00:00:00'")
            ->whereNotIn('id', $exclude)
            ->orderBy('id', 'desc');

        // dd($resources->count());
        $resources->chunk(200, function ($resources) {
            foreach ($resources as $resource) {
                $this->createThumbUsingLamda($resource);

                //check thumb exist if not then create
                // $thumbPath = preg_replace('/(\.gif|\.jpg|\.png|\.jpeg)/i', '_thumb$1', $resource->path);
                // if (!FlySystem::exists(config('resources.BASE_PATH').$thumbPath))  {
                // 	$this->createThumb($resource);
                // }

                File::append('resources_thumb2.txt', $resource->id . ',');
            }
        });
    }

    private function createThumbUsingLamda($resource)
    {
        $fullPath = config('resources.BASE_PATH') . $resource->path;
        try {
            $response = $this->request->get('create-tumbs', [
                'query' => [
                    'bucket' => $this->bucket,
                    'key' => $fullPath,
                    'type' => 'resource',
                ]
            ]);
        } catch (\Exception $e) {
            Log::warning('Error Resource Thumb Creation: ' . $resource->id);
        }
    }

    // create thumb
    private function createThumb($resource)
    {
        $fullPath = config('resources.BASE_PATH') . $resource->path;
        $img = FlySystem::read($fullPath);
        $thumb = \Image::make($img);

        if ($thumb->height() > $thumb->width()) {
            $thumb->heighten(200, function ($constraint) {
                $constraint->upsize();
            });
        } else {
            $thumb->widen(200, function ($constraint) {
                $constraint->upsize();
            });
        }

        //thumb path
        $thumbPath = preg_replace('/(\.gif|\.jpg|\.png|\.jpeg)/i', '_thumb$1', $resource->path);

        // upload thumb..
        FlySystem::put(config('resources.BASE_PATH') . $thumbPath, $thumb->encode()->getEncoded());
    }
}
