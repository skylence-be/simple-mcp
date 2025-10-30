<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Skylence\SimpleMcp\Http\Controllers\McpController;

// Main MCP endpoint
Route::post('/', [McpController::class, 'handle']);

// Get server manifest
Route::get('/manifest.json', [McpController::class, 'manifest']);

// Call a specific tool
Route::post('/tools/call', [McpController::class, 'callTool']);

// Direct tool execution
Route::post('/tools/{tool}', [McpController::class, 'executeTool']);
