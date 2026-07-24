<?php

namespace Zerp\ProductService\Providers;

use Illuminate\Support\ServiceProvider;

class ProductServiceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $routesPath = __DIR__.'/../Routes/web.php';
        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }

        $apiRoutesPath = __DIR__.'/../Routes/api.php';
        if (file_exists($apiRoutesPath)) {
            $this->loadRoutesFrom($apiRoutesPath);
        }

        // Scoped Swagger/OpenAPI docs for this module at /docs/product-service.
        if (class_exists(\Dedoc\Scramble\Scramble::class)) {
            \Dedoc\Scramble\Scramble::registerApi('product-service', [
                'api_path' => 'api/product-service-catalog',
                'info' => ['version' => \Composer\InstalledVersions::getPrettyVersion('zerp/product-service') ?? '1.0.0', 'description' => 'Zerp Product & Service catalog module REST API for mobile and third-party clients.'],
                'ui' => ['title' => 'Zerp Product & Service API'],
            ])->expose(ui: '/docs/product-service', document: '/docs/product-service.json');
        }

        $migrationsPath = __DIR__.'/../Database/Migrations';
        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
    }
}