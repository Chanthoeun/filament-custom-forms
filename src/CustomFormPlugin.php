<?php

namespace Chanthoeun\FilamentCustomForms;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Chanthoeun\FilamentCustomForms\Filament\Resources\CustomForms\CustomFormResource;
use Chanthoeun\FilamentCustomForms\Filament\Resources\CustomFormEntries\CustomFormEntryResource;

class CustomFormPlugin implements Plugin
{
    protected ?string $formModel = null;
    protected ?string $entryModel = null;
    protected ?string $userModel = null;
    protected ?string $uploadDisk = null;
    protected ?string $uploadDirectory = null;
    protected ?string $uploadVisibility = null;
    protected ?string $navigationGroup = null;
    protected ?string $navigationOpsGroup = null;
    protected ?string $navigationFormIcon = null;
    protected ?string $navigationEntryIcon = null;

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

    // --- Models ---
    public function formModel(string $model): static
    {
        $this->formModel = $model;
        return $this;
    }

    public function getFormModel(): string
    {
        return $this->formModel ?? config('filament-custom-forms.models.form', \Chanthoeun\FilamentCustomForms\Models\CustomForm::class);
    }

    public function entryModel(string $model): static
    {
        $this->entryModel = $model;
        return $this;
    }

    public function getEntryModel(): string
    {
        return $this->entryModel ?? config('filament-custom-forms.models.entry', \Chanthoeun\FilamentCustomForms\Models\CustomFormEntry::class);
    }

    public function userModel(string $model): static
    {
        $this->userModel = $model;
        return $this;
    }

    public function getUserModel(): string
    {
        return $this->userModel ?? config('filament-custom-forms.models.user', \App\Models\User::class);
    }

    // --- Uploads ---
    public function uploadDisk(string $disk): static
    {
        $this->uploadDisk = $disk;
        return $this;
    }

    public function getUploadDisk(): string
    {
        return $this->uploadDisk ?? config('filament-custom-forms.uploads.disk', 'public');
    }

    public function uploadDirectory(string $directory): static
    {
        $this->uploadDirectory = $directory;
        return $this;
    }

    public function getUploadDirectory(): string
    {
        return $this->uploadDirectory ?? config('filament-custom-forms.uploads.directory', 'custom-forms');
    }

    public function uploadVisibility(string $visibility): static
    {
        $this->uploadVisibility = $visibility;
        return $this;
    }

    public function getUploadVisibility(): string
    {
        return $this->uploadVisibility ?? config('filament-custom-forms.uploads.visibility', 'public');
    }

    // --- Navigation ---
    public function navigationGroup(string $group): static
    {
        $this->navigationGroup = $group;
        return $this;
    }

    public function getNavigationGroup(): string
    {
        return $this->navigationGroup ?? config('filament-custom-forms.navigation.group', __('custom_form.builder_group'));
    }

    public function navigationOpsGroup(string $group): static
    {
        $this->navigationOpsGroup = $group;
        return $this;
    }

    public function getNavigationOpsGroup(): string
    {
        return $this->navigationOpsGroup ?? config('filament-custom-forms.navigation.ops_group', __('custom_form.operations_group'));
    }

    public function navigationFormIcon(string $icon): static
    {
        $this->navigationFormIcon = $icon;
        return $this;
    }

    public function getNavigationFormIcon(): string
    {
        return $this->navigationFormIcon ?? config('filament-custom-forms.navigation.icon', 'heroicon-o-rectangle-stack');
    }

    public function navigationEntryIcon(string $icon): static
    {
        $this->navigationEntryIcon = $icon;
        return $this;
    }

    public function getNavigationEntryIcon(): string
    {
        return $this->navigationEntryIcon ?? config('filament-custom-forms.navigation.entry_icon', 'heroicon-o-document-text');
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

