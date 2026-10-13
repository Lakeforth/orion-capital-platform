<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class FileUploadService
{

    public function upload($file, $path): string
    {
        $fullPath = storage_path('app/public/' . $path);

        if (!File::exists($fullPath)) {
            File::makeDirectory($fullPath, 0755, true);
        }

        $filename = time() . '_' . $file->getClientOriginalName();
        $file->storeAs('public/'.$path, $filename);
        return asset('storage/'.$path.'/'.$filename);
    }
}
