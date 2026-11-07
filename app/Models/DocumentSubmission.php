<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSubmission extends Model
{
    protected $fillable = [
        'team_id', 'submitted_by', 'document_type', 'version_tag',
        'file_path_or_drive_url', 'changelog_notes',
        'faculty_review_status', 'faculty_comments'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
