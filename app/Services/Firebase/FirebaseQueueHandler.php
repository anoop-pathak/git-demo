<?php

namespace App\Services\Firebase;

use Firebase\FirebaseLib;
use Illuminate\Support\Facades\Log;

class FirebaseQueueHandler
{
    /**
     * Queue
     * @param  Queue Object $job  Queue object data
     * @param  Array $data Firebase Data
     * @return Void
     */
    public function fire($job, $data)
    {
        try {
            $firebase = new FirebaseLib(config('firebase.url'), config('firebase.database_secret'));
            $firebase->update($data['key'], $data['data']);
            $job->delete();
        } catch (\Exception $e) {
            $message = $e->getMessage() . ' in file ' . $e->getFile() . ' on line number ' . $e->getLine();
            Log::error('Firebase: ' . $message);
        }
    }
}
