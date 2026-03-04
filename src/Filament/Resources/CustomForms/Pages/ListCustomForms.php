<?php

namespace Chanthoeun\FilamentCustomForms\Filament\Resources\CustomForms\Pages;

use Chanthoeun\FilamentCustomForms\Filament\Resources\CustomForms\CustomFormResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomForms extends ListRecords
{
    protected static string $resource = CustomFormResource::class;

    public function mount(): void
    {
        $tenant = filament()->getTenant();
        if ($tenant && !$tenant->checkFeature('custom_form')) {
            \Filament\Notifications\Notification::make()
                ->title(__('general.access_denied'))
                ->body(__('tenant.upgrade_required'))
                ->warning()
                ->send();

            $this->redirect(config('filament.home_url') ?? '/');
        }

        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
