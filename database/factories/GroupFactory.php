<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory\u003cGroup\u003e
 */
class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'owner_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name . '-' . fake()->unique()->numberBetween(1, 10000)),
            'description' => fake()->sentence(),
        ];
    }
}
