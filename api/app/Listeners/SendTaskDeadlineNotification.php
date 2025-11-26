<?php

namespace App\Listeners;

use App\Events\TaskUpdated;
use App\Notifications\TaskDeadlinePassedNotification;

class SendTaskDeadlineNotification
{
    /**
     * Handle the event.
     *
     * @param  \App\Events\TaskUpdated  $event
     * @return void
     */
    public function handle(TaskUpdated $event): void
    {
        $task = $event->task;
        if ($task->deadline && $task->deadline->isPast()) {
            $task->user->notify(new TaskDeadlinePassedNotification($task));
        }
    }
}





