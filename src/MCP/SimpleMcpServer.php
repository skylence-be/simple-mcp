<?php

declare(strict_types=1);

namespace Skylence\SimpleMcp\MCP;

use Skylence\SimpleMcp\MCP\Tools\AbstractTool;
use Skylence\SimpleMcp\MCP\Tools\EchoTool;
use Skylence\SimpleMcp\MCP\Tools\PingTool;

final class SimpleMcpServer
{
    /**
     * Registered tools.
     *
     * @var array<string, AbstractTool>
     */
    private array $tools = [];

    /**
     * Create a new SimpleMcpServer instance.
     */
    public function __construct()
    {
        $this->registerTools();
    }

    /**
     * Register all available tools.
     */
    private function registerTools(): void
    {
        $this->registerTool(new PingTool());
        $this->registerTool(new EchoTool());
    }

    /**
     * Register a single tool.
     */
    private function registerTool(AbstractTool $tool): void
    {
        $this->tools[$tool->getShortName()] = $tool;
    }

    /**
     * Get the server manifest.
     */
    public function getManifest(): array
    {
        return [
            'name' => 'simple-mcp',
            'version' => '1.0.0',
            'description' => 'A simple MCP server for Laravel',
            'tools' => $this->getToolsAsArray(),
            'resources' => $this->getResourcesAsArray(),
            'prompts' => $this->getPromptsAsArray(),
            'metadata' => $this->getMetadata(),
        ];
    }

    /**
     * Get all registered tools schemas.
     */
    public function getTools(): array
    {
        return $this->getToolsAsArray();
    }

    /**
     * Get tools as an array (for MCP protocol).
     */
    private function getToolsAsArray(): array
    {
        $tools = [];
        foreach ($this->tools as $tool) {
            $schema = $tool->getSchema();
            $tools[] = [
                'name' => $schema['name'],
                'description' => $schema['description'],
                'inputSchema' => $schema['inputSchema'] ?? [
                    'type' => 'object',
                    'properties' => [],
                    'required' => [],
                ],
            ];
        }

        return $tools;
    }

    /**
     * Get available resources as an array (for MCP protocol).
     */
    private function getResourcesAsArray(): array
    {
        return [
            [
                'uri' => 'simple-mcp://status',
                'name' => 'Server Status',
                'description' => 'Current status and health of the Simple MCP server',
                'mimeType' => 'application/json',
            ],
        ];
    }

    /**
     * Get available prompts as an array (for MCP protocol).
     */
    private function getPromptsAsArray(): array
    {
        return [
            [
                'name' => 'test-server',
                'description' => 'Test the Simple MCP server with ping and echo',
                'arguments' => [],
            ],
        ];
    }

    /**
     * Get server metadata.
     */
    private function getMetadata(): array
    {
        return [
            'author' => 'Skylence',
            'repository' => 'https://github.com/skylence-be/simple-mcp',
            'license' => 'MIT',
            'tags' => [
                'laravel',
                'mcp',
                'model-context-protocol',
                'json-rpc',
                'ai',
                'tools',
            ],
        ];
    }

    /**
     * Execute a tool by name.
     */
    public function executeTool(string $toolName, array $params = []): array
    {
        if (! isset($this->tools[$toolName])) {
            throw new \InvalidArgumentException("Tool '{$toolName}' not found");
        }

        return $this->tools[$toolName]->execute($params);
    }

    /**
     * Check if a tool exists.
     */
    public function hasTool(string $toolName): bool
    {
        return isset($this->tools[$toolName]);
    }
}
