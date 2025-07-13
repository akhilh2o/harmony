<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Page>
 */
class PageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title'         => fake()->sentence(),
            'slug'          => fake()->slug(),
            'subtitle'      => fake()->sentence(),
            'content'       => fake()->paragraphs(3, true),
            'status'        => 'published',
            'feature_image' => fake()->imageUrl(),
        ];
    }
}
