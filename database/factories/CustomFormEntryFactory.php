<?php

namespace Dcx\FilamentCustomForms\Database\Factories;

use Dcx\FilamentCustomForms\Models\CustomFormEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomFormEntryFactory extends Factory
{
    protected $model = CustomFormEntry::class;

    public function definition(): array
    {
        return [
            'data' => [],
        ];
    }
}
