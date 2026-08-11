<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Ensure storage symlink subdirectories exist
	if (env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }
        $directories = ['jumbotron', 'profile', 'berita', 'layanan'];
        foreach ($directories as $dir) {
            Storage::disk('public')->makeDirectory($dir);
        }
    }
}
