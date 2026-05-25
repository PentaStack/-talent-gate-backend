<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CloudinaryService
{
    private Cloudinary $cloudinary;

    public function __construct()
    {
        $cloudName = config('cloudinary.cloud_name');
        $apiKey    = config('cloudinary.api_key');
        $apiSecret = config('cloudinary.api_secret');

        // Guard: detect missing credentials early and log clearly
        if (! $cloudName || ! $apiKey || ! $apiSecret) {
            $missing = array_filter([
                'CLOUDINARY_CLOUD_NAME' => $cloudName,
                'CLOUDINARY_API_KEY'    => $apiKey,
                'CLOUDINARY_API_SECRET' => $apiSecret,
            ], fn($v) => ! $v);

            $missingKeys = implode(', ', array_keys($missing));

            Log::error('[Cloudinary] Missing credentials', [
                'missing_env_vars' => $missingKeys,
                'hint'             => 'Set the values in your .env file and run: php artisan config:clear',
            ]);

            throw new RuntimeException(
                "Cloudinary is not configured. Missing: {$missingKeys}. " .
                "Add them to your .env file and run: php artisan config:clear"
            );
        }

        $config = new Configuration([
            'cloud' => [
                'cloud_name' => $cloudName,
                'api_key'    => $apiKey,
                'api_secret' => $apiSecret,
            ],
            'url' => [
                'secure' => true,
            ],
        ]);

        $this->cloudinary = new Cloudinary($config);

        Log::debug('[Cloudinary] Configured', ['cloud_name' => $cloudName]);
    }

    /**
     * Upload an image (avatar / logo).
     * Returns the secure HTTPS URL.
     */
    public function uploadImage(\Illuminate\Http\UploadedFile $file, string $folder, ?string $publicId = null): string
    {
        $options = [
            'folder'         => $folder,
            'resource_type'  => 'image',
            'overwrite'      => true,
            'transformation' => [
                ['width' => 400, 'height' => 400, 'crop' => 'fill', 'gravity' => 'face'],
                ['quality' => 'auto', 'fetch_format' => 'auto'],
            ],
        ];

        if ($publicId) {
            $options['public_id'] = $publicId;
        }

        Log::info('[Cloudinary] Uploading image', [
            'folder'     => $folder,
            'public_id'  => $publicId,
            'mime'       => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
        ]);

        try {
            $result = $this->cloudinary->uploadApi()->upload($file->getRealPath(), $options);
            Log::info('[Cloudinary] Image uploaded', ['url' => $result['secure_url']]);
            return $result['secure_url'];
        } catch (\Throwable $e) {
            Log::error('[Cloudinary] Image upload failed', [
                'error'      => $e->getMessage(),
                'folder'     => $folder,
                'public_id'  => $publicId,
            ]);
            throw $e;
        }
    }

    /**
     * Upload a document (PDF / DOCX resume).
     * Returns the secure HTTPS URL.
     */
    public function uploadDocument(\Illuminate\Http\UploadedFile $file, string $folder, ?string $publicId = null): string
    {
        $options = [
            'folder'        => $folder,
            'resource_type' => 'raw',
            'overwrite'     => true,
        ];

        if ($publicId) {
            $options['public_id'] = $publicId;
        }

        Log::info('[Cloudinary] Uploading document', [
            'folder'     => $folder,
            'public_id'  => $publicId,
            'mime'       => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
        ]);

        try {
            $result = $this->cloudinary->uploadApi()->upload($file->getRealPath(), $options);
            Log::info('[Cloudinary] Document uploaded', ['url' => $result['secure_url']]);
            return $result['secure_url'];
        } catch (\Throwable $e) {
            Log::error('[Cloudinary] Document upload failed', [
                'error'     => $e->getMessage(),
                'folder'    => $folder,
                'public_id' => $publicId,
            ]);
            throw $e;
        }
    }

    /**
     * Delete a resource by its full Cloudinary URL.
     * Gracefully ignores errors (e.g. already deleted).
     */
    public function deleteByUrl(string $url, string $resourceType = 'image'): void
    {
        try {
            // Extract public_id from the URL
            // URL pattern: .../upload/v123456789/<folder/public_id>.<ext>
            if (preg_match('/\/upload\/(?:v\d+\/)?(.+?)(?:\.[a-z0-9]+)?$/i', $url, $matches)) {
                $publicId = $matches[1];
                Log::info('[Cloudinary] Deleting resource', ['public_id' => $publicId, 'type' => $resourceType]);
                $this->cloudinary->uploadApi()->destroy($publicId, ['resource_type' => $resourceType]);
            }
        } catch (\Throwable $e) {
            Log::warning('[Cloudinary] Delete failed (non-fatal)', [
                'error' => $e->getMessage(),
                'url'   => $url,
            ]);
        }
    }
}
