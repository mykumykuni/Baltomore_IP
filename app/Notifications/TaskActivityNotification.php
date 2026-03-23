<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskActivityNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Task $task,
        private readonly string $action,
        private readonly ?User $actor = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $actorName = $this->actor?->name ?? 'System';

        return [
            'task_id' => $this->task->id,
            'task_name' => $this->task->name,
            'task_email' => $this->task->email,
            'action' => $this->action,
            'actor_id' => $this->actor?->id,
            'actor_name' => $actorName,
            'message' => sprintf('Task "%s" was %s by %s.', $this->task->name, $this->action, $actorName),
        ];
    }
}
