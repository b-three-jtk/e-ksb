<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    /**
     * Boot the trait to hook into model events.
     */
    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            $model->logAudit('created', [], $model->getAttributes());
        });

        static::updated(function (Model $model) {
            $dirty = $model->getDirty();
            $original = [];
            
            foreach (array_keys($dirty) as $key) {
                $original[$key] = $model->getOriginal($key);
            }
            
            $model->logAudit('updated', $original, $dirty);
        });

        static::deleted(function (Model $model) {
            $model->logAudit('deleted', $model->getAttributes(), []);
        });
    }

    /**
     * Insert the audit log entry.
     */
    protected function logAudit(string $event, array $oldValues, array $newValues): void
    {
        $exclude = ['created_at', 'updated_at', 'deleted_at', 'password', 'remember_token'];
        
        if (property_exists($this, 'auditExclude')) {
            $exclude = array_merge($exclude, $this->auditExclude);
        }

        $oldValues = array_diff_key($oldValues, array_flip($exclude));
        $newValues = array_diff_key($newValues, array_flip($exclude));

        if ($event === 'updated' && empty($newValues)) {
            return;
        }

        AuditLog::create([
            'user_id' => auth()->check() ? auth()->id() : null,
            'auditable_type' => get_class($this),
            'auditable_id' => $this->getKey(),
            'event' => $event,
            'old_values' => empty($oldValues) ? null : $oldValues,
            'new_values' => empty($newValues) ? null : $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
