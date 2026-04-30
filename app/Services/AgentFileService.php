<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AgentFileService
{
    public function saveWorkspaceFile(AgentExecution $execution, string $filePath, string $content): void
    {
        $workspacePath = "agent-executions/{$execution->id}";
        
        if (!Storage::exists($workspacePath)) {
            Storage::makeDirectory($workspacePath);
        }
        
        Storage::put("{$workspacePath}/{$filePath}", $content);
        
        Log::debug('Workspace file saved', [
            'execution_id' => $execution->id,
            'file_path' => $filePath,
            'size' => strlen($content),
        ]);
    }
    
    public function readWorkspaceFile(AgentExecution $execution, string $filePath): ?string
    {
        $workspacePath = "agent-executions/{$execution->id}/{$filePath}";
        
        if (!Storage::exists($workspacePath)) {
            Log::warning('Workspace file not found', [
                'execution_id' => $execution->id,
                'file_path' => $filePath,
            ]);
            
            return null;
        }
        
        $content = Storage::get($workspacePath);
        
        Log::debug('Workspace file read', [
            'execution_id' => $execution->id,
            'file_path' => $filePath,
            'size' => strlen($content),
        ]);
        
        return $content;
    }
    
    public function listWorkspaceFiles(AgentExecution $execution): array
    {
        $workspacePath = "agent-executions/{$execution->id}";
        
        if (!Storage::exists($workspacePath)) {
            return [];
        }
        
        $files = Storage::files($workspacePath);
        
        $fileDetails = array_map(function ($filePath) {
            return [
                'path' => $filePath,
                'size' => Storage::size($filePath),
                'modified' => Storage::lastModified($filePath),
            ];
        }, $files);
        
        return $fileDetails;
    }
    
    public function deleteWorkspaceFile(AgentExecution $execution, string $filePath): bool
    {
        $workspacePath = "agent-executions/{$execution->id}/{$filePath}";
        
        try {
            if (Storage::exists($workspacePath)) {
                Storage::delete($workspacePath);
                
                Log::info('Workspace file deleted', [
                    'execution_id' => $execution->id,
                    'file_path' => $filePath,
                ]);
            }
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to delete workspace file', [
                'execution_id' => $execution->id,
                'file_path' => $filePath,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    public function cleanupWorkspace(AgentExecution $execution): bool
    {
        $workspacePath = "agent-executions/{$execution->id}";
        
        try {
            if (Storage::exists($workspacePath)) {
                Storage::deleteDirectory($workspacePath);
                
                Log::info('Workspace cleaned up', [
                    'execution_id' => $execution->id,
                ]);
            }
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to cleanup workspace', [
                'execution_id' => $execution->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    public function appendToWorkspaceFile(
        AgentExecution $execution,
        string $filePath,
        string $content
    ): bool {
        $workspacePath = "agent-executions/{$execution->id}/{$filePath}";
        
        try {
            if (Storage::exists($workspacePath)) {
                $existingContent = Storage::get($workspacePath);
                $newContent = $existingContent . "\n" . $content;
                Storage::put($workspacePath, $newContent);
                
                Log::debug('Workspace file appended', [
                    'execution_id' => $execution->id,
                    'file_path' => $filePath,
                    'original_size' => strlen($existingContent),
                    'new_size' => strlen($newContent),
                ]);
            } else {
                return $this->saveWorkspaceFile($execution, $filePath, $content);
            }
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to append to workspace file', [
                'execution_id' => $execution->id,
                'file_path' => $filePath,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    public function getWorkspaceStats(AgentExecution $execution): array
    {
        $files = $this->listWorkspaceFiles($execution);
        
        $totalSize = array_sum(array_column($files, 'size'));
        $fileCount = count($files);
        
        $fileTypes = array_count_values(
            array_map(function ($file) {
                return pathinfo($file['path'], PATHINFO_EXTENSION);
            }, $files)
        );
        
        return [
            'file_count' => $fileCount,
            'total_size_bytes' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'files' => $files,
            'file_types' => $fileTypes,
        ];
    }
    
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $bytes;
        $unitIndex = 0;
        
        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }
        
        return sprintf('%.2f %s', $size, $units[$unitIndex]);
    }
}
