<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplianceDisclosure extends Model
{
    protected $fillable = [
        'team_id', 'ai_tools_used', 'ai_usage_purpose',
        'influenced_modules', 'third_party_packages',
        'student_ownership_verified', 'supervisor_verified'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}
