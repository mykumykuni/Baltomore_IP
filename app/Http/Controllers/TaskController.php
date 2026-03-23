<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskActivityNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class TaskController extends Controller {
    public function index()
    {
        return response()->json(Task::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string',
            'age'       => 'required|integer',
            'birthdate' => 'required|date',
            'email'     => 'required|email|unique:tasks,email',
        ]);

        $task = Task::create($validated);

        $this->notifyTaskActivity($request, $task, 'created');

        return response()->json($task, 201);
    }

    public function show(Task $task)
    {
        return response()->json($task);
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'name'      => 'sometimes|string',
            'age'       => 'sometimes|integer',
            'birthdate' => 'sometimes|date',
            'email'     => 'sometimes|email|unique:tasks,email,' . $task->id,
        ]);

        $task->update($validated);

        $this->notifyTaskActivity($request, $task->fresh(), 'updated');

        return response()->json($task->fresh());
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