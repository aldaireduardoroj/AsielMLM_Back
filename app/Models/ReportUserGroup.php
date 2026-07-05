<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportUserGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'userId',
        'maxGroupUserId',
        'maxGroupPoint',
        'minGroupUserId',
        'minGroupPoint',
        'dateSync',
    ];
}