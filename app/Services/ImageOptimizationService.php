<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageOptimizationService
{
    /**
     * Standard variant configurations.
     * Max dimensions (width/height), quality, and target format.
     */
    public const VARIANTS = [
        'thumb'   => ['max_width' => 320,  'max_height' => 320,  'quality' => 82],
        'card'    => ['max_width' => 540,  'max_height' => 540,  'quality' => 84],
        'listing' => ['max_width' => 760,  'max_height' => 760,  'quality' => 85],
        'detail'  => ['max_width' => 1200, 'max_height' => 1200, 'quality' => 88],
        'zoom'    => ['max_width' => 1800, 'max_height' => 1800, 'quality' => 90],
    ];

    /**
     * Process an image (from file upload, URL, or Base64 string).
     * Saves master image to originals/ and generates optimized WebP variants.
     *
     * @param mixed $source File upload, binary string, Base64, or URL
     * @param int|string|null $productId
     * @return array Metadata with original URL, dimensions, and all variant URLs
     */
    public static function processImage(mixed $source, int|string|null $productId = null): ?array
    {
        try {
            $imageData = self::extractImageData($source);
            if (!$imageData || empty($imageData['data'])) {
                return null;
            }

            $rawBinary = $imageData['data'];
            $originalExt = $imageData['extension'] ?? 'jpg';
            $fileSize = strlen($rawBinary);

            // Load GD image resource
            $srcImage = @imagecreatefromstring($rawBinary);
            if (!$srcImage) {
                Log::warning('ImageOptimizationService: Failed to create GD image resource from source data.');
                return null;
            }

            $origWidth = imagesx($srcImage);
            $origHeight = imagesy($srcImage);

            $randomHash = Str::random(12);
            $folder = $productId ? "products/{$productId}" : "products/general";

            // 1. Store Master / Original image
            $originalFileName = "{$folder}/originals/master_{$randomHash}.{$originalExt}";
            Storage::disk('public')->put($originalFileName, $rawBinary);
            $originalUrl = Storage::disk('public')->url($originalFileName);

            // 2. Generate multi-size variants in modern WebP format
            $variants = [];
            foreach (self::VARIANTS as $variantName => $config) {
                $variantResult = self::createResizedWebp(
                    $srcImage,
                    $origWidth,
                    $origHeight,
                    $config['max_width'],
                    $config['max_height'],
                    $config['quality']
                );

                if ($variantResult) {
                    $variantFileName = "{$folder}/variants/{$variantName}_{$randomHash}.webp";
                    Storage::disk('public')->put($variantFileName, $variantResult['data']);
                    $variants[$variantName] = Storage::disk('public')->url($variantFileName);
                } else {
                    // Fallback to original URL if variant creation failed
                    $variants[$variantName] = $originalUrl;
                }
            }

            // Cleanup master GD resource
            imagedestroy($srcImage);

            return [
                'original_url' => $originalUrl,
                'original_path' => $originalFileName,
                'variants' => $variants,
                'width' => $origWidth,
                'height' => $origHeight,
                'file_size' => $fileSize,
                'format' => $originalExt,
            ];
        } catch (\Throwable $e) {
            Log::error('ImageOptimizationService Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Create a proportionally resized WebP binary string while preserving transparency.
     */
    protected static function createResizedWebp(
        \GdImage $srcImage,
        int $origWidth,
        int $origHeight,
        int $maxWidth,
        int $maxHeight,
        int $quality
    ): ?array {
        // If image is already smaller than max dimensions, preserve original dimensions
        if ($origWidth <= $maxWidth && $origHeight <= $maxHeight) {
            $targetWidth = $origWidth;
            $targetHeight = $origHeight;
        } else {
            $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight);
            $targetWidth = (int) max(1, round($origWidth * $ratio));
            $targetHeight = (int) max(1, round($origHeight * $ratio));
        }

        $dstImage = imagecreatetruecolor($targetWidth, $targetHeight);
        if (!$dstImage) {
            return null;
        }

        // Enable alpha transparency preservation for PNG/WebP/GIF cutouts
        imagealphablending($dstImage, false);
        imagesavealpha($dstImage, true);
        $transparent = imagecolorallocatealpha($dstImage, 255, 255, 255, 127);
        imagefilledrectangle($dstImage, 0, 0, $targetWidth, $targetHeight, $transparent);

        // High quality bicubic resampling
        imagecopyresampled(
            $dstImage,
            $srcImage,
            0, 0, 0, 0,
            $targetWidth,
            $targetHeight,
            $origWidth,
            $origHeight
        );

        // Render to WebP in memory buffer
        ob_start();
        imagewebp($dstImage, null, $quality);
        $webpData = ob_get_clean();

        imagedestroy($dstImage);

        return [
            'data' => $webpData,
            'width' => $targetWidth,
            'height' => $targetHeight,
        ];
    }

    /**
     * Extract raw image binary and format from various input types.
     */
    protected static function extractImageData(mixed $source): ?array
    {
        if (empty($source)) {
            return null;
        }

        // 1. UploadedFile instance
        if ($source instanceof \Illuminate\Http\UploadedFile) {
            return [
                'data' => file_get_contents($source->getRealPath()),
                'extension' => strtolower($source->getClientOriginalExtension() ?: 'jpg'),
            ];
        }

        // 2. Base64 Data URI string
        if (is_string($source) && str_starts_with($source, 'data:image/')) {
            if (preg_match('/data:image\/(?<extension>[\w]+);base64,(?<data>.*)/', $source, $matches)) {
                $ext = strtolower($matches['extension']) === 'jpeg' ? 'jpg' : strtolower($matches['extension']);
                return [
                    'data' => base64_decode($matches['data']),
                    'extension' => $ext,
                ];
            }
        }

        // 3. Local storage or public file path
        if (is_string($source) && (file_exists($source) || Storage::disk('public')->exists($source))) {
            $path = file_exists($source) ? $source : Storage::disk('public')->path($source);
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg');
            return [
                'data' => @file_get_contents($path),
                'extension' => $ext,
            ];
        }

        // 4. Remote HTTP URL
        if (is_string($source) && (str_starts_with($source, 'http://') || str_starts_with($source, 'https://'))) {
            try {
                $context = stream_context_create([
                    'http' => ['timeout' => 8, 'user_agent' => 'JSS-Solutions-Optimizer/1.0'],
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
                ]);
                $binary = @file_get_contents($source, false, $context);
                if ($binary) {
                    $ext = strtolower(pathinfo(parse_url($source, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'jpg');
                    return [
                        'data' => $binary,
                        'extension' => $ext,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('ImageOptimizationService: Failed to fetch remote image from ' . $source);
            }
        }

        return null;
    }
}
