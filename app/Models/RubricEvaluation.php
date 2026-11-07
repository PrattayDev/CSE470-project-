<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RubricEvaluation extends Model
{
    protected $fillable = [
        'team_id', 'student_id', 'evaluated_by',
        'srs_and_sprint_marks', 'mvc_implementation_marks',
        'individual_viva_marks', 'total_marks', 'viva_remarks', 'is_finalized'
    ];

    protected $casts = [
        'is_finalized' => 'boolean',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }
}
