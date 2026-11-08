<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'student_or_faculty_id',
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = ['password'];
}
