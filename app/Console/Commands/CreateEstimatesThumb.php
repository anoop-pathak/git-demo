<?php

namespace App\Console\Commands;

use App\Models\Estimation;
use FlySystem;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CreateEstimatesThumb extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:create_estimates_thumb';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create estimates thumb';

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

        //get thumb estimate ids
        if (File::exists('estimates_thumb2.txt')) {
            $exclude = explode(',', rtrim(File::get('estimates_thumb2.txt'), ','));
        }

        $estimates = Estimation::whereIn('file_mime_type', config('resources.image_types'))
            ->where('file_path', 'Like', '%.jpeg%')
            // ->whereNull('thumb')
            ->orderBy('id', 'desc')
            ->whereNotIn('id', $exclude);
        //->withTrashed()

        $estimates->chunk(200, function ($estimations) {
            foreach ($estimations as $estimate) {
                //create thumb
                $this->createThumbUsingLamda($estimate);

                //create thumb
                //$this->createThumb($estimate);
                // File::append('estimates_thumb2.txt', $estimate->id .',');
            }
        });
    }

    private function createThumbUsingLamda($estimate)
    {
        $fullPath = config('jp.BASE_PATH') . $estimate->file_path;
        try {
            $response = $this->request->get('create-tumbs', [
                'query' => [
                    'bucket' => $this->bucket,
                    'key' => $fullPath,
                    'type' => 'public',
                ]
            ]);
            $response = json_decode($response->getBody());

            // if(sizeof($response)) {
            // 	// thumb path
            // 	$thumbPath = 'estimations/thumb/'. basename($estimate->file_path);

            // 	//save thumb
            //  $estimate->update(['thumb' => $thumbPath]);
            // }else{
            //  Log::warning('Error Proposal Not exists: '.$estimate->id);
            // }
        } catch (\Exception $e) {
            Log::warning('Error Estimate Thumb Creation: ' . $estimate->id);
        }
    }

    private function createThumb($estimate)
    {
        $fullPath = config('jp.BASE_PATH') . $estimate->file_path;
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

        // thumb path
        $thumbPath = 'estimations/thumb/' . basename($estimate->file_path);

        // upload thumb..
        FlySystem::put(config('jp.BASE_PATH') . $thumbPath, $thumb->encode()->getEncoded());

        //save thumb
        $estimate->update(['thumb' => $thumbPath]);
    }
}
