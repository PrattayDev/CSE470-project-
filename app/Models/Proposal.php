<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{
    protected $fillable = [
        'team_id', 'supervisor_id', 'title', 'problem_statement',
        'proposed_features', 'tech_stack', 'similarity_score',
        'matched_archive_title', 'status', 'supervisor_feedback'
    ];

    protected $casts = [
        'proposed_features' => 'array',
        'similarity_score' => 'decimal:2',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
}
