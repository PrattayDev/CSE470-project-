<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentRequisition extends Model
{
    protected $fillable = [
        'team_id', 'equipment_id', 'requested_by', 'quantity',
        'issue_date', 'due_date', 'returned_date',
        'calculated_fine_bdt', 'fine_paid_amount', 'fine_paid_at', 'status'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function equipment()
    {
        return $this->belongsTo(LabEquipment::class, 'equipment_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
