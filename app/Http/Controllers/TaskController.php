<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller {
    public function index()
{
    // This fetches everything from the tasks table
    return response()->json(\App\Models\Task::all());
}

public function store(Request $request)
{
    $validated = $request->validate([
        'name'      => 'required|string',
        'age'       => 'required|integer',
        'birthdate' => 'required|date',
        'email'     => 'required|email|unique:tasks,email',
    ]);

    $task = \App\Models\Task::create($validated);
    return response()->json($task, 201);
}

    public function update(Request $request, Task $task) {
        $task->update($request->all());
        return response()->json($task);
    }

    public function destroy(Task $task) {
        $task->delete();
        return response()->json(['message' => 'Deleted']);
    }
}