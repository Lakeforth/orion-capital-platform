<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageService
{
    public function uploadFileOld($file,$path): string
    {
        $filepath = $file->store($path,'public');

        return asset('storage/'.$filepath);

    }

    public function uploadFile(UploadedFile $file, $path = 'files'): string
    {
        $path = Storage::disk('s3')->put($path, $file, 'public');
        return Storage::disk('s3')->url($path);
    }

    /**
     * Store a file nobody may read without going through the application.
     *
     * Returns the object KEY, not a URL, and uses a private ACL. Used for KYC
     * documents: uploadFile() above puts objects up public and saves the public
     * URL, which is how every passport on the platform came to be one bucket
     * setting away from being world-readable. Serve these through
     * App\Support\Kyc::stream() instead.
     */
    public function uploadPrivateFile(UploadedFile $file, string $path = 'files'): string
    {
        return Storage::disk('s3')->put($path, $file, 'private');
    }


    public function uploadBase64File(string $base64Image, string $path): string
    {
        // Check if it's a valid base64 string
        if (!preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
            throw new \Exception('Invalid base64 image format');
        }

        // Get file extension (jpg, png, etc)
        $extension = strtolower($type[1]);

        // Remove the "data:image/png;base64," part
        $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);

        // Decode base64 string
        $imageData = base64_decode($base64Image);

        if ($imageData === false) {
            throw new \Exception('Failed to decode base64 image');
        }

        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_');

        if ($tempFile === false) {
            throw new \Exception('Failed to create temporary file');
        }

        try {
            // Write image data to temporary file
            if (file_put_contents($tempFile, $imageData) === false) {
                throw new \Exception('Failed to write image data to temporary file');
            }

            // Create unique filename
            $filename = Str::uuid() . '.' . $extension;

            // Create an UploadedFile instance from the temporary file
            $uploadedFile = new UploadedFile(
                $tempFile,
                $filename,
                'image/' . $extension,
                null,
                true // test mode to avoid some validations
            );

            // Use the existing uploadFile function for consistency
            return $this->uploadFile($uploadedFile, $path);

        } finally {
            // Clean up the temporary file
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }


    public function deleteFile($path): void
    {
        if (Storage::disk('s3')->exists($path)) {
            Storage::disk('s3')->delete($path);
        }
    }
}
