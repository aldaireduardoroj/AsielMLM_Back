<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoImageStory extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'preview_id',
        'user_id',
        'name',
        'description',
        'link',
        'is_story',
        'state',
    ];


    public function file()
    {
        return $this->hasOne(File::class , 'id' , 'file_id');
    }

    public function preview()
    {
        return $this->hasOne(File::class , 'id' , 'preview_id');
    }
}
