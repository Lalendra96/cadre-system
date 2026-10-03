<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UtilityBillPayment extends Model
{
    protected $fillable = ['utility_bill_id','payment_date','amount','payment_method','payment_reference','voucher_no','remarks','recorded_by'];
    protected $casts = ['payment_date'=>'date','amount'=>'decimal:2'];
    public function bill() { return $this->belongsTo(UtilityBill::class, 'utility_bill_id'); }
    public function recorder() { return $this->belongsTo(User::class, 'recorded_by'); }
}
