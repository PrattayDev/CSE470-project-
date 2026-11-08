<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    public $timestamps = false;
    protected $fillable = ['team_id', 'student_id', 'assigned_module', 'joined_at'];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
