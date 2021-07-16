<?php

namespace App\Console\Commands;

use App\Models\Proposal;
use FlySystem;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CreateProposalsThumb extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:create_proposals_thumb';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create proposals thumb';

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

        //get thumb proposal ids
        if (File::exists('proposals_thumb2.txt')) {
            $exclude = explode(',', rtrim(File::get('proposals_thumb2.txt'), ','));
        }

        $proposals = Proposal::whereIn('file_mime_type', config('resources.image_types'))
            ->where('file_path', 'Like', '%.jpeg%')
            // ->whereNull('thumb')
            ->orderBy('id', 'desc')
            // ->withTrashed()
            ->whereNotIn('id', $exclude);


        $proposals->chunk(200, function ($proposals) {
            foreach ($proposals as $proposal) {
                //create thumb
                $this->createThumbUsingLamda($proposal);
                // //create thumb
                // $this->createThumb($proposal);
                File::append('proposals_thumb2.txt', $proposal->id . ',');
            }
        });
    }

    private function createThumbUsingLamda($proposal)
    {
        $fullPath = config('jp.BASE_PATH') . $proposal->file_path;
        try {
            $response = $this->request->get('create-tumbs', [
                'query' => [
                    'bucket' => $this->bucket,
                    'key' => $fullPath,
                    'type' => 'public',
                ]
            ]);
            // $response = json_decode($response->getBody());

            // if(sizeof($response)) {
            // 	// thumb path
            // 	$thumbPath = 'proposals/thumb/'. basename($proposal->file_path);

            // 	//save thumb
            //  $proposal->update(['thumb' => $thumbPath]);
            // }else{
            //  Log::warning('Error Proposal Not exists: '.$proposal->id);
            // }
        } catch (\Exception $e) {
            Log::warning('Error Proposal Thumb Creation: ' . $proposal->id);
        }
    }

    // create thumb
    private function createThumb($proposal)
    {
        $fullPath = config('jp.BASE_PATH') . $proposal->file_path;
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
        $thumbPath = 'proposals/thumb/' . basename($proposal->file_path);

        // upload thumb..
        FlySystem::put(config('jp.BASE_PATH') . $thumbPath, $thumb->encode()->getEncoded());

        //save thumb
        $proposal->update(['thumb' => $thumbPath]);
    }
}
