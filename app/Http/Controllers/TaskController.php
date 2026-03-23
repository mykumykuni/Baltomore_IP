<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskActivityNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class TaskController extends Controller {
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => 'sometimes|string',
            'min_age' => 'sometimes|integer|min:0',
            'max_age' => 'sometimes|integer|min:0',
        ]);

        $tasks = Task::query()
            ->with(['user:id,name,email', 'files:id,task_id,original_name,mime_type,size,created_at'])
            ->search($validated['search'] ?? null)
            ->when(isset($validated['min_age']), fn ($query) => $query->where('age', '>=', $validated['min_age']))
            ->when(isset($validated['max_age']), fn ($query) => $query->where('age', '<=', $validated['max_age']))
            ->latest()
            ->get();

        return response()->json($tasks);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string',
            'age'       => 'required|integer',
            'birthdate' => 'required|date',
            'email'     => 'required|email|unique:tasks,email',
        ]);

        $task = $request->user()->tasks()->create($validated);

        $this->notifyTaskActivity($request, $task, 'created');

        return response()->json($task->load(['user:id,name,email', 'files:id,task_id,original_name,mime_type,size,created_at']), 201);
    }

    public function show(Task $task)
    {
        return response()->json($task->load(['user:id,name,email', 'files:id,task_id,original_name,mime_type,size,created_at']));
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'name'      => 'sometimes|string',
            'age'       => 'sometimes|integer',
            'birthdate' => 'sometimes|date',
            'email'     => 'sometimes|email|unique:tasks,email,' . $task->id,
        ]);

        $task->fill($validated);
        $task->save();

        $this->notifyTaskActivity($request, $task->fresh(), 'updated');

        return response()->json($task->fresh()->load(['user:id,name,email', 'files:id,task_id,original_name,mime_type,size,created_at']));
    }

    public function destroy(Request $request, Task $task)
    {
        $this->notifyTaskActivity($request, $task, 'deleted');

        $task->delete();
        return response()->json(['message' => 'Deleted']);
    }

    private function notifyTaskActivity(Request $request, Task $task, string $action): void
    {
        $actor = $request->user();

        $recipients = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'admin'))
            ->get();

        if ($actor !== null && ! $recipients->contains('id', $actor->id)) {
            $recipients->push($actor);
        }

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new TaskActivityNotification($task, $action, $actor));
        }
    }
}