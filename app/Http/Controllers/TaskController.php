<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

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
        return response()->json($task->fresh());
    }

    public function destroy(Task $task)
    {
        $task->delete();
        return response()->json(['message' => 'Deleted']);
    }
}