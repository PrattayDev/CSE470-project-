<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultationSlot extends Model
{
    protected $fillable = [
        'supervisor_id', 'booked_by_team_id', 'slot_date', 'start_time',
        'end_time', 'room_or_meet_link', 'agenda', 'status'
    ];

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function bookedTeam()
    {
        return $this->belongsTo(Team::class, 'booked_by_team_id');
    }
}
