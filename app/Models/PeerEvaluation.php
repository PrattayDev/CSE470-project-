<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeerEvaluation extends Model
{
    protected $fillable = [
        'team_id', 'evaluator_student_id', 'evaluatee_student_id',
        'coding_contribution_score', 'sprint_reliability_score',
        'documentation_score', 'justification'
    ];

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_student_id');
    }

    public function evaluatee()
    {
        return $this->belongsTo(User::class, 'evaluatee_student_id');
    }
}
