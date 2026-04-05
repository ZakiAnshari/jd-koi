<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    // public static function bootLogsActivity()
    // {
    //     static::created(function (Model $model) {
    //         $model->logActivity('created');
    //     });
    //
    //     static::updated(function (Model $model) {
    //         $model->logActivity('updated');
    //     });
    //
    //     static::deleted(function (Model $model) {
    //         $model->logActivity('deleted');
    //     });
    // }

    // NOTE: Automatic activity logging has been disabled in this trait.
    // Activity logging is now handled directly in controllers via ActivityLog::log()
    // to avoid duplicate entries in activity_logs table.

    protected function logActivity(string $action)
    {
        $userId = Auth::id();
        if (!$userId) {
            return; // Skip if no authenticated user
        }

        $module = $this->logModule ?? class_basename($this);
        $except = $this->logExcept ?? [];

        $description = $this->getActivityDescription($action);
        $subjectType = get_class($this);
        $subjectId = $this->getKey();

        $oldValues = null;
        $newValues = null;

        if ($action === 'created') {
            $newValues = $this->getAttributesForLog($this->getAttributes());
        } elseif ($action === 'updated') {
            $dirty = $this->getDirty();
            $oldValues = $this->getAttributesForLog(array_intersect_key($this->getOriginal(), $dirty));
            $newValues = $this->getAttributesForLog($dirty);
        } elseif ($action === 'deleted') {
            $oldValues = $this->getAttributesForLog($this->getOriginal());
        }

        ActivityLog::create([
            'user_id' => $userId,
            'module' => $module,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    protected function getActivityDescription(string $action): string
    {
        $modelName = $this->logModule ?? class_basename($this);
        $id = $this->getKey();
        $itemName = $this->getActivityItemName();

        if ($itemName) {
            switch ($action) {
                case 'created':
                    return "Menambahkan data {$modelName} \"{$itemName}\" (ID: {$id})";
                case 'updated':
                    return "Mengubah data {$modelName} \"{$itemName}\" (ID: {$id})";
                case 'deleted':
                    return "Menghapus data {$modelName} \"{$itemName}\" (ID: {$id})";
                default:
                    return "Aktivitas {$action} pada {$modelName} \"{$itemName}\" (ID: {$id})";
            }
        }

        switch ($action) {
            case 'created':
                return "Menambahkan data {$modelName} baru";
            case 'updated':
                return "Mengubah data {$modelName} #{$id}";
            case 'deleted':
                return "Menghapus data {$modelName} #{$id}";
            default:
                return "Aktivitas {$action} pada {$modelName} #{$id}";
        }
    }

    protected function getActivityItemName(): ?string
    {
        // Override this method in models to provide a meaningful name
        // For example: return $this->name; or return $this->code;
        return null;
    }

    protected function getAttributesForLog(array $attributes): ?array
    {
        $except = $this->logExcept ?? [];
        $filtered = array_diff_key($attributes, array_flip($except));
        return empty($filtered) ? null : $filtered;
    }
}