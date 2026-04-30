<?php

namespace App\Services;

use App\Models\AgentTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AgentTemplateService
{
    public function getTemplateCategories(): array
    {
        try {
            $categories = DB::table('agent_templates')
                ->select('category')
                ->distinct()
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->orderBy('category')
                ->get()
                ->pluck('category')
                ->unique()
                ->values()
                ->toArray();
            
            return $categories;
            
        } catch (\Exception $e) {
            Log::error('Failed to get template categories', [
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }
    
    public function getPopularTemplates(int $limit = 10): array
    {
        try {
            $popularTemplates = AgentTemplate::withCount('executions')
                ->orderByDesc('executions_count')
                ->limit($limit)
                ->get();
            
            return $popularTemplates;
            
        } catch (\Exception $e) {
            Log::error('Failed to get popular templates', [
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }
    
    public function searchTemplates(string $query, ?array $filters = []): array
    {
        try {
            $templates = AgentTemplate::query();
            
            if (!empty($query)) {
                $templates->where(function ($q) use ($query) {
                    $q->where('name', 'like', '%' . $query . '%')
                        ->orWhere('description', 'like', '%' . $query . '%')
                        ->orWhere('system_prompt', 'like', '%' . $query . '%');
                });
            }
            
            if (!empty($filters)) {
                if (isset($filters['category']) && $filters['category']) {
                    $templates->where('category', $filters['category']);
                }
                
                if (isset($filters['min_executions']) && $filters['min_executions']) {
                    $templates->withCount('executions')
                        ->having('executions_count', '>=', $filters['min_executions']);
                }
            }
            
            $templates->orderBy('created_at', 'desc')
                ->orderBy('execution_count', 'desc');
            
            return $templates->get();
            
        } catch (\Exception $e) {
            Log::error('Failed to search templates', [
                'query' => $query,
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }
    
    public function validateTemplate(AgentTemplate $template): array
    {
        $errors = [];
        $warnings = [];
        
        if (empty($template->name)) {
            $errors[] = 'Template name is required';
        }
        
        if (empty($template->system_prompt)) {
            $errors[] = 'System prompt (AGENT.md content) is required';
        }
        
        if (empty($template->config_defaults) && empty($template->agent_config)) {
            $warnings[] = 'No configuration defaults provided';
        }
        
        if (empty($template->env_defaults) && empty($template->env_variables)) {
            $warnings[] = 'No environment variables provided';
        }
        
        if (!empty($template->user_context) && !is_string($template->user_context)) {
            $errors[] = 'User context must be a string';
        }
        
        if (!empty($template->tool_definitions) && !is_array($template->tool_definitions)) {
            $errors[] = 'Tool definitions must be an array';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
    
    public function getTemplateStats(AgentTemplate $template): array
    {
        try {
            $stats = [
                'execution_count' => 0,
                'last_execution' => null,
                'avg_execution_time' => 0,
                'success_rate' => 0,
                'agents_using_template' => [],
            ];
            
            $executionCount = $template->executions()->count();
            $stats['execution_count'] = $executionCount;
            
            if ($executionCount > 0) {
                $stats['agents_using_template'] = $template->agents()->pluck('name', 'id');
                
                $lastExecution = $template->executions()
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if ($lastExecution) {
                    $stats['last_execution'] = [
                        'id' => (string) $lastExecution->id,
                        'status' => $lastExecution->status,
                        'created_at' => $lastExecution->created_at->toIso8601String(),
                        'completed_at' => $lastExecution->completed_at?->toIso8601String(),
                    ];
                }
                
                if ($template->executions()->where('status', 'completed')->exists()) {
                    $completedCount = $template->executions()
                        ->where('status', 'completed')
                        ->count();
                    
                    $stats['success_rate'] = round(($completedCount / $executionCount) * 100, 2);
                }
            }
            
            return $stats;
            
        } catch (\Exception $e) {
            Log::error('Failed to get template stats', [
                'template_id' => $template->id,
                'error' => $e->getMessage(),
            ]);
            
            return $stats;
        }
    }
    
    public function createTemplateFromDescription(string $description): array
    {
        try {
            $name = Str::slug($description, '_');
            $name = substr($name, 0, 100);
            
            $template = AgentTemplate::create([
                'name' => $name,
                'description' => $description,
                'system_prompt' => "Automated agent based on: {$description}",
                'user_context' => null,
                'config_defaults' => [
                    'model' => 'gpt-4',
                    'temperature' => 0.7,
                    'max_tokens' => 2000,
                ],
                'env_defaults' => [],
                'tool_definitions' => [],
            ]);
            
            Log::info('Template created from description', [
                'template_id' => $template->id,
                'name' => $name,
            ]);
            
            return [
                'success' => true,
                'template' => $template,
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to create template from description', [
                'description' => $description,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    public function exportTemplateSchema(): array
    {
        return [
            'name' => 'string (required, max:255)',
            'description' => 'string (optional)',
            'system_prompt' => 'string (required)',
            'user_context' => 'string (optional)',
            'config_defaults' => 'array (optional)',
            'env_defaults' => 'array (optional)',
            'tool_definitions' => 'array (optional)',
        ];
    }
}
