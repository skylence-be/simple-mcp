<?php

declare(strict_types=1);

namespace Skylence\SimpleMcp\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Skylence\SimpleMcp\MCP\SimpleMcpServer;
use Skylence\SimpleMcp\Support\JsonRpcResponse;
use Skylence\SimpleMcp\Support\Logger;

final class McpController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly SimpleMcpServer $server,
        private readonly Logger $logger
    ) {
    }

    /**
     * Handle JSON-RPC requests.
     */
    public function handle(Request $request): JsonResponse
    {
        $this->logger->info('MCP request received', [
            'method' => $request->input('method'),
            'params' => $request->input('params'),
        ]);

        // Validate JSON-RPC 2.0 format
        if (! $request->has('jsonrpc') || $request->input('jsonrpc') !== '2.0') {
            return response()->json(
                JsonRpcResponse::invalidRequest($request->input('id'))
            );
        }

        $method = $request->input('method');
        $params = $request->input('params', []);
        $id = $request->input('id');

        // Handle notifications (no response expected)
        if ($method === 'notifications/initialized' && is_null($id)) {
            $this->logger->info('MCP notification: Client initialized');

            return response(null, 204);
        }

        try {
            $result = match ($method) {
                'initialize', 'mcp.manifest', 'mcp.getManifest' => $this->getManifestResponse(),
                'tools/list' => ['tools' => array_values($this->server->getTools())],
                'tools/call' => $this->callTool($params),
                default => throw new \Exception("Method not found: {$method}"),
            };

            $this->logger->info('MCP request completed', ['method' => $method]);

            return response()->json(JsonRpcResponse::success($result, $id));
        } catch (\Exception $e) {
            $this->logger->error('MCP request failed', [
                'method' => $method,
                'error' => $e->getMessage(),
            ]);

            return response()->json(
                JsonRpcResponse::error($e->getMessage(), JsonRpcResponse::INTERNAL_ERROR, $id)
            );
        }
    }

    /**
     * Get the server manifest.
     */
    public function manifest(Request $request): JsonResponse
    {
        $this->logger->info('MCP manifest request received', [
            'method' => $request->method(),
            'uri' => $request->fullUrl(),
        ]);

        // Handle GET requests for manifest
        if ($request->method() === 'GET') {
            return response()->json(
                JsonRpcResponse::success($this->buildManifestResponse())
            );
        }

        // For POST requests, treat as JSON-RPC call
        return $this->handle($request);
    }

    /**
     * Get manifest response in MCP protocol format.
     */
    private function getManifestResponse(): array
    {
        return $this->buildManifestResponse();
    }

    /**
     * Build the complete manifest response.
     */
    private function buildManifestResponse(): array
    {
        $manifest = $this->server->getManifest();

        return [
            'protocolVersion' => '2024-11-05',
            'serverInfo' => [
                'name' => $manifest['name'],
                'version' => $manifest['version'],
                'description' => $manifest['description'],
            ],
            'capabilities' => [
                'tools' => $manifest['tools'],
                'resources' => $manifest['resources'],
                'prompts' => $manifest['prompts'],
            ],
            'metadata' => $manifest['metadata'],
        ];
    }

    /**
     * Call a tool via JSON-RPC.
     */
    public function callTool(Request|array $params): array
    {
        if ($params instanceof Request) {
            $params = $params->all();
        }

        $toolName = $params['name'] ?? null;
        $arguments = $params['arguments'] ?? [];

        if (! $toolName) {
            throw new \InvalidArgumentException('Tool name is required');
        }

        // Extract short name from full name if needed
        $toolName = str_replace('mcp__simple-mcp__', '', $toolName);

        return $this->server->executeTool($toolName, $arguments);
    }

    /**
     * Execute a specific tool directly.
     */
    public function executeTool(Request $request, string $tool): JsonResponse
    {
        try {
            $params = $request->all();
            $result = $this->server->executeTool($tool, $params);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
