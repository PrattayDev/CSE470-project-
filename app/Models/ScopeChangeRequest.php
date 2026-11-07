<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScopeChangeRequest extends Model
{
    protected $fillable = [
        'team_id', 'requested_by', 'request_type',
        'current_scope_detail', 'proposed_change_detail',
        'reason_justification', 'status', 'coordinator_decision_note'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
