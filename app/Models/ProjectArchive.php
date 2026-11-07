<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectArchive extends Model
{
    protected $fillable = [
        'project_title', 'semester', 'team_members_summary',
        'tech_stack', 'abstract', 'github_repo_url',
        'keywords', 'featured_award'
    ];
}
