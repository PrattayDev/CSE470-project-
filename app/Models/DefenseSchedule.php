<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefenseSchedule extends Model
{
    protected $fillable = [
        'team_id', 'defense_date', 'start_time', 'end_time',
        'room_number', 'panel_chair_id', 'external_examiner_name', 'status'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function panelChair()
    {
        return $this->belongsTo(User::class, 'panel_chair_id');
    }
}
