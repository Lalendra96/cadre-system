<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CadreReviewItem extends Model
{
    protected $table = 'cadre_review_items';
    protected $fillable = ['proposal_id','position_id','current_approved','proposed_amount','change_type','justification'];
    protected $casts = ['current_approved'=>'integer','proposed_amount'=>'integer'];

    public function proposal()  { return $this->belongsTo(CadreReviewProposal::class, 'proposal_id'); }
    public function position()  { return $this->belongsTo(Position::class, 'position_id'); }

    public function getNetChangeAttribute(): int { return $this->proposed_amount - $this->current_approved; }
}
