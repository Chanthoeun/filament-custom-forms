<?php

namespace LaraSpace\FilamentCustomForms;

use Filament\Contracts\Plugin;
use Filament\Panel;
use LaraSpace\FilamentCustomForms\Filament\Resources\CustomFormResource;
use LaraSpace\FilamentCustomForms\Filament\Resources\CustomFormEntryResource;

class CustomFormPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-custom-forms';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                CustomFormResource::class,
                CustomFormEntryResource::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
