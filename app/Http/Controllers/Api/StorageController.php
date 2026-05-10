<?php

namespace App\Http\Controllers\Api;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\BaseController as BaseController;
use App\Models\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class StorageController extends BaseController{
    public function register( Request $request )
    {
        try {

            $validator = Validator::make( $request->all() , [
                'file' => 'required|file|mimes:png,jpg,jpeg|max:2048',
                'folder'    => 'required',
            ]);

            if ($validator->fails()) return $this->sendError('Error de validacion.', $validator->errors(), 422);

            DB::beginTransaction();

            $dataBody = (object) $request->all();

            $user_id = Auth::id();

            $fileId = 0;

            if($request->hasfile('file'))
            {
                $filePath = Storage::disk('public')->put('files/'.$dataBody->folder, $request->file('file'));
                $fileModel = File::create(array(
                    'path' => $filePath,
                    'name' => $request->file('file')->getClientOriginalName(),
                    'extension' => $request->file('file')->getClientOriginalExtension(),
                    'size' => $request->file('file')->getSize()
                ));
                $fileId = $fileModel->id;
            }


            DB::commit();

            return $this->sendResponse( $fileId, 'Creado');


        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError( $e->getMessage() );
        }
    }
}
