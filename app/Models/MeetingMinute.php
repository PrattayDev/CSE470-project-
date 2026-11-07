<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingMinute extends Model
{
    protected $fillable = [
        'slot_id', 'team_id', 'discussion_summary', 'action_items',
        'action_status', 'assigned_to', 'due_date', 'recorded_by'
    ];

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function slot()
    {
        return $this->belongsTo(ConsultationSlot::class, 'slot_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
