<?php

namespace Database\Factories;

use App\Models\AgentDeployment;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgentDeployment>
 */
class AgentDeploymentFactory extends Factory
{
    protected $model = AgentDeployment::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);
        $user = User::factory()->create();

        return [
            'user_id' => $user->id,
            'group_id' => $user->personalGroup?->id ?? Group::factory()->create(['owner_id' => $user->id])->id,
            'name' => ucfirst($name),
            'slug' => str($name)->slug(),
            'description' => fake()->sentence(),
            'image' => 'nousresearch/hermes-agent:v2026.4.30',
            'namespace' => 'agent-desk',
            'model_config' => [
                'default' => 'kimi-k2.6:cloud',
                'provider' => 'ollama-cloud',
                'api_mode' => 'chat_completions',
            ],
            'soul_markdown' => '## Agent Identity\n- Name: ' . ucfirst($name),
            'agents_markdown' => '## Agent Overview\nYou are a helpful AI agent running in Kubernetes.',
            'config_yaml' => null,
            'env_variables' => ['DOCKER_HOST' => 'tcp://localhost:2375'],
            'secrets' => [],
            'resource_limits' => [
                'agent' => [
                    'limits' => ['memory' => '8000Mi', 'cpu' => '2000m'],
                    'requests' => ['memory' => '500Mi', 'cpu' => '500m'],
                ],
                'dind' => [
                    'limits' => ['memory' => '4000Mi', 'cpu' => '2000m'],
                    'requests' => ['memory' => '1000Mi', 'cpu' => '500m'],
                ],
            ],
            'replicas' => 1,
            'domain' => 'agent-services.example.com',
            'status' => 'pending',
            'is_active' => true,
        ];
    }
}
