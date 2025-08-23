<?php
namespace App\Services\Core;

use App\Models\File;
use Illuminate\Support\Str;

class FileUpload{

    public function __construct()
    {

    }

    public function upload( $input_image , $path)
    {
        $fileId = null;
        $fileModel = new File;

        $extension = $input_image->extension();
        $fileName = time().'_'.Str::random(12).'.'.$extension;

        $size = $input_image->getSize();

        $filePath = $input_image->storeAs( $path , $fileName, 'public');

        $fileModel->path = $filePath;
        $fileModel->name = $fileName;
        $fileModel->extension = $extension;
        $fileModel->size = $size;

        $fileModel->save();

        return $fileModel->id;

        return $fileId;
    }
}
