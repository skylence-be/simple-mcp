# Simple MCP

A super simple MCP (Model Context Protocol) server package for Laravel, based on the Laravel Telescope MCP architecture.

## Features

- 🚀 Simple and lightweight MCP server
- 🛠️ Two example tools: Ping and Echo
- 📦 Easy to extend with custom tools
- 🔧 Follows JSON-RPC 2.0 specification
- 📝 Built-in logging support
- ⚙️ Configurable via config file

## Installation

### 1. Install via Composer

If you're developing this package locally, add it to your Laravel project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../laravel-packages/simple-mcp"
        }
    ]
}
```

Then require it:

```bash
composer require skylence/simple-mcp
```

### 2. Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=simple-mcp-config
```

This will create `config/simple-mcp.php` where you can customize:
- Server path (default: `simple-mcp`)
- Enable/disable the server
- Middleware configuration
- Logging settings

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
SIMPLE_MCP_PATH=simple-mcp
SIMPLE_MCP_ENABLED=true
SIMPLE_MCP_LOGGING_ENABLED=true
SIMPLE_MCP_LOGGING_CHANNEL=stack
```

## Usage

### Available Endpoints

Once installed, the MCP server is available at `/simple-mcp` (or your configured path):

```
POST /simple-mcp                - JSON-RPC 2.0 endpoint (supports all methods)
GET  /simple-mcp/manifest.json  - Get server manifest (MCP protocol format)
POST /simple-mcp/manifest.json  - JSON-RPC 2.0 endpoint (same as POST /)
POST /simple-mcp/tools/call     - Call a tool via JSON-RPC
POST /simple-mcp/tools/{tool}   - Direct tool execution
```

### Testing the Server

#### 1. Get the Manifest

```bash
curl http://localhost:8000/simple-mcp/manifest.json
```

Response:
```json
{
    "jsonrpc": "2.0",
    "result": {
        "protocolVersion": "2024-11-05",
        "serverInfo": {
            "name": "simple-mcp",
            "version": "1.0.0",
            "description": "A simple MCP server for Laravel"
        },
        "capabilities": {
            "tools": [
                {
                    "name": "mcp__simple-mcp__ping",
                    "description": "Simple ping tool to check if the MCP server is responding",
                    "parameters": {...}
                },
                {
                    "name": "mcp__simple-mcp__echo",
                    "description": "Echo back any message you send",
                    "parameters": {...}
                }
            ]
        }
    },
    "id": null
}
```

#### 2. Ping Tool (via direct endpoint)

```bash
curl -X POST http://localhost:8000/simple-mcp/tools/ping
```

Response:
```json
{
    "content": [
        {
            "type": "text",
            "text": "Pong! MCP server is up and running."
        }
    ],
    "data": {
        "status": "ok",
        "timestamp": "2025-10-30T12:34:56+00:00",
        "server": "simple-mcp"
    }
}
```

#### 3. Echo Tool (via direct endpoint)

```bash
curl -X POST http://localhost:8000/simple-mcp/tools/echo \
  -H "Content-Type: application/json" \
  -d '{"message": "Hello, MCP!"}'
```

Response:
```json
{
    "content": [
        {
            "type": "text",
            "text": "Echo: Hello, MCP!"
        }
    ],
    "data": {
        "original": "Hello, MCP!",
        "length": 11,
        "timestamp": "2025-10-30T12:34:56+00:00"
    }
}
```

#### 4. Using JSON-RPC 2.0 Format

```bash
curl -X POST http://localhost:8000/simple-mcp \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "method": "tools/call",
    "params": {
        "name": "echo",
        "arguments": {
            "message": "Testing JSON-RPC"
        }
    },
    "id": 1
}'
```

## Available Tools

### 1. Ping Tool

Simple health check tool.

**Name:** `mcp__simple-mcp__ping`

**Parameters:** None

**Example:**
```bash
curl -X POST http://localhost:8000/simple-mcp/tools/ping
```

### 2. Echo Tool

Echoes back any message you send.

**Name:** `mcp__simple-mcp__echo`

**Parameters:**
- `message` (string, required): The message to echo back

**Example:**
```bash
curl -X POST http://localhost:8000/simple-mcp/tools/echo \
  -H "Content-Type: application/json" \
  -d '{"message": "Hello World"}'
```

## Creating Custom Tools

### 1. Create a New Tool Class

Create a new file in `src/MCP/Tools/YourTool.php`:

```php
<?php

declare(strict_types=1);

namespace Skylence\SimpleMcp\MCP\Tools;

final class YourTool extends AbstractTool
{
    public function getShortName(): string
    {
        return 'your-tool';
    }

    public function getSchema(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'Your tool description',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'param1' => [
                        'type' => 'string',
                        'description' => 'Parameter description',
                    ],
                ],
                'required' => ['param1'],
            ],
        ];
    }

    public function execute(array $params): array
    {
        // Validate parameters
        if (!isset($params['param1'])) {
            return $this->formatError('param1 is required');
        }

        // Your logic here
        $result = "Processing: {$params['param1']}";

        return $this->formatResponse($result, [
            'processed' => true,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
```

### 2. Register Your Tool

In `src/MCP/SimpleMcpServer.php`, add your tool to the `registerTools()` method:

```php
private function registerTools(): void
{
    $this->registerTool(new PingTool());
    $this->registerTool(new EchoTool());
    $this->registerTool(new YourTool()); // Add this line
}
```

### 3. Test Your Tool

```bash
curl -X POST http://localhost:8000/simple-mcp/tools/your-tool \
  -H "Content-Type: application/json" \
  -d '{"param1": "test value"}'
```

## Architecture

```
simple-mcp/
├── config/
│   └── simple-mcp.php              # Configuration file
├── routes/
│   └── api.php                      # Route definitions
├── src/
│   ├── Http/
│   │   └── Controllers/
│   │       └── McpController.php    # HTTP handler
│   ├── MCP/
│   │   ├── SimpleMcpServer.php      # Core server
│   │   └── Tools/
│   │       ├── AbstractTool.php     # Base tool class
│   │       ├── PingTool.php         # Ping tool
│   │       └── EchoTool.php         # Echo tool
│   ├── Support/
│   │   ├── JsonRpcResponse.php      # JSON-RPC helpers
│   │   └── Logger.php               # Logging helper
│   └── SimpleMcpServiceProvider.php # Service provider
└── composer.json
```

## JSON-RPC 2.0 Compliance

This package follows the JSON-RPC 2.0 specification:

- **Request Format:**
  ```json
  {
      "jsonrpc": "2.0",
      "method": "tools/call",
      "params": {...},
      "id": 1
  }
  ```

- **Success Response:**
  ```json
  {
      "jsonrpc": "2.0",
      "result": {...},
      "id": 1
  }
  ```

- **Error Response:**
  ```json
  {
      "jsonrpc": "2.0",
      "error": {
          "code": -32600,
          "message": "Invalid Request"
      },
      "id": 1
  }
  ```

## Error Codes

- `-32700`: Parse error
- `-32600`: Invalid request
- `-32601`: Method not found
- `-32602`: Invalid params
- `-32603`: Internal error

## Logging

All MCP requests and responses are logged if logging is enabled. Check your Laravel logs:

```bash
tail -f storage/logs/laravel.log
```

## Requirements

- PHP 8.1 or higher
- Laravel 10, 11, or 12

## License

MIT

## Credits

Based on the [Laravel Telescope MCP](https://github.com/lucianotonet/laravel-telescope-mcp) architecture by Luciano Tonet.

## Contributing

Feel free to extend this package with more tools and features!
