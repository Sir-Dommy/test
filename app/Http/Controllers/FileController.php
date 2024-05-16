<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FileController extends Controller
{
    public function upload(Request $request)
    {
        try{
            // return $request;
            $request->validate([
                'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048', // Example validation rules
            ]);
    
            $file = $request->file('file');
            $fileName = time().'_'.$file->getClientOriginalName();
            $filePath = $file->storeAs('public', $fileName);
    
            return response()->json(['file_path' => $filePath]);
        }
        catch(\Exception $e){
            return response(["error"=>$e->getMessage()], 500);
        }
        
    }

    public function download($filename)
    {
        $filePath = storage_path('app/uploads/'.$filename);

        if (file_exists($filePath)) {
            return response()->download($filePath);
        }

        return response()->json(['error' => 'File not found'], 404);
    }
}

