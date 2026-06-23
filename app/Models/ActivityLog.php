<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'module',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeModule($query, $module)
    {
        return $query->where('module', $module);
    }

    public function scopeAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    public static function log(string $action, string $description, string $module = 'general', array $options = []): self
    {
        $userId = Auth::id();
        return self::create([
            'user_id'      => $userId,
            'action'       => $action,
            'description'  => $description,
            'module'       => $module,
            'subject_type' => $options['subject_type'] ?? null,
            'subject_id'   => $options['subject_id'] ?? null,
            'old_values'   => $options['old_values'] ?? null,
            'new_values'   => $options['new_values'] ?? null,
            'ip_address'   => $options['ip_address'] ?? request()->ip(),
            'user_agent'   => $options['user_agent'] ?? request()->userAgent(),
        ]);
    }
}
