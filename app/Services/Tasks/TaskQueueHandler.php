<?php

namespace App\Services\Tasks;

use App\Models\Task;
use App\Models\TaskParticipant;
use App\Models\User;
use App\Repositories\NotificationsRepository;
use App\Services\Google\GoogleTasksService;
use App\Services\Messages\MessageService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Exception;
use Firebase;
use App\Models\TaskMessage;

class TaskQueueHandler
{

    public function __construct(
        NotificationsRepository $notificationRepo,
        MessageService $messageService,
        GoogleTasksService $taskService
    ) {

        $this->notificationRepo = $notificationRepo;
        $this->messageService = $messageService;
        $this->googleTaskService = $taskService;
    }

    public function sendEmail($queueJob, $task)
    {
        $task = $this->getTask($task['id']);
        if (!$task) {
            return $queueJob->delete();
        }

        $scope = setAuthAndScope($task->created_by);

        if (!$scope) {
            return $queueJob->delete();
        }

        $participants = implode(', ', $task->participants->pluck('full_name')->toArray());

        $users = $task->participants;

        foreach ($users as $user) {
            if (!$user) {
                continue;
            }

            $userId = $user->id;
            $emailData = [
                'user' => $user,
                'task' => $task,
                'participants' => $participants,
            ];

            Mail::send('emails.task_created_notification', $emailData, function ($message) use ($user) {
                $message->to($user->email, $user->full_name)->subject('New Task Assigned');
            });
        }

        $queueJob->delete();
    }

    public function sendMessage($queueJob, $task)
    {
        $task = $this->getTask($task['id']);
        if (!$task) {
            return $queueJob->delete();
        }

        $scope = setAuthAndScope($task->created_by);
        if (!$scope) {
            return $queueJob->delete();
        }

        try {
            $participants = implode(', ', $task->participants->pluck('full_name')->toArray());

            $messageSubject = null;

            $messageContent = <<<EOT
You have been assigned a new task.Here are the details:-\n
Title - $task->title.
EOT;
            if (($linkedJob = $task->job) && ($customer = $linkedJob->customer)) {
                $messageContent .= "\nLinked Job - " . $customer->full_name . " / " . $linkedJob->number;
            }

            $messageContent .= "\nParticipants - $participants";

            if ($task->notes) {
                $messageContent .= "\nNotes - " . $task->notes;
            }

            if ($task->due_date) {
                $dueDate = Carbon::parse($task->due_date)->format('m/d/Y');
                $messageContent .= "\nDue Date - " . $dueDate;
            }

            $users = arry_fu($task->participants->pluck('id')->toArray());
			$users[] = $task->created_by;
			$uniqueUser = arry_fu($users);
			sort($uniqueUser);

			if ($task->job) {
				$jobId  = $task->job->id;
				$thread = $this->messageService->getTaskParticipantThread(implode('_', $uniqueUser), $jobId);
			} else {
				$thread = $this->messageService->getTaskParticipantThread(implode('_', $uniqueUser));
			}

			$meta['thread_id'] = ine($thread, 'thread_id') ? $thread->thread_id : null;
			$meta['job_id'] = $task->job_id;
			$meta['customer_id'] = $task->customer_id;

			$message = $this->messageService->sendMessage($task->created_by,
				(array)$uniqueUser,
				$messageSubject,
				$messageContent,
				$meta
			);

			$task->message_id = $message->id;
			$task->thread_id = $message->thread_id;
			$task->save();
		} catch (Exception $e) {
			throw $e;
		}

        $queueJob->delete();
    }

    public function sendWebNotification($queueJob, $data)
    {
        try {
            $scope = setAuthAndScope($data['current_login_id']);
            if (!$scope) {
                return $queueJob->delete();
            }

            $task = $this->getTask($data['task_id']);
            if (!$task) {
                return $queueJob->delete();
            }

            $notifyUsers = $task->notifyUsers;
            if (empty($notifyUsers)) {
                return $queueJob->delete();
            }

            $subject = 'Task Completed';
            $sender = \Auth::user();
            $body = json_encode([
                'completed_by' => $sender->full_name,
            ]);

            foreach ($notifyUsers as $key => $recepient) {
                $this->notificationRepo->notification($sender, $recepient->id, $subject, $task, $body, $updateFirebase = true);
            }
        } catch (\Exception $e) {
            Log::error($e);
        }

        $queueJob->delete();
    }

    public function deleteTaskOnGoogle($queueJob, $data)
    {
        foreach ($data['old_users'] as $userId => $taskEventId) {
            if (!$taskEventId) {
                continue;
            }

            $token = $this->getAccesstoken($userId);
            if (!$token) {
                continue;
            }
            try {
                $this->googleTaskService->delete($token, $taskEventId, $userId);
            } catch (\Exception $e) {
                //handle exception
            }
        }

        $queueJob->delete();
    }

    public function createTaskOnGoogle($queueJob, $data)
    {
        $scope = setAuthAndScope($data['current_login_id']);

        if (!$scope) {
            return $queueJob->delete();
        }

        $task = $this->getTask($data['task_id']);

        if (!$task) {
            return $queueJob->delete();
        }

        try {
            foreach ($task->participants()->pluck('user_id')->toArray() as $userId) {
                $token = $this->getAccesstoken($userId);
                if (!$token) {
                    continue;
                }

                $googleTaskId = null;
                $participant = TaskParticipant::user($userId)->task($task->id)->first();
                if (!$participant) {
                    continue;
                }

                //manage google event
                try {
                    if ($participant->google_task_id) {
                        $this->googleTaskService->update($token, $participant->google_task_id, $task->title, $task->notes, $task->due_date, $userId);
                    } else {
                        $googleTaskId = $this->googleTaskService->insert($token, $task->title, $task->notes, $task->due_date, $userId);
                    }
                } catch (\Google_Service_Exception $e) {
                    if ($e->getCode() == 404) {
                        $googleTaskId = $this->googleTaskService->insert($token, $task->title, $task->notes, $task->due_date, $userId);
                    }
                } catch (\Exception $e) {
                }
                $participant->google_task_id = $googleTaskId;
                $participant->save();
            }
        } catch (\Exception $e) {
            Log::error($e);
        }

        $queueJob->delete();
    }

    public function googleEventMarkAsPendingOrComplete($queueJob, $data)
    {
        try {
            $scope = setAuthAndScope($data['current_login_id']);
            if (!$scope) {
                return $queueJob->delete();
            }

            $task = $this->getTask($data['task_id']);
            if (!$task) {
                return $queueJob->delete();
            }

            $completed = $task->completed;

            foreach ($task->participants()->pluck('user_id')->toArray() as $userId) {
                $accesstoken = $this->getAccesstoken($userId);
                if (!$accesstoken) {
                    continue;
                }

                $participant = TaskParticipant::user($userId)->task($task->id)->first();

                if (!isset($participant->google_task_id)) {
                    continue;
                }

                //for handling google error
                try {
                    if ($completed) {
                        $this->googleTaskService->markAsCompleted($accesstoken, $participant->google_task_id, $userId);
                    } else {
                        $this->googleTaskService->markAsPending($accesstoken, $participant->google_task_id, $userId);
                    }
                } catch (\Google_Service_Exception $e) {
                    if ($e->getCode() != 404) {
                        Log::error($e);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::info($e);
        }

        $queueJob->delete();
    }

    /**************** PRIVATE METHOD **********************/

    private function getTask($taskId)
    {
        return Task::find($taskId);
    }

    private function getAccesstoken($userId)
    {
        try {
            $user = User::findOrFail($userId);
            if (!$user) {
                return false;
            }

            $calendarClient = $user->googleCalendarClient;
            if (!$calendarClient) {
                return false;
            }

            return $calendarClient->token;
        } catch (\Exception $e) {
            return false;
        }
    }
}
