<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class RosterAuditService
{
    public static function log(string $action, string $type, ?int $id, string $description, $old=null, $new=null): void
    {
        DB::table('audit_logs')->insert([
            'user_id'=>auth()->id(), 'action'=>$action, 'auditable_type'=>$type, 'auditable_id'=>$id,
            'description'=>$description,
            'old_values'=>$old ? json_encode($old) : null,
            'new_values'=>$new ? json_encode($new) : null,
            'ip_address'=>request()->ip(), 'created_at'=>now(),
        ]);
    }
}
