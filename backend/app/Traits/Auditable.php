<?php

namespace App\Traits;

use App\Models\AuditLog;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    /**
     * Boot the trait - Laravel will automatically call boot{TraitName}()
     */
    protected static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            static::writeAuditLog('created', $model);
        });

        static::updated(function (Model $model) {
            static::writeAuditLog('updated', $model);
        });

        static::deleted(function (Model $model) {
            $action = method_exists($model, 'isForceDeleting') && $model->isForceDeleting()
                ? 'force_deleted'
                : 'deleted';
            static::writeAuditLog($action, $model);
        });

        static::restored(function (Model $model) {
            static::writeAuditLog('restored', $model);
        });
    }

    protected static function writeAuditLog(string $action, Model $model): void
    {
        $ignored = ['created_at', 'updated_at', 'deleted_at'];
        $oldValues = null;
        $newValues = null;

        if ($action === 'updated') {
            $dirty = array_diff_key($model->getDirty(), array_flip($ignored));
            if (empty($dirty)) {
                return;
            }

            $oldValues = array_intersect_key($model->getOriginal(), $dirty);
            $newValues = $dirty;
        } elseif ($action === 'created' || $action === 'restored') {
            $newValues = array_diff_key($model->attributesToArray(), array_flip($ignored));
        } elseif (in_array($action, ['deleted', 'force_deleted'], true)) {
            $oldValues = array_diff_key($model->attributesToArray(), array_flip($ignored));
        }

        $request = app()->runningInConsole() ? null : request();

        AuditLog::create([
            'user_id' => Auth::id(),
            'institution_id' => static::resolveInstitutionId($model),
            'action' => $action,
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'url' => $request?->fullUrl(),
            'method' => $request?->method(),
        ]);
    }

    protected static function resolveInstitutionId(Model $model): ?int
    {
        if (array_key_exists('institution_id', $model->getAttributes())) {
            return $model->getAttribute('institution_id');
        }

        if ($model instanceof Institution) {
            return $model->getKey();
        }

        $user = Auth::user();
        return $user?->institution_id;
    }
}
