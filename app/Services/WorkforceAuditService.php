<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WorkforceAuditService
{
    public function log(string $action, string $type, ?int $id, string $description, array $old = [], array $new = []): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => Auth::id(),
            'action' => substr($action, 0, 20),
            'auditable_type' => substr($type, 0, 150),
            'auditable_id' => $id,
            'description' => substr($description, 0, 255),
            'old_values' => $old ? json_encode($old) : null,
            'new_values' => $new ? json_encode($new) : null,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    public function model(string $action, Model $model, string $description, array $old = []): void
    {
        $this->log($action, $model::class, (int) $model->getKey(), $description, $old, $model->getAttributes());
    }
}
