<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ExportAuditLog extends Model
{
    protected $table      = 'export_audit_logs';
    public    $timestamps = false;
    protected $fillable   = ['user_id','export_type','resource_type','resource_id',
        'filename','file_size_bytes','ip_address','user_agent','exported_at'];
    protected $casts      = ['exported_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class, 'user_id'); }
}
