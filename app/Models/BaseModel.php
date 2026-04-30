<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class BaseModel extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;
    protected $primaryKey = 'id';
    
    /**
     * Generate a UUID for the model.
     */
    protected static function generateUuid(): string
    {
        return Str::uuid()->toString();
    }
    
    /**
     * Get a new model instance without casting.
     */
    public static function newModelInstance(array $attributes = [])
    {
        $instance = new static($attributes);
        $instance->exists = false;
        
        return $instance;
    }
    
    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (!$model->{$model->getKeyName()}) {
                $model->{$model->getKeyName()} = self::generateUuid();
            }
        });
    }
    
    protected $casts = [
        'agent_config' => 'array',
        'env_variables' => 'array',
        'config_template' => 'array',
        'config_defaults' => 'array',
        'env_defaults' => 'array',
        'tool_definitions' => 'array',
        'input' => 'array',
        'context' => 'array',
        'config_snapshot' => 'array',
    ];
    
    /**
     * Get the casts that should be used for relationships.
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
