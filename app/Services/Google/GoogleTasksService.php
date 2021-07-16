<?php

namespace App\Services\Google;

use App\Traits\HandleGoogleExpireToken;
use Carbon\Carbon;
use Google_Service_Tasks;

class GoogleTasksService
{

    use HandleGoogleExpireToken;

    protected $client;

    public function __construct()
    {
        $this->client = new \Google_Client();
        $this->client->setClientId(\config('google.client_id'));
        $this->client->setClientSecret(\config('google.client_secret'));
        $this->client->setState('offline');
    }

    public function getTasks($accessToken, $userId = null)
    {
        $list = [];
        try {
            $service = $this->taskService($accessToken);
            $tasks = $service->tasks->listTasks('@default');
            foreach ($tasks->getItems() as $task) {
                $list[] = $task->getID();
            }
        } catch (\Exception $e) {
            if ($this->isTokenExpireException($e)) {
                $this->fireTokenExpireEvent($accessToken);
            } else {
                throw $e;
            }
        }

        return $list;
    }

    public function insert($accessToken, $title, $notes = null, $dueDate = null, $userId = null)
    {
        $googleTaskId = null;
        try {
            $service = $this->taskService($accessToken);
            $task = new \Google_Service_Tasks_Task();
            $task->setTitle($title);
            $task->setNotes($notes);
            if ($dueDate) {
                if (!$dueDate instanceof Carbon) {
                    $dueDate = new Carbon($dueDate);
                }
                $task->setDue($dueDate->toAtomString());
            }
            $result = $service->tasks->insert('@default', $task);

            $googleTaskId = $result->getId();
        } catch (\Exception $e) {
            if ($this->isTokenExpireException($e)) {
                $this->fireTokenExpireEvent($accessToken);
            } else {
                throw $e;
            }
        }

        return $googleTaskId;
    }

    public function update($accessToken, $taskId, $title, $notes = null, $dueDate = null, $userId = null)
    {

        $googleTaskId = null;
        try {
            $service = $this->taskService($accessToken);
            $task = $service->tasks->get('@default', $taskId);
            $task->setTitle($title);
            $task->setNotes($notes);
            if ($dueDate) {
                if (!$dueDate instanceof Carbon) {
                    $dueDate = new Carbon($dueDate);
                }
                $task->setDue($dueDate->toAtomString());
            }
            $result = $service->tasks->update('@default', $taskId, $task);
            $googleTaskId = $result->getId();
        } catch (\Exception $e) {
            if ($this->isTokenExpireException($e)) {
                $this->fireTokenExpireEvent($accessToken);
            } else {
                throw $e;
            }
        }

        return $googleTaskId;
    }

    public function markAsCompleted($accessToken, $taskId, $userId = null)
    {
        try {
            $service = $this->taskService($accessToken);
            $task = $service->tasks->get('@default', $taskId);
            $task->setStatus('completed');
            $result = $service->tasks->update('@default', $taskId, $task);
        } catch (\Exception $e) {
            if ($this->isTokenExpireException($e)) {
                $this->fireTokenExpireEvent($accessToken);
            } else {
                throw $e;
            }
        }

        return true;
    }

    public function markAsPending($accessToken, $taskId, $userId = null)
    {
        try {
            $service = $this->taskService($accessToken);
            $task = $service->tasks->get('@default', $taskId);
            $task->setStatus('needsAction');
            $task->setCompleted(null);
            $result = $service->tasks->update('@default', $taskId, $task);
        } catch (\Exception $e) {
            if ($this->isTokenExpireException($e)) {
                $this->fireTokenExpireEvent($accessToken);
            } else {
                throw $e;
            }
        }

        return true;
    }

    public function delete($accessToken, $taskId, $userId = null)
    {
        try {
            $service = $this->taskService($accessToken);
            $service->tasks->delete('@default', $taskId);
        } catch (\Exception $e) {
            if ($this->isTokenExpireException($e)) {
                $this->fireTokenExpireEvent($accessToken);
            } else {
                throw $e;
            }
        }

        return true;
    }

    /******************* Private section **********************/

    private function taskService($accessToken)
    {
        $this->client->setAccessToken($accessToken);
        $service = new Google_Service_Tasks($this->client);

        return $service;
    }
}
