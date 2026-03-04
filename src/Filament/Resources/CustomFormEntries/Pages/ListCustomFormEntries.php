<?php

namespace Chanthoeun\FilamentCustomForms\Filament\Resources\CustomFormEntries\Pages;

use Chanthoeun\FilamentCustomForms\Filament\Resources\CustomFormEntries\CustomFormEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomFormEntries extends ListRecords
{
    protected static string $resource = CustomFormEntryResource::class;

    public ?string $activeFormId = null;

    public function mount(): void
    {
        parent::mount();
        // Initialize from URL or request body on page load
        $this->activeFormId = request()->input('tableFilters.custom_form_id.value')
            ?? data_get(request()->query('tableFilters'), 'custom_form_id.value')
            ?? request()->query('form_id');
    }

    public function updatedTableFilters(): void
    {
        // Update local state when filters change
        $this->activeFormId = data_get($this->tableFilters, 'custom_form_id.value');
    }

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        // Use the manually tracked state which is robust across updates
        if ($this->activeFormId) {
            $customForm = \App\Models\CustomForm::find($this->activeFormId);
            if ($customForm) {
                $name = __("filament-custom-forms::custom_form.names.{$customForm->slug}");
                if ($name === "custom_form.names.{$customForm->slug}") {
                    $name = $customForm->name;
                }
                return $name;
            }
        }

        return __('filament-custom-forms::custom_form_entry.plural');
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        $customFormId = request()->input('tableFilters.custom_form_id.value');
        $createLabel = __('filament-custom-forms::custom_form_entry.action.create', ['name' => __('filament-custom-forms::custom_form_entry.single')]);

        if ($customFormId) {
            $customForm = \App\Models\CustomForm::find($customFormId);
            if ($customForm) {
                $name = __("filament-custom-forms::custom_form.names.{$customForm->slug}");
                if ($name === "custom_form.names.{$customForm->slug}") {
                    $name = $customForm->name;
                }
                $createLabel = __('filament-custom-forms::custom_form_entry.action.create', ['name' => $name]);
            }
        }

        return [

            Actions\Action::make('export_data')
                ->label(__('filament-custom-forms::custom_form_entry.action.export_data'))
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    \Filament\Forms\Components\Radio::make('format')
                        ->label(__('filament-custom-forms::custom_form_entry.field.export_format'))
                        ->options([
                            'excel' => __('filament-custom-forms::custom_form_entry.option.excel'),
                            'json' => __('filament-custom-forms::custom_form_entry.option.json'),
                            'sql' => __('filament-custom-forms::custom_form_entry.option.sql'),
                        ])
                        ->default('excel')
                        ->inline()
                        ->required(),
                ])
                ->action(function (array $data) {
                    $query = $this->getFilteredTableQuery();

                    if ($this->activeFormId) {
                        $query->where('custom_form_id', $this->activeFormId);
                    }

                    $records = $query->get();
                    $format = $data['format'];

                    $formName = 'custom-entries';
                    if ($this->activeFormId) {
                        $customForm = \App\Models\CustomForm::find($this->activeFormId);
                        if ($customForm) {
                            $name = trim($customForm->name);
                            if ($format === 'sql') {
                                // SQL safe name
                                $name = preg_replace('/[^A-Za-z0-9_]/', '_', strtolower($name));
                                $formName = $name;
                            } else {
                                // File safe name
                                $name = preg_replace('/[^A-Za-z0-9\-\_ ]/', '', $name);
                                $formName = str_replace(' ', '-', $name);
                            }
                        }
                    }

                    if ($format === 'excel') {
                        return \Maatwebsite\Excel\Facades\Excel::download(
                            new \App\Exports\CustomFormEntryExport($records, $this->activeFormId),
                            $formName . '-' . now()->format('Y-m-d-His') . '.xlsx'
                        );
                    } elseif ($format === 'json') {
                        // Reuse the Export class to get consistent formatting
                        $exporter = new \App\Exports\CustomFormEntryExport($records, $this->activeFormId);
                        $headings = $exporter->headings();

                        $data = $records->map(function ($record) use ($exporter, $headings) {
                            $values = $exporter->map($record);
                            return array_combine($headings, $values);
                        });

                        return response()->streamDownload(function () use ($data) {
                            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        }, $formName . '-' . now()->format('Y-m-d-His') . '.json');
                    } elseif ($format === 'sql') {
                        $exporter = new \App\Exports\CustomFormEntrySqlExport($records, $this->activeFormId, $formName);
                        $sql = $exporter->generate();

                        return response()->streamDownload(function () use ($sql) {
                            echo $sql;
                        }, $formName . '-' . now()->format('Y-m-d-His') . '.sql');
                    }
                }),
            Actions\CreateAction::make()
                ->label($createLabel)
                ->url(fn() => CustomFormEntryResource::getUrl('create', [
                    'tableFilters' => request()->query('tableFilters', []),
                ])),
        ];
    }
}
