<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskParticipant extends Model
{

    protected $fillable = ['user_id', 'task_id', 'google_task_id'];
    public $timestamps = false;

    public function scopeUser($query, $userId)
    {
        $query->where('user_id', $userId);
    }

    public function scopeTask($query, $taskId)
    {
        $query->where('task_id', $taskId);
    }
}
