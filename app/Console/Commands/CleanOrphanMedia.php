<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Services\S3MediaService;
use Illuminate\Console\Command;

class CleanOrphanMedia extends Command
{
    protected $signature = 'media:clean-orphans
                            {--dry-run : List orphan media without deleting them}
                            {--days=30 : Only consider media older than this many days}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Find and optionally delete orphan media (media not linked to any product)';

    public function __construct(
        private S3MediaService $mediaService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $days = (int) $this->option('days');
        $force = $this->option('force');

        $this->info($dryRun ? '=== DRY RUN: Orphan Media Analysis ===' : '=== Orphan Media Cleanup ===');
        $this->newLine();

        $orphanMedia = Media::query()
            ->whereDoesntHave('products')
            ->where('created_at', '<', now()->subDays($days))
            ->get();

        if ($orphanMedia->isEmpty()) {
            $this->info("No orphan media found older than {$days} days.");

            return self::SUCCESS;
        }

        $this->info("Found {$orphanMedia->count()} orphan media older than {$days} days:");
        $this->newLine();

        $tableData = $orphanMedia->map(fn ($m) => [
            'id' => substr($m->id, 0, 8).'...',
            'filename' => $m->original_filename,
            'type' => $m->type,
            'size' => $m->formatted_size,
            'created_at' => $m->created_at->format('Y-m-d H:i'),
        ])->toArray();

        $this->table(['ID', 'Filename', 'Type', 'Size', 'Created'], $tableData);

        $totalSize = $orphanMedia->sum('size');
        $this->info('Total space: '.$this->formatBytes($totalSize));
        $this->newLine();

        if ($dryRun) {
            $this->warn('This was a dry run. No media was deleted.');
            $this->info('Run without --dry-run to delete these files.');

            return self::SUCCESS;
        }

        if (! $force && ! $this->confirm('Do you want to delete these orphan media files?')) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        $this->info('Deleting orphan media...');
        $bar = $this->output->createProgressBar($orphanMedia->count());
        $bar->start();

        $deleted = 0;
        $errors = [];

        foreach ($orphanMedia as $media) {
            try {
                $this->mediaService->delete($media);
                $deleted++;
            } catch (\Exception $e) {
                $errors[] = [
                    'media' => $media->original_filename,
                    'error' => $e->getMessage(),
                ];
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Successfully deleted {$deleted} orphan media files.");

        if (! empty($errors)) {
            $this->warn('Some files could not be deleted:');
            foreach ($errors as $error) {
                $this->error("  - {$error['media']}: {$error['error']}");
            }
        }

        return empty($errors) ? self::SUCCESS : self::FAILURE;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';
    }
}
