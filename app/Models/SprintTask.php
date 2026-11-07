<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SprintTask extends Model
{
    protected $fillable = [
        'sprint_id', 'assigned_to', 'feature_number', 'feature_title',
        'task_description', 'git_commit_hash', 'status'
    ];

    public function sprint()
    {
        return $this->belongsTo(Sprint::class, 'sprint_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
