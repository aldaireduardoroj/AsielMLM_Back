<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportUserNew extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'userId',
        'countChildren',
        'codeUsers',
        'dateSync',
    ];
}
