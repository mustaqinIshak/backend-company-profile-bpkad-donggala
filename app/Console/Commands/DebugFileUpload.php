<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class DebugFileUpload extends Command
{
    protected $signature = 'debug:file-upload {--check-permissions} {--check-logs}';
    protected $description = 'Debug file upload issues and display system information';

    public function handle()
    {
        $this->line('📋 File Upload Debug Information');
        $this->line('================================');

        // Check PHP Configuration
        $this->line("\n🔧 PHP Configuration:");
        $this->line("- Max Upload Size: " . ini_get('upload_max_filesize'));
        $this->line("- Post Max Size: " . ini_get('post_max_size'));
        $this->line("- Upload Tmp Dir: " . ini_get('upload_tmp_dir'));
        $this->line("- Memory Limit: " . ini_get('memory_limit'));

        // Check File Extensions
        $this->line("\n📦 PHP Extensions:");
        $extensions = ['fileinfo', 'gd', 'curl', 'zip'];
        foreach ($extensions as $ext) {
            $status = extension_loaded($ext) ? '✅' : '❌';
            $this->line("- $status $ext");
        }

        // Check Storage Directory
        $this->line("\n📁 Storage Directory:");
        $storageDir = storage_path('app/public/layanan');
        if (File::isDirectory($storageDir)) {
            $this->line("✅ Directory exists: $storageDir");
            
            if ($this->option('check-permissions')) {
                $perms = substr(sprintf('%o', fileperms($storageDir)), -4);
                $this->line("- Permissions: $perms");
                $this->line("- Is Writable: " . (is_writable($storageDir) ? '✅' Yes' : '❌ No'));
            }

            $files = File::files($storageDir);
            $this->line("- Files Count: " . count($files));
            $this->line("- Total Size: " . $this->formatBytes(array_sum(array_map('filesize', $files))));
        } else {
            $this->error("❌ Directory not found: $storageDir");
        }

        // Check Disk Space
        $this->line("\n💾 Disk Space:");
        $totalSpace = disk_total_space('/');
        $freeSpace = disk_free_space('/');
        $usedSpace = $totalSpace - $freeSpace;
        $usedPercent = ($usedSpace / $totalSpace) * 100;

        $this->line("- Total: " . $this->formatBytes($totalSpace));
        $this->line("- Used: " . $this->formatBytes($usedSpace) . " ($usedPercent%)");
        $this->line("- Free: " . $this->formatBytes($freeSpace));

        if ($usedPercent > 90) {
            $this->warn("⚠️  WARNING: Disk usage is above 90%!");
        }

        // Check Logs
        if ($this->option('check-logs')) {
            $this->line("\n📝 Recent Errors from Logs:");
            $logFile = storage_path('logs/laravel.log');
            if (File::exists($logFile)) {
                $lines = File::lines($logFile);
                $errorLines = $lines->filter(function ($line) {
                    return strpos($line, 'ERROR') !== false || strpos($line, 'Exception') !== false;
                })->slice(-10);

                if ($errorLines->count() > 0) {
                    foreach ($errorLines as $line) {
                        $this->line("- " . substr($line, 0, 100));
                    }
                } else {
                    $this->info("No recent errors found");
                }
            }
        }

        // Recommendations
        $this->line("\n💡 Recommendations:");
        if (ini_get('upload_max_filesize') < 20) {
            $this->warn("- Increase upload_max_filesize to at least 20M");
        }
        if (!is_writable($storageDir)) {
            $this->warn("- Fix storage directory permissions");
        }
        if ($usedPercent > 80) {
            $this->warn("- Consider cleaning up old files");
        }

        $this->info("\n✅ Debug complete!");
    }

    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
