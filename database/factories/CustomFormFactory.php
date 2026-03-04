<?php

namespace Dcx\FilamentCustomForms\Database\Factories;

use Dcx\FilamentCustomForms\Models\CustomForm;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CustomFormFactory extends Factory
{
    protected $model = CustomForm::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'slug' => fn (array $attributes) => Str::slug($attributes['name']),
            'is_active' => true,
            'allowed_roles' => null,
        ];
    }
}
