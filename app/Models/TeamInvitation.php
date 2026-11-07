<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamInvitation extends Model
{
    protected $fillable = ['team_id', 'invited_student_id', 'status', 'message'];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function invitedStudent()
    {
        return $this->belongsTo(User::class, 'invited_student_id');
    }
}
