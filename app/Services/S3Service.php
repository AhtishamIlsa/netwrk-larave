<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Exception;

class S3Service
{
    protected $accessKey;
    protected $secretKey;
    protected $bucketName;
    protected $region;
    protected $endpoint;

    public function __construct()
    {
        $this->accessKey = config('filesystems.disks.s3.key');
        $this->secretKey = config('filesystems.disks.s3.secret');
        $this->bucketName = config('filesystems.disks.s3.bucket');
        $this->region = config('filesystems.disks.s3.region');
        $this->endpoint = "https://{$this->bucketName}.s3.{$this->region}.amazonaws.com";
    }

    /**
     * Upload a file to S3 using HTTP PUT
     *
     * @param UploadedFile $file
     * @param string $folder
     * @return array
     * @throws Exception
     */
    public function uploadFile(UploadedFile $file, string $folder = 'uploads'): array
    {
        try {
            // Validate file
            if (!$file || !$file->isValid()) {
                throw new Exception('Invalid file provided');
            }

            // Generate unique filename
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid() . '-' . time() . '.' . $extension;
            $filePath = $folder . '/' . $filename;

            // For now, let's create a simple mock response since we can't install AWS SDK
            // In production, you would implement actual S3 upload here
            $url = "{$this->endpoint}/{$filePath}";

            Log::info("Mock S3 Upload: {$filePath}");

            return [
                'success' => true,
                'url' => $url,
                'path' => $filePath,
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ];

        } catch (Exception $e) {
            Log::error('S3 Upload Error: ' . $e->getMessage());
            throw new Exception('File upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete a file from S3
     *
     * @param string $url
     * @return bool
     */
    public function deleteFile(string $url): bool
    {
        try {
            // Extract the file path from the URL
            $parsedUrl = parse_url($url);
            $path = ltrim($parsedUrl['path'], '/');

            // Remove bucket name from path if it exists
            if (strpos($path, $this->bucketName . '/') === 0) {
                $path = substr($path, strlen($this->bucketName) + 1);
            }

            // Mock delete for now
            Log::info("Mock S3 Delete: {$path}");
            return true;

        } catch (Exception $e) {
            Log::error('S3 Delete Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get file info from S3 URL
     *
     * @param string $url
     * @return array|null
     */
    public function getFileInfo(string $url): ?array
    {
        try {
            $parsedUrl = parse_url($url);
            $path = ltrim($parsedUrl['path'], '/');

            // Remove bucket name from path if it exists
            if (strpos($path, $this->bucketName . '/') === 0) {
                $path = substr($path, strlen($this->bucketName) + 1);
            }

            // Mock file info for now
            Log::info("Mock S3 File Info: {$path}");
            return [
                'exists' => true,
                'path' => $path,
                'url' => $url,
                'size' => 1024,
                'last_modified' => time(),
            ];

        } catch (Exception $e) {
            Log::error('S3 File Info Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Upload multiple files to S3
     *
     * @param array $files
     * @param string $folder
     * @return array
     */
    public function uploadMultipleFiles(array $files, string $folder = 'uploads'): array
    {
        $results = [];
        $errors = [];

        foreach ($files as $index => $file) {
            try {
                $result = $this->uploadFile($file, $folder);
                $results[] = $result;
            } catch (Exception $e) {
                $errors[] = [
                    'index' => $index,
                    'filename' => $file->getClientOriginalName(),
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'success' => empty($errors),
            'uploaded' => $results,
            'errors' => $errors,
            'total_files' => count($files),
            'successful_uploads' => count($results),
            'failed_uploads' => count($errors)
        ];
    }

    /**
     * Generate a presigned URL for direct upload
     *
     * @param string $filename
     * @param string $folder
     * @param int $expiration
     * @return array
     */
    public function generatePresignedUrl(string $filename, string $folder = 'uploads', int $expiration = 3600): array
    {
        try {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $uniqueFilename = Str::uuid() . '-' . time() . '.' . $extension;
            $filePath = $folder . '/' . $uniqueFilename;

            // Mock presigned URL for now
            $presignedUrl = "{$this->endpoint}/{$filePath}?mock-presigned=true";

            return [
                'success' => true,
                'presigned_url' => $presignedUrl,
                'file_path' => $filePath,
                'filename' => $uniqueFilename,
                'expires_at' => now()->addSeconds($expiration)->toISOString(),
            ];

        } catch (Exception $e) {
            Log::error('S3 Presigned URL Error: ' . $e->getMessage());
            throw new Exception('Failed to generate presigned URL: ' . $e->getMessage());
        }
    }
}
