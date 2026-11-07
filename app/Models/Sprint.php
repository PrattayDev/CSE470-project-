<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sprint extends Model
{
    protected $fillable = [
        'team_id', 'sprint_number', 'sprint_goal', 'start_date', 'end_date',
        'attainment_summary', 'blockers_reported', 'github_pr_or_commit_url',
        'demo_video_url', 'status', 'supervisor_remarks', 'attainment_percentage'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function tasks()
    {
        return $this->hasMany(SprintTask::class, 'sprint_id');
    }
}
