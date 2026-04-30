<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;
    protected $primaryKey = 'id';
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
}
