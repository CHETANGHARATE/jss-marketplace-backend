<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use App\Services\ImageOptimizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OptimizeExistingImagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'images:optimize-existing {--batch=50 : Number of images to process per run} {--force : Reprocess even if variants already exist}';

    /**
     * The console command description.
     */
    protected $description = 'Scan existing product images, generate WebP/AVIF multi-size variants, and update metadata';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $batchSize = (int) $this->option('batch');
        $force = (bool) $this->option('force');

        $this->info('Starting Existing Image Optimization Pipeline...');

        $query = ProductImage::query();
        if (!$force) {
            $query->whereNull('variants');
        }

        $totalFound = $query->count();
        if ($totalFound === 0) {
            $this->info('All images are already optimized with multi-size variants! Use --force to reprocess.');
            return Command::SUCCESS;
        }

        $this->info("Found {$totalFound} image(s) to optimize. Processing batch of {$batchSize}...");

        $images = $query->limit($batchSize)->get();

        $processed = 0;
        $skipped = 0;
        $failed = 0;
        $originalBytesTotal = 0;
        $optimizedBytesTotal = 0;

        $progressBar = $this->output->createProgressBar($images->count());
        $progressBar->start();

        foreach ($images as $img) {
            $source = $img->image_url;

            // Check if source is relative storage path
            if (!str_starts_with($source, 'http://') && !str_starts_with($source, 'https://') && !str_starts_with($source, 'data:')) {
                $clean = ltrim($source, '/');
                if (str_starts_with($clean, 'storage/')) {
                    $clean = substr($clean, 8);
                }
                if (Storage::disk('public')->exists($clean)) {
                    $source = Storage::disk('public')->path($clean);
                }
            }

            $result = ImageOptimizationService::processImage($source, $img->product_id);

            if ($result && !empty($result['variants'])) {
                $img->update([
                    'variants' => $result['variants'],
                    'width' => $result['width'],
                    'height' => $result['height'],
                    'file_size' => $result['file_size'],
                    'format' => $result['format'],
                ]);

                $origSize = $result['file_size'] ?? 0;
                $originalBytesTotal += $origSize;

                // Estimate card variant size savings (~80% reduction)
                $optimizedBytesTotal += (int) round($origSize * 0.18);
                $processed++;
            } else {
                $failed++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $savedMB = max(0, round(($originalBytesTotal - $optimizedBytesTotal) / (1024 * 1024), 2));

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Images Found', $totalFound],
                ['Processed in this batch', $processed],
                ['Skipped', $skipped],
                ['Failed', $failed],
                ['Original Size', round($originalBytesTotal / (1024 * 1024), 2) . ' MB'],
                ['Optimized Derivatives Size (Est.)', round($optimizedBytesTotal / (1024 * 1024), 2) . ' MB'],
                ['Estimated Bandwidth / Storage Saved', $savedMB . ' MB'],
            ]
        );

        $this->info('Image optimization batch completed successfully.');

        return Command::SUCCESS;
    }
}
