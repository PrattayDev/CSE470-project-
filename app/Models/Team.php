<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = [
        'group_name', 'section', 'leader_id', 'supervisor_id',
        'status', 'preferred_stack', 'domain_interest'
    ];

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function members()
    {
        return $this->hasMany(TeamMember::class, 'team_id');
    }

    public function invitations()
    {
        return $this->hasMany(TeamInvitation::class, 'team_id');
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class, 'team_id');
    }

    public function documents()
    {
        return $this->hasMany(DocumentSubmission::class, 'team_id');
    }
}
