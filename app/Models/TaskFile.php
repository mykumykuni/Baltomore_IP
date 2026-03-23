<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TaskFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'original_name',
        'file_path',
        'mime_type',
        'size',
    ];

    protected static function booted(): void
    {
        static::deleting(function (TaskFile $taskFile): void {
            Storage::disk('local')->delete($taskFile->file_path);
        });
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
