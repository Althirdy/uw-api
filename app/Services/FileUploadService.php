<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class FileUploadService
{
    /**
     * Upload a single file.
     */
    public function uploadSingle($file, $directory = 'uploads')
    {
        // Use default disk
        $disk = config('filesystems.default');

        // Safety Fallback: If configured for S3 but no bucket is defined, fallback to public
        if ($disk === 's3' && empty(config('filesystems.disks.s3.bucket'))) {
            $disk = 'public';
        }

        // If default is 'local', force 'public' for accessibility
        if ($disk === 'local') {
            $disk = 'public';
        }

        $path = $file->store($directory, $disk);
        $publicUrl = Storage::disk($disk)->url($path);

        return [
            'public_url' => $publicUrl,
            'storage_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ];
    }

    /**
     * Upload multiple files.
     */
    public function uploadMultiple($files, $directory = 'uploads')
    {
        $results = [];

        foreach ($files as $file) {
            $upload = $this->uploadSingle($file, $directory);
            $results[] = $upload;
        }

        return [
            'successful' => $results,
        ];
    }
}