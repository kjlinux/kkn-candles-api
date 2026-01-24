<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Models\Product;
use Illuminate\Console\Command;

class DiagnoseMediaUrls extends Command
{
    protected $signature = 'media:diagnose';

    protected $description = 'Diagnose media URL mapping issues';

    public function handle(): int
    {
        $this->info('=== DIAGNOSTIC MEDIA URLs ===');
        $this->newLine();

        // 1. Show media in database
        $this->info('📋 Media in database:');
        $media = Media::all();
        $mediaTable = [];
        foreach ($media->take(10) as $m) {
            $mediaTable[] = [
                'original_filename' => $m->original_filename,
                'filename' => $m->filename,
                'type' => $m->type,
            ];
        }
        $this->table(['original_filename', 'filename', 'type'], $mediaTable);
        $this->info("Total: {$media->count()} media");
        $this->newLine();

        // 2. Show product images
        $this->info('📦 Product images in database:');
        $products = Product::all();
        $productTable = [];
        foreach ($products as $p) {
            $images = $p->images ?? [];
            foreach ($images as $img) {
                $filename = basename($img);
                $productTable[] = [
                    'product' => substr($p->name, 0, 30),
                    'image_url' => substr($img, 0, 60),
                    'extracted_filename' => $filename,
                ];
            }
        }
        $this->table(['product', 'image_url', 'extracted_filename'], $productTable);
        $this->newLine();

        // 3. Check for matches
        $this->info('🔍 Matching analysis:');
        $mediaFilenames = $media->pluck('original_filename')->toArray();

        foreach ($products as $p) {
            $images = $p->images ?? [];
            foreach ($images as $img) {
                $filename = basename($img);

                // Direct match
                if (in_array($filename, $mediaFilenames)) {
                    $this->line("   ✅ {$p->name}: '{$filename}' found in media table");
                } else {
                    $this->warn("   ❌ {$p->name}: '{$filename}' NOT found in media table");

                    // Try pattern matching
                    if (preg_match('/(\d+)\.(jpg|jpeg|png|gif|webp)$/i', $filename, $matches)) {
                        $searchName = $matches[1].'.'.$matches[2];
                        if (in_array($searchName, $mediaFilenames)) {
                            $this->info("      → But '{$searchName}' exists!");
                        }
                    }
                }
            }
        }

        return self::SUCCESS;
    }
}
