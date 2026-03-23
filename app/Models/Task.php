<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model {
    use HasFactory;

    protected $fillable = ['name', 'age', 'birthdate', 'email', 'user_id'];

    protected $casts = [
        'birthdate' => 'date:Y-m-d',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if ($search === null || trim($search) === '') {
            return $query;
        }

        $term = trim($search);

        return $query->where(function (Builder $innerQuery) use ($term): void {
            $innerQuery
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%");
        });
    }
}