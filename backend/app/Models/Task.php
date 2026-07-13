<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'due_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $query->when($status, fn (Builder $q) => $q->where('status', $status));
    }

    public function scopePriority(Builder $query, ?string $priority): Builder
    {
        return $query->when($priority, fn (Builder $q) => $q->where('priority', $priority));
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, fn (Builder $q) => $q->where('title', 'like', "%{$search}%"));
    }
}
