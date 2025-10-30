<?php

declare(strict_types=1);

namespace Skylence\SimpleMcp;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Skylence\SimpleMcp\MCP\SimpleMcpServer;
use Skylence\SimpleMcp\Support\Logger;

final class SimpleMcpServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/simple-mcp.php',
            'simple-mcp'
        );

        // Register Logger as singleton
        $this->app->singleton(Logger::class, function ($app) {
            return new Logger(
                config('simple-mcp.logging.enabled', true),
                config('simple-mcp.logging.channel', 'stack')
            );
        });

        // Register SimpleMcpServer
        $this->app->singleton(SimpleMcpServer::class, function ($app) {
            return new SimpleMcpServer();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! config('simple-mcp.enabled', true)) {
            return;
        }

        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/simple-mcp.php' => config_path('simple-mcp.php'),
        ], 'simple-mcp-config');

        // Register routes
        $this->registerRoutes();
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        Route::group([
            'prefix' => config('simple-mcp.path', 'simple-mcp'),
            'middleware' => config('simple-mcp.middleware', ['api']),
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });
    }
}
