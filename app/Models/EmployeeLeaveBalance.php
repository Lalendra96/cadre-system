<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EmployeeLeaveBalance extends Model { protected $fillable=['employee_id','leave_type_id','year','opening_balance','accrued','used','adjusted']; protected $casts=['opening_balance'=>'decimal:2','accrued'=>'decimal:2','used'=>'decimal:2','adjusted'=>'decimal:2']; public function employee(){return $this->belongsTo(Employee::class);} public function leaveType(){return $this->belongsTo(LeaveType::class);} public function available(): float { return (float)$this->opening_balance+(float)$this->accrued+(float)$this->adjusted-(float)$this->used; } }
