<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClearanceCertificate extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'team_id', 'certificate_serial', 'lab_dues_cleared',
        'final_code_submitted', 'srs_approved', 'issued_by', 'issued_at'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
