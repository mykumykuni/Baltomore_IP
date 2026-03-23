<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TaskFileController extends Controller
{
    public function index(Task $task)
    {
        return response()->json($task->files()->latest()->get());
    }

    public function store(Request $request, Task $task)
    {
        $validated = $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $uploadedFile = $validated['file'];
        $extension = $uploadedFile->getClientOriginalExtension();
        $safeExtension = $extension === '' ? '' : '.'.$extension;
        $storedName = Str::uuid()->toString().$safeExtension;
        $path = $uploadedFile->storeAs("tasks/{$task->id}", $storedName, 'local');

        $taskFile = $task->files()->create([
            'original_name' => $uploadedFile->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $uploadedFile->getClientMimeType(),
            'size' => $uploadedFile->getSize(),
        ]);

        return response()->json($taskFile, 201);
    }

    public function download(Task $task, TaskFile $taskFile)
    {
        $taskFile = $task->files()->whereKey($taskFile->id)->firstOrFail();

        return response()->download(
            Storage::disk('local')->path($taskFile->file_path),
            $taskFile->original_name
        );
    }

    public function destroy(Task $task, TaskFile $taskFile)
    {
        $taskFile = $task->files()->whereKey($taskFile->id)->firstOrFail();
        $taskFile->delete();

        return response()->json(['message' => 'File deleted']);
    }
}
