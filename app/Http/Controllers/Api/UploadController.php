<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\S3Service;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Exception;

/**
 * @OA\Tag(
 *     name="upload",
 *     description="File upload endpoints"
 * )
 */
class UploadController extends Controller
{
    protected $s3Service;

    public function __construct(S3Service $s3Service)
    {
        $this->s3Service = $s3Service;
    }

    /**
     * @OA\Post(
     *     path="/api/upload",
     *     summary="Upload a file to S3",
     *     tags={"upload"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="File to upload"
     *                 ),
     *                 @OA\Property(
     *                     property="folder",
     *                     type="string",
     *                     description="Folder to upload to (optional, defaults to 'uploads')",
     *                     example="avatars"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="statusCode", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="File uploaded successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="url", type="string", example="https://bucket.s3.amazonaws.com/uploads/file.jpg"),
     *                 @OA\Property(property="path", type="string", example="uploads/uuid-filename.jpg"),
     *                 @OA\Property(property="filename", type="string", example="uuid-filename.jpg"),
     *                 @OA\Property(property="original_name", type="string", example="original-filename.jpg"),
     *                 @OA\Property(property="size", type="integer", example=1024),
     *                 @OA\Property(property="mime_type", type="string", example="image/jpeg")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid file upload",
     *         @OA\JsonContent(
     *             @OA\Property(property="statusCode", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="No file provided"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="statusCode", type="integer", example=422),
     *             @OA\Property(property="message", type="string", example="The file field is required"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     )
     * )
     */
    public function upload(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|max:10240', // 10MB max
                'folder' => 'sometimes|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'statusCode' => 422,
                    'message' => 'Validation failed',
                    'data' => [
                        'errors' => $validator->errors()
                    ]
                ], 422);
            }

            $file = $request->file('file');
            $folder = $request->input('folder', 'uploads');

            // Upload file to S3
            $result = $this->s3Service->uploadFile($file, $folder);

            return response()->json([
                'statusCode' => 200,
                'message' => 'File uploaded successfully',
                'data' => $result
            ]);

        } catch (Exception $e) {
            return response()->json([
                'statusCode' => 400,
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/upload/multiple",
     *     summary="Upload multiple files to S3",
     *     tags={"upload"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="files[]",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary"),
     *                     description="Files to upload"
     *                 ),
     *                 @OA\Property(
     *                     property="folder",
     *                     type="string",
     *                     description="Folder to upload to (optional, defaults to 'uploads')",
     *                     example="documents"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Files uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="statusCode", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Files uploaded successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_files", type="integer", example=3),
     *                 @OA\Property(property="successful_uploads", type="integer", example=2),
     *                 @OA\Property(property="failed_uploads", type="integer", example=1),
     *                 @OA\Property(property="uploaded", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="errors", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     )
     * )
     */
    public function uploadMultiple(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'files' => 'required|array|min:1|max:10',
                'files.*' => 'file|max:10240', // 10MB max per file
                'folder' => 'sometimes|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'statusCode' => 422,
                    'message' => 'Validation failed',
                    'data' => [
                        'errors' => $validator->errors()
                    ]
                ], 422);
            }

            $files = $request->file('files');
            $folder = $request->input('folder', 'uploads');

            // Upload files to S3
            $result = $this->s3Service->uploadMultipleFiles($files, $folder);

            $message = $result['success'] 
                ? 'All files uploaded successfully' 
                : "Uploaded {$result['successful_uploads']} of {$result['total_files']} files";

            return response()->json([
                'statusCode' => 200,
                'message' => $message,
                'data' => $result
            ]);

        } catch (Exception $e) {
            return response()->json([
                'statusCode' => 400,
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/upload/delete",
     *     summary="Delete a file from S3",
     *     tags={"upload"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="url",
     *                 type="string",
     *                 description="S3 URL of the file to delete",
     *                 example="https://bucket.s3.amazonaws.com/uploads/file.jpg"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="statusCode", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="File deleted successfully"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Failed to delete file",
     *         @OA\JsonContent(
     *             @OA\Property(property="statusCode", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Failed to delete file"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     )
     * )
     */
    public function delete(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'url' => 'required|url'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'statusCode' => 422,
                    'message' => 'Validation failed',
                    'data' => [
                        'errors' => $validator->errors()
                    ]
                ], 422);
            }

            $url = $request->input('url');

            // Delete file from S3
            $deleted = $this->s3Service->deleteFile($url);

            if ($deleted) {
                return response()->json([
                    'statusCode' => 200,
                    'message' => 'File deleted successfully',
                    'data' => null
                ]);
            } else {
                return response()->json([
                    'statusCode' => 400,
                    'message' => 'Failed to delete file',
                    'data' => null
                ], 400);
            }

        } catch (Exception $e) {
            return response()->json([
                'statusCode' => 400,
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/upload/presigned-url",
     *     summary="Generate a presigned URL for direct upload",
     *     tags={"upload"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="filename",
     *                 type="string",
     *                 description="Original filename",
     *                 example="document.pdf"
     *             ),
     *             @OA\Property(
     *                 property="folder",
     *                 type="string",
     *                 description="Folder to upload to (optional, defaults to 'uploads')",
     *                 example="documents"
     *             ),
     *             @OA\Property(
     *                 property="expiration",
     *                 type="integer",
     *                 description="URL expiration time in seconds (optional, defaults to 3600)",
     *                 example=3600
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Presigned URL generated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="statusCode", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Presigned URL generated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="presigned_url", type="string", example="https://bucket.s3.amazonaws.com/uploads/file.jpg?signature=..."),
     *                 @OA\Property(property="file_path", type="string", example="uploads/uuid-filename.jpg"),
     *                 @OA\Property(property="filename", type="string", example="uuid-filename.jpg"),
     *                 @OA\Property(property="expires_at", type="string", example="2024-01-01T12:00:00Z")
     *             )
     *         )
     *     )
     * )
     */
    public function generatePresignedUrl(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'filename' => 'required|string|max:255',
                'folder' => 'sometimes|string|max:255',
                'expiration' => 'sometimes|integer|min:60|max:86400' // 1 minute to 24 hours
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'statusCode' => 422,
                    'message' => 'Validation failed',
                    'data' => [
                        'errors' => $validator->errors()
                    ]
                ], 422);
            }

            $filename = $request->input('filename');
            $folder = $request->input('folder', 'uploads');
            $expiration = $request->input('expiration', 3600);

            // Generate presigned URL
            $result = $this->s3Service->generatePresignedUrl($filename, $folder, $expiration);

            return response()->json([
                'statusCode' => 200,
                'message' => 'Presigned URL generated successfully',
                'data' => $result
            ]);

        } catch (Exception $e) {
            return response()->json([
                'statusCode' => 400,
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/upload/file-info",
     *     summary="Get file information from S3 URL",
     *     tags={"upload"},
     *     @OA\Parameter(
     *         name="url",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="S3 URL of the file"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File information retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="statusCode", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="File information retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="exists", type="boolean", example=true),
     *                 @OA\Property(property="path", type="string", example="uploads/file.jpg"),
     *                 @OA\Property(property="url", type="string", example="https://bucket.s3.amazonaws.com/uploads/file.jpg"),
     *                 @OA\Property(property="size", type="integer", example=1024),
     *                 @OA\Property(property="last_modified", type="integer", example=1640995200)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="File not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="statusCode", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="File not found"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     )
     * )
     */
    public function getFileInfo(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'url' => 'required|url'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'statusCode' => 422,
                    'message' => 'Validation failed',
                    'data' => [
                        'errors' => $validator->errors()
                    ]
                ], 422);
            }

            $url = $request->input('url');

            // Get file info from S3
            $fileInfo = $this->s3Service->getFileInfo($url);

            if ($fileInfo) {
                return response()->json([
                    'statusCode' => 200,
                    'message' => 'File information retrieved successfully',
                    'data' => $fileInfo
                ]);
            } else {
                return response()->json([
                    'statusCode' => 404,
                    'message' => 'File not found',
                    'data' => null
                ], 404);
            }

        } catch (Exception $e) {
            return response()->json([
                'statusCode' => 400,
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }
}
