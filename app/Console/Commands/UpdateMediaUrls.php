<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateMediaUrls extends Command
{
    protected $signature = 'media:update-urls
                            {--dry-run : Show changes without applying them}
                            {--rollback : Revert S3 URLs back to local paths}
                            {--force : Force update all products even if URLs look like S3}';

    protected $description = 'Update product and category image URLs from local paths to S3 URLs';

    private array $stats = [
        'products_updated' => 0,
        'categories_updated' => 0,
        'images_mapped' => 0,
        'not_found' => 0,
    ];

    private bool $forceUpdate = false;

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $isRollback = $this->option('rollback');
        $this->forceUpdate = $this->option('force');

        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║          Update Media URLs in Products/Categories           ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        if ($this->forceUpdate) {
            $this->warn('💪 FORCE MODE - Will update all products based on filename matching');
            $this->newLine();
        }

        if ($isRollback) {
            $this->warn('⏪ ROLLBACK MODE - Converting S3 URLs back to local paths');
            $this->newLine();
        }

        // Build URL mapping from Media table
        $mediaMapping = $this->buildMediaMapping();

        if (empty($mediaMapping) && ! $isRollback) {
            $this->warn('⚠️  No media found in database. Run media:migrate-to-s3 first.');

            return self::FAILURE;
        }

        $this->info('📋 Found '.count($mediaMapping).' media entries in database');
        $this->newLine();

        // Update products
        $this->updateProducts($mediaMapping, $isDryRun, $isRollback);

        // Update categories
        $this->updateCategories($mediaMapping, $isDryRun, $isRollback);

        // Display results
        $this->displayResults();

        return self::SUCCESS;
    }

    private function buildMediaMapping(): array
    {
        $mapping = [];

        $media = Media::all();

        foreach ($media as $item) {
            $originalName = $item->original_filename;
            $mapping[$originalName] = [
                'url' => $item->url,
                'type' => $item->type,
                'local_path' => $item->type === 'image'
                    ? "/pictures/{$originalName}"
                    : "/videos/{$originalName}",
            ];
        }

        return $mapping;
    }

    private function updateProducts(array $mediaMapping, bool $isDryRun, bool $isRollback): void
    {
        $this->info('📦 Processing products...');

        $products = Product::all();

        foreach ($products as $product) {
            $images = $product->images ?? [];
            $updatedImages = [];
            $hasChanges = false;

            foreach ($images as $image) {
                $newUrl = $this->resolveUrl($image, $mediaMapping, $isRollback);

                if ($newUrl !== $image) {
                    $hasChanges = true;
                    $this->stats['images_mapped']++;
                    $this->line("   ✅ {$product->name}: {$image} → {$newUrl}");
                }

                $updatedImages[] = $newUrl;
            }

            if ($hasChanges) {
                if (! $isDryRun) {
                    $product->update(['images' => $updatedImages]);
                }
                $this->stats['products_updated']++;
            }
        }

        $this->newLine();
    }

    private function updateCategories(array $mediaMapping, bool $isDryRun, bool $isRollback): void
    {
        $this->info('📁 Processing categories...');

        $categories = Category::whereNotNull('image_url')->get();

        foreach ($categories as $category) {
            $currentUrl = $category->image_url;
            $newUrl = $this->resolveUrl($currentUrl, $mediaMapping, $isRollback);

            if ($newUrl !== $currentUrl) {
                $this->line("   ✅ {$category->name}: {$currentUrl} → {$newUrl}");
                $this->stats['images_mapped']++;

                if (! $isDryRun) {
                    $category->update(['image_url' => $newUrl]);
                }
                $this->stats['categories_updated']++;
            }
        }

        $this->newLine();
    }

    private function resolveUrl(string $url, array $mediaMapping, bool $isRollback): string
    {
        // Extract filename from URL (works for both local paths and S3 URLs)
        $filename = basename($url);

        // Remove any query parameters
        if (str_contains($filename, '?')) {
            $filename = explode('?', $filename)[0];
        }

        // Check if we have this file in our media mapping (direct match by original_filename)
        if (isset($mediaMapping[$filename])) {
            $media = $mediaMapping[$filename];

            if ($isRollback) {
                return $media['local_path'];
            }

            if ($this->forceUpdate || $url !== $media['url']) {
                return $media['url'];
            }
        }

        // Try to match by pattern (e.g., "1.jpg", "2.jpg") for local paths
        if (preg_match('/(\d+)\.(jpg|jpeg|png|gif|webp|mp4|mov|avi|webm)$/i', $filename, $matches)) {
            $searchName = $matches[1].'.'.$matches[2];

            if (isset($mediaMapping[$searchName])) {
                $media = $mediaMapping[$searchName];

                if ($isRollback) {
                    return $media['local_path'];
                }

                if ($this->forceUpdate || $url !== $media['url']) {
                    return $media['url'];
                }
            }
        }

        // Try to match by hash in filename (for S3 URLs from old migrations)
        // Pattern: img_YYYYMMDD_HHMMSS_HASH_RANDOM.ext - extract HASH
        if (preg_match('/(?:img|vid)_\d{8}_\d{6}_([a-f0-9]{6})_[A-Za-z0-9]+\.(jpg|jpeg|png|gif|webp|mp4|mov|avi|webm)$/i', $filename, $matches)) {
            $hash = $matches[1];
            $extension = strtolower($matches[2]);

            // Search for media with matching hash in their filename
            foreach ($mediaMapping as $originalName => $media) {
                // Check if the media's S3 filename contains the same hash
                if (preg_match('/(?:img|vid)_\d{8}_\d{6}_'.preg_quote($hash, '/').'_/', basename($media['url']))) {
                    if ($isRollback) {
                        return $media['local_path'];
                    }

                    if ($this->forceUpdate || $url !== $media['url']) {
                        return $media['url'];
                    }
                }
            }
        }

        // No mapping found
        if (! $isRollback && str_starts_with($url, 'http')) {
            // It's an S3 URL that doesn't match - this is the problem case
            $this->stats['not_found']++;
            $this->warn("   ⚠️  Orphan S3 URL (no matching media): {$filename}");
        } elseif (! $isRollback && ! str_starts_with($url, 'http')) {
            $this->stats['not_found']++;
            $this->warn("   ⚠️  No S3 mapping for local path: {$url}");
        }

        return $url;
    }

    private function displayResults(): void
    {
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║                        Results                               ║');
        $this->info('╠══════════════════════════════════════════════════════════════╣');
        $this->info('║  📦 Products updated:   '.str_pad($this->stats['products_updated'], 5).'                              ║');
        $this->info('║  📁 Categories updated: '.str_pad($this->stats['categories_updated'], 5).'                              ║');
        $this->info('║  🖼️  Images mapped:      '.str_pad($this->stats['images_mapped'], 5).'                              ║');

        if ($this->stats['not_found'] > 0) {
            $this->info('║  ⚠️  Not found:         '.str_pad($this->stats['not_found'], 5).'                              ║');
        }

        $this->info('╚══════════════════════════════════════════════════════════════╝');

        Log::info('Media URLs updated', ['stats' => $this->stats]);
    }
}
