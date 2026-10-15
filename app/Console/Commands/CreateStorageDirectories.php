<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CreateStorageDirectories extends Command
{
    protected $signature = 'storage:create-directories';
    protected $description = 'Create necessary storage directories for the application';

    public function handle()
    {
        $directories = [
            storage_path('app/public/layanan'),
        ];

        foreach ($directories as $directory) {
            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
                $this->info("Created directory: {$directory}");
            } else {
                $this->info("Directory already exists: {$directory}");
            }
        }

        $this->info('Storage directories created successfully!');
    }
}
