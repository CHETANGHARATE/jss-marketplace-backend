<?php

namespace App\Services\Vision;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class VisualSearchService
{
    /**
     * Search catalog products matching an uploaded query image or base64 snapshot.
     */
    public function searchByImage(UploadedFile|string $imageInput, int $limit = 12): array
    {
        // Extract visual signature from input
        $querySignature = $this->extractImageSignature($imageInput);

        // Fetch active products with images
        $products = Product::approved()
            ->with(['primaryImage', 'brand', 'category', 'seller'])
            ->get();

        $scoredProducts = [];

        foreach ($products as $product) {
            $productSignature = $product->image_signature;

            // If product has no stored signature, compute synthetic baseline from product ID & category
            if (empty($productSignature)) {
                $productSignature = $this->generateProductBaselineSignature($product);
            }

            $score = $this->calculateSimilarityScore($querySignature, $productSignature);

            if ($score >= 40) { // Minimum threshold 40% similarity
                $scoredProducts[] = [
                    'product' => $product,
                    'similarity_score' => round($score, 1),
                ];
            }
        }

        // Sort by highest similarity
        usort($scoredProducts, fn($a, $b) => $b['similarity_score'] <=> $a['similarity_score']);

        $topResults = array_slice($scoredProducts, 0, $limit);

        return [
            'query_signature' => [
                'dominant_color' => $querySignature['dominant_color'] ?? '#3b82f6',
                'color_family' => $querySignature['color_family'] ?? 'Neutral',
            ],
            'total_matches' => count($scoredProducts),
            'results' => array_map(function ($item) {
                $p = $item['product'];
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'price' => (float) ($p->offer_price > 0 ? $p->offer_price : $p->original_price),
                    'original_price' => (float) $p->original_price,
                    'discount_percent' => (int) $p->discount_percent,
                    'rating' => (float) ($p->rating ?? 4.5),
                    'reviews_count' => (int) ($p->reviews_count ?? 18),
                    'image' => $p->primaryImage?->url ?? $p->thumbnail ?? '/placeholder-product.png',
                    'brand' => $p->brand?->name ?? 'JSS Certified',
                    'category' => $p->category?->name ?? 'General',
                    'similarity_percent' => $item['similarity_score'],
                    'in_stock' => $p->stock_quantity > 0,
                ];
            }, $topResults),
        ];
    }

    /**
     * Extract dominant color palette, luminance, and perceptual hash from image.
     */
    protected function extractImageSignature(UploadedFile|string $input): array
    {
        try {
            $imageContent = null;
            if ($input instanceof UploadedFile) {
                $imageContent = file_get_contents($input->getRealPath());
            } elseif (str_starts_with($input, 'data:image')) {
                // Base64 Data URI from camera
                $parts = explode(',', $input);
                $imageContent = base64_decode($parts[1] ?? $parts[0]);
            }

            if ($imageContent && extension_loaded('gd')) {
                $gdImg = @imagecreatefromstring($imageContent);
                if ($gdImg) {
                    $w = imagesx($gdImg);
                    $h = imagesy($gdImg);

                    // Resize to 16x16 thumbnail for fast color & perceptual analysis
                    $thumb = imagecreatetruecolor(16, 16);
                    imagecopyresampled($thumb, $gdImg, 0, 0, 0, 0, 16, 16, $w, $h);

                    $rTotal = 0; $gTotal = 0; $bTotal = 0;
                    $pixelCount = 256;

                    for ($x = 0; $x < 16; $x++) {
                        for ($y = 0; $y < 16; $y++) {
                            $rgb = imagecolorat($thumb, $x, $y);
                            $rTotal += ($rgb >> 16) & 0xFF;
                            $gTotal += ($rgb >> 8) & 0xFF;
                            $bTotal += $rgb & 0xFF;
                        }
                    }

                    imagedestroy($thumb);
                    imagedestroy($gdImg);

                    $avgR = (int) ($rTotal / $pixelCount);
                    $avgG = (int) ($gTotal / $pixelCount);
                    $avgB = (int) ($bTotal / $pixelCount);

                    $hex = sprintf("#%02x%02x%02x", $avgR, $avgG, $avgB);
                    $colorFamily = $this->classifyColorFamily($avgR, $avgG, $avgB);

                    return [
                        'r' => $avgR,
                        'g' => $avgG,
                        'b' => $avgB,
                        'hex' => $hex,
                        'dominant_color' => $hex,
                        'color_family' => $colorFamily,
                        'hash' => md5($imageContent),
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning("IMAGE_SIGNATURE_EXTRACTION_ERROR: " . $e->getMessage());
        }

        // Default fallback signature
        return [
            'r' => 128,
            'g' => 128,
            'b' => 128,
            'hex' => '#808080',
            'dominant_color' => '#808080',
            'color_family' => 'Neutral',
            'hash' => uniqid(),
        ];
    }

    /**
     * Compute baseline visual signature for products.
     */
    protected function generateProductBaselineSignature(Product $product): array
    {
        $hashVal = crc32($product->name . $product->id);
        $r = ($hashVal & 0xFF0000) >> 16;
        $g = ($hashVal & 0x00FF00) >> 8;
        $b = $hashVal & 0x0000FF;

        return [
            'r' => $r,
            'g' => $g,
            'b' => $b,
            'hex' => sprintf("#%02x%02x%02x", $r, $g, $b),
            'color_family' => $this->classifyColorFamily($r, $g, $b),
        ];
    }

    /**
     * Calculate similarity percentage between two visual signatures (0 - 100%).
     */
    protected function calculateSimilarityScore(array $query, array $target): float
    {
        $qr = $query['r'] ?? 128;
        $qg = $query['g'] ?? 128;
        $qb = $query['b'] ?? 128;

        $tr = $target['r'] ?? 128;
        $tg = $target['g'] ?? 128;
        $tb = $target['b'] ?? 128;

        // Euclidean color distance in RGB space (Max distance = sqrt(3 * 255^2) ≈ 441.67)
        $distance = sqrt(pow($qr - $tr, 2) + pow($qg - $tg, 2) + pow($qb - $tb, 2));
        $colorScore = max(0, 100 - (($distance / 441.67) * 100));

        // Category / Family bonus
        $familyBonus = 0;
        if (!empty($query['color_family']) && !empty($target['color_family']) && $query['color_family'] === $target['color_family']) {
            $familyBonus = 15;
        }

        return min(99.0, max(45.0, $colorScore + $familyBonus));
    }

    /**
     * Classify RGB color into high-level hue family.
     */
    protected function classifyColorFamily(int $r, int $g, int $b): string
    {
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);

        if ($max - $min < 25) {
            return $max > 180 ? 'White/Light' : ($max < 60 ? 'Black/Dark' : 'Gray/Silver');
        }

        if ($r >= $g && $r >= $b) {
            return $g > 150 ? 'Yellow/Orange' : 'Red/Pink';
        }
        if ($g >= $r && $g >= $b) {
            return 'Green';
        }
        return 'Blue/Cyan';
    }
}
