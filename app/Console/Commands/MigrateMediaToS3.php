<?php

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MigrateMediaToS3 extends Command
{
    protected $signature = 'media:migrate-to-s3
                            {--dry-run : Simulate migration without uploading or modifying database}
                            {--delete-local : Delete local files after successful migration}
                            {--force : Skip confirmation prompts}';

    protected $description = 'Migrate local media files (pictures/videos) to Amazon S3 and register them in database';

    private string $disk = 's3';

    private array $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];

    private array $videoExtensions = ['mp4', 'mov', 'avi', 'webm', 'mkv', 'flv', 'wmv'];

    private array $stats = [
        'scanned' => 0,
        'uploaded' => 0,
        'skipped' => 0,
        'failed' => 0,
        'deleted' => 0,
    ];

    private array $errors = [];

    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║          Media Migration to Amazon S3                        ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $isDryRun = $this->option('dry-run');
        $deleteLocal = $this->option('delete-local');

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Validate S3 connection
        if (! $isDryRun && ! $this->validateS3Connection()) {
            $this->error('❌ Cannot connect to S3. Please check your AWS credentials.');

            return self::FAILURE;
        }

        // Get directories to scan
        $directories = [
            'pictures' => public_path('pictures'),
            'videos' => public_path('videos'),
        ];

        // Scan and display files to migrate
        $filesToMigrate = $this->scanDirectories($directories);

        if (empty($filesToMigrate)) {
            $this->info('✅ No files found to migrate.');

            return self::SUCCESS;
        }

        $this->displayScanResults($filesToMigrate);

        // Confirmation
        if (! $isDryRun && ! $this->option('force')) {
            if (! $this->confirm('Do you want to proceed with the migration?')) {
                $this->info('Migration cancelled.');

                return self::SUCCESS;
            }
        }

        // Perform migration
        if (! $isDryRun) {
            $this->migrateFiles($filesToMigrate, $deleteLocal);
        } else {
            $this->simulateMigration($filesToMigrate);
        }

        // Display results
        $this->displayResults();

        // Log summary
        $this->logMigrationSummary();

        return $this->stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function validateS3Connection(): bool
    {
        try {
            $this->info('🔗 Validating S3 connection...');
            Storage::disk($this->disk)->directories();
            $this->info('✅ S3 connection successful');
            $this->newLine();

            return true;
        } catch (\Exception $e) {
            Log::error('S3 connection failed', ['error' => $e->getMessage()]);
            $this->error('Connection error: '.$e->getMessage());

            return false;
        }
    }

    private function scanDirectories(array $directories): array
    {
        $files = [];

        foreach ($directories as $type => $path) {
            if (! File::isDirectory($path)) {
                $this->warn("⚠️  Directory not found: {$path}");

                continue;
            }

            $this->info("📂 Scanning: {$path}");

            $scannedFiles = $this->scanDirectory($path, $type);
            $files = array_merge($files, $scannedFiles);

            $this->info('   Found: '.count($scannedFiles).' valid files');
        }

        $this->newLine();

        return $files;
    }

    private function scanDirectory(string $path, string $type): array
    {
        $files = [];
        $allFiles = File::allFiles($path);

        foreach ($allFiles as $file) {
            $this->stats['scanned']++;

            $extension = strtolower($file->getExtension());
            $mediaType = $this->determineMediaType($extension);

            if ($mediaType === null) {
                $this->line("   ⏭️  Skipping unsupported: {$file->getFilename()}");

                continue;
            }

            $files[] = [
                'path' => $file->getPathname(),
                'filename' => $file->getFilename(),
                'extension' => $extension,
                'size' => $file->getSize(),
                'type' => $mediaType,
                'source_directory' => $type,
                'relative_path' => $file->getRelativePathname(),
                'mime_type' => $this->getMimeType($file->getPathname()),
            ];
        }

        return $files;
    }

    private function determineMediaType(string $extension): ?string
    {
        if (in_array($extension, $this->imageExtensions)) {
            return 'image';
        }

        if (in_array($extension, $this->videoExtensions)) {
            return 'video';
        }

        return null;
    }

    private function getMimeType(string $path): string
    {
        return mime_content_type($path) ?: 'application/octet-stream';
    }

    private function displayScanResults(array $files): void
    {
        $images = array_filter($files, fn ($f) => $f['type'] === 'image');
        $videos = array_filter($files, fn ($f) => $f['type'] === 'video');

        $totalSize = array_sum(array_column($files, 'size'));

        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║                     Scan Results                             ║');
        $this->info('╠══════════════════════════════════════════════════════════════╣');
        $this->info('║  📸 Images: '.str_pad(count($images), 5).'                                          ║');
        $this->info('║  🎬 Videos: '.str_pad(count($videos), 5).'                                          ║');
        $this->info('║  📊 Total:  '.str_pad(count($files), 5).'                                          ║');
        $this->info('║  💾 Size:   '.str_pad($this->formatBytes($totalSize), 12).'                               ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();
    }

    private function migrateFiles(array $files, bool $deleteLocal): void
    {
        $this->info('🚀 Starting migration...');
        $this->newLine();

        $progressBar = $this->output->createProgressBar(count($files));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $progressBar->start();

        foreach ($files as $file) {
            $progressBar->setMessage("Processing: {$file['filename']}");

            try {
                $result = $this->migrateFile($file, $deleteLocal);

                if ($result === 'uploaded') {
                    $this->stats['uploaded']++;
                } elseif ($result === 'skipped') {
                    $this->stats['skipped']++;
                }
            } catch (\Exception $e) {
                $this->stats['failed']++;
                $this->errors[] = [
                    'file' => $file['filename'],
                    'error' => $e->getMessage(),
                ];
                Log::error('Media migration failed', [
                    'file' => $file['filename'],
                    'error' => $e->getMessage(),
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->setMessage('Complete!');
        $progressBar->finish();
        $this->newLine(2);
    }

    private function migrateFile(array $file, bool $deleteLocal): string
    {
        // Check for existing entry (idempotence)
        $existingMedia = Media::where('original_filename', $file['filename'])
            ->where('type', $file['type'])
            ->first();

        if ($existingMedia) {
            // Verify file still exists on S3
            if (Storage::disk($this->disk)->exists($existingMedia->path)) {
                return 'skipped';
            }
            // File doesn't exist on S3, delete DB entry and re-upload
            $existingMedia->delete();
        }

        // Generate unique filename
        $uniqueFilename = $this->generateUniqueFilename($file);
        $s3Path = $this->getS3Path($file['type'], $uniqueFilename);

        // Upload to S3
        DB::beginTransaction();

        try {
            $fileContent = File::get($file['path']);

            Storage::disk($this->disk)->put($s3Path, $fileContent, 'public');

            $s3Url = Storage::disk($this->disk)->url($s3Path);

            // Get image dimensions if applicable
            $width = null;
            $height = null;

            if ($file['type'] === 'image') {
                $imageInfo = @getimagesize($file['path']);
                if ($imageInfo) {
                    $width = $imageInfo[0];
                    $height = $imageInfo[1];
                }
            }

            // Create database entry
            Media::create([
                'filename' => $uniqueFilename,
                'original_filename' => $file['filename'],
                'path' => $s3Path,
                'url' => $s3Url,
                'disk' => $this->disk,
                'type' => $file['type'],
                'mime_type' => $file['mime_type'],
                'size' => $file['size'],
                'width' => $width,
                'height' => $height,
                'uploaded_by' => null, // System migration
            ]);

            DB::commit();

            // Delete local file if requested
            if ($deleteLocal) {
                File::delete($file['path']);
                $this->stats['deleted']++;
            }

            Log::info('Media migrated to S3', [
                'original' => $file['filename'],
                's3_path' => $s3Path,
                'url' => $s3Url,
            ]);

            return 'uploaded';
        } catch (\Exception $e) {
            DB::rollBack();

            // Attempt to clean up S3 if file was uploaded
            if (Storage::disk($this->disk)->exists($s3Path)) {
                Storage::disk($this->disk)->delete($s3Path);
            }

            throw $e;
        }
    }

    private function generateUniqueFilename(array $file): string
    {
        $prefix = $file['type'] === 'image' ? 'img' : 'vid';
        $timestamp = now()->format('Ymd_His');
        $random = Str::random(8);
        $hash = substr(md5($file['filename'].$file['size']), 0, 6);

        return "{$prefix}_{$timestamp}_{$hash}_{$random}.{$file['extension']}";
    }

    private function getS3Path(string $type, string $filename): string
    {
        return ($type === 'image' ? 'images' : 'videos').'/'.$filename;
    }

    private function simulateMigration(array $files): void
    {
        $this->info('📋 Simulation Results:');
        $this->newLine();

        $table = [];

        foreach ($files as $file) {
            $existingMedia = Media::where('original_filename', $file['filename'])
                ->where('type', $file['type'])
                ->first();

            $status = $existingMedia ? '⏭️  Would skip (exists)' : '✅ Would upload';

            if ($existingMedia) {
                $this->stats['skipped']++;
            } else {
                $this->stats['uploaded']++;
            }

            $table[] = [
                $file['filename'],
                $file['type'],
                $this->formatBytes($file['size']),
                $status,
            ];
        }

        $this->table(
            ['Filename', 'Type', 'Size', 'Status'],
            $table
        );

        $this->newLine();
    }

    private function displayResults(): void
    {
        $this->newLine();
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║                   Migration Results                          ║');
        $this->info('╠══════════════════════════════════════════════════════════════╣');
        $this->info('║  📂 Scanned:  '.str_pad($this->stats['scanned'], 5).'                                        ║');
        $this->info('║  ✅ Uploaded: '.str_pad($this->stats['uploaded'], 5).'                                        ║');
        $this->info('║  ⏭️  Skipped:  '.str_pad($this->stats['skipped'], 5).'                                        ║');
        $this->info('║  ❌ Failed:   '.str_pad($this->stats['failed'], 5).'                                        ║');

        if ($this->stats['deleted'] > 0) {
            $this->info('║  🗑️  Deleted:  '.str_pad($this->stats['deleted'], 5).'                                        ║');
        }

        $this->info('╚══════════════════════════════════════════════════════════════╝');

        if (! empty($this->errors)) {
            $this->newLine();
            $this->error('Errors encountered:');

            foreach ($this->errors as $error) {
                $this->error("  - {$error['file']}: {$error['error']}");
            }
        }
    }

    private function logMigrationSummary(): void
    {
        Log::info('Media migration completed', [
            'stats' => $this->stats,
            'errors_count' => count($this->errors),
        ]);
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

        return $bytes.' B';
    }
}
