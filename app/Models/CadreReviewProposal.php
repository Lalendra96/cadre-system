<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CadreReviewProposal extends Model
{
    use SoftDeletes;
    protected $table = 'cadre_review_proposals';
    protected $fillable = ['title','proposal_year','status','justification','created_by',
        'submitted_by','submitted_at','approved_by','approved_at','approval_remarks','moh_reference'];
    protected $casts = ['submitted_at'=>'datetime','approved_at'=>'datetime'];

    public const STATUSES = ['draft','submitted','director_approved','moh_submitted','approved','rejected','cancelled'];
    public const STATUS_LABELS = [
        'draft'=>'Draft','submitted'=>'Submitted','director_approved'=>'Director Approved',
        'moh_submitted'=>'Submitted to MoH','approved'=>'MoH Approved','rejected'=>'Rejected',
        'cancelled'=>'Cancelled',
    ];

    public function items()   { return $this->hasMany(CadreReviewItem::class, 'proposal_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function approver(){ return $this->belongsTo(User::class, 'approved_by'); }

    public function scopeForYear($q, int $year) { return $q->where('proposal_year', $year); }
}
