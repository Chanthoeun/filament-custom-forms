<?php

namespace Chanthoeun\FilamentCustomForms\Filament\Resources\CustomFormEntries\Tables;

use Chanthoeun\FilamentCustomForms\Models\CustomForm;
use Chanthoeun\FilamentCustomForms\Models\CustomFormField;
use Chanthoeun\FilamentDocumentBuilder\Models\DocumentTemplate;
use Chanthoeun\FilamentDocumentBuilder\Tables\Actions\DownloadPdfAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn; // Use generic Action
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CustomFormEntriesTable
{
    public static function configure(Table $table): Table
    {
        $formId = self::getFormId($table);

        return $table
            ->columns(self::getColumns($table, $formId))
            ->filters(self::getFilters($formId))
            ->recordActions(self::getRecordActions())
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(function (HasTable $livewire) {
                            $panelId = filament()->getCurrentPanel()?->getId();
                            if (! $panelId) {
                                return true;
                            }

                            $formId = data_get($livewire, 'tableFilters.custom_form_id.value')
                                ?? data_get($livewire, 'activeFormId')
                                ?? request()->input('tableFilters.custom_form_id.value');

                            if (! $formId) {
                                return true;
                            }

                            $form = CustomForm::find($formId);
                            if (! $form) {
                                return true;
                            }

                            return $form->hasPermissionInPanel($panelId, 'DeleteAny:CustomFormEntry');
                        }),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => self::applyQueryConstraints($query, $formId));
    }

    protected static function getFormId(Table $table): ?string
    {
        $livewire = $table->getLivewire();

        return data_get($livewire, 'tableFilters.custom_form_id.value')
            ?? data_get($livewire, 'activeFormId')
            ?? request()->input('tableFilters.custom_form_id.value')
            ?? data_get(request()->query('tableFilters'), 'custom_form_id.value')
            ?? request()->query('form_id');
    }

    protected static function getColumns(Table $table, ?string $formId): array
    {
        $columns = [];

        // Fetch fields metadata from parent CustomFormField table first
        $fieldsMetadata = CustomFormField::query()
            ->when($formId, fn ($query) => $query->where('custom_form_id', $formId))
            ->orderBy('sort')
            ->get()
            ->keyBy('name');

        $definedKeys = $fieldsMetadata->keys();

        $keys = $definedKeys->unique();

        $sortOrder = $fieldsMetadata->pluck('sort', 'name');
        $fieldTypes = $fieldsMetadata->pluck('type', 'name');
        $fieldOptions = $fieldsMetadata->pluck('options', 'name');
        $fieldsById = $fieldsMetadata->keyBy('id');

        $sortedKeys = $keys->sortBy(fn ($key) => $sortOrder[$key] ?? 999999);

        $visibleColumnCount = 0;

        foreach ($sortedKeys as $key) {
            if (in_array(($fieldTypes[$key] ?? null), ['repeater', 'section', 'grid', 'fieldset', 'wizard'])) {
                continue;
            }

            $field = $fieldsMetadata[$key] ?? null;
            if ($field && $field->parent_id) {
                $parent = $fieldsById[$field->parent_id] ?? null;
                if ($parent && $parent->type === 'repeater') {
                    continue;
                }
            }

            $columnKey = "data.{$key}";

            $column = TextColumn::make($columnKey)
                ->label(function () use ($field, $key, $table) {
                    $livewire = $table->getLivewire();
                    $locale = property_exists($livewire, 'activeLocale') && $livewire->activeLocale ? $livewire->activeLocale : app()->getLocale();

                    $label = Str::headline($key);
                    if ($field && $field->label) {
                        if (method_exists($field, 'getTranslation')) {
                            $translated = $field->getTranslation('label', $locale, false) ?: $field->getTranslation('label', config('app.fallback_locale', 'en'), false);
                            if ($translated) {
                                return $translated;
                            }
                        }

                        return $field->label;
                    }

                    return $label;
                })
                ->toggleable(isToggledHiddenByDefault: $visibleColumnCount >= 4);

            // Only make the first 4 columns searchable by default to prevent massive slow SQL JSON queries
            if ($visibleColumnCount < 4) {
                $column->searchable();
            }

            if (($fieldTypes[$key] ?? null) === 'number_input') {
                $column->numeric();
            }

            if (($fieldTypes[$key] ?? null) === 'money') {
                $currency = $fieldOptions[$key]['currency'] ?? 'USD';
                $column->money(strtoupper($currency));
            }

            if (($fieldTypes[$key] ?? null) === 'time_picker') {
                $column->time();
            }

            if (in_array($fieldTypes[$key] ?? null, ['select', 'radio', 'checkbox_list'])) {
                $column->formatStateUsing(function ($state) use ($fieldOptions, $key) {
                    if (blank($state)) return null;
                    
                    $choices = $fieldOptions[$key]['choices'] ?? [];
                    if (is_array($state)) {
                        return collect($state)->map(fn ($val) => $choices[$val] ?? $val)->implode(', ');
                    }
                    
                    return $choices[$state] ?? $state;
                });
            }

            $columns[] = $column;
            $visibleColumnCount++;
        }

        if (count($columns) > 0) {
            $columns[] = TextColumn::make('created_at')
                ->label(__('filament-custom-forms::fcf.general.created_at'))
                ->dateTime()
                ->sortable();
        }

        return $columns;
    }

    protected static function getFilters(?string $formId): array
    {
        $filters = [];

        if ($formId) {
            $formSchema = CustomForm::find($formId);
            if ($formSchema) {
                $schemaFields = $formSchema->fields()->orderBy('sort')->get();

                foreach ($schemaFields as $field) {
                    $jsonKey = $field->name;
                    $label = $field->label ?? $field->name;

                    switch ($field->type) {
                        case 'boolean':
                            $filters[] = TernaryFilter::make($field->name)
                                ->label($label)
                                ->query(
                                    fn (Builder $query, array $data) => $query->when(
                                        isset($data['value']),
                                        fn ($q) => $q->where("data->{$jsonKey}", $data['value'] === '1' || $data['value'] === true)
                                    )
                                );
                            break;

                        case 'select':
                            $choices = $field->options['choices'] ?? [];
                            if (! empty($choices)) {
                                $filters[] = SelectFilter::make($field->name)
                                    ->label($label)
                                    ->options($choices)
                                    ->query(
                                        fn (Builder $query, array $data) => $query->when(
                                            $data['value'],
                                            fn ($q) => $q->where("data->{$jsonKey}", $data['value'])
                                        )
                                    );
                            }
                            break;

                        case 'date_picker':
                            $filters[] = Filter::make($field->name)
                                ->label($label)
                                ->form([
                                    DatePicker::make('from')->label($label.' From'),
                                    DatePicker::make('until')->label($label.' Until'),
                                ])
                                ->query(function (Builder $query, array $data) use ($jsonKey) {
                                    return $query
                                        ->when($data['from'], fn ($q) => $q->where("data->{$jsonKey}", '>=', $data['from']))
                                        ->when($data['until'], fn ($q) => $q->where("data->{$jsonKey}", '<=', $data['until']));
                                });
                            break;
                    }
                }
            }
        }

        $filters[] = SelectFilter::make('custom_form_id')
            ->label(__('filament-custom-forms::fcf.form.single'))
            ->options(CustomForm::pluck('name', 'id'))
            ->hidden();

        return $filters;
    }

    protected static function getRecordActions(): array
    {
        $actions = [
            EditAction::make(),
            DeleteAction::make(),
        ];

        if (class_exists(DocumentTemplate::class)) {
            $actions[] = DownloadPdfAction::make('download_pdf')
                ->templateType(fn ($record) => 'custom_form_'.$record->custom_form_id)
                ->filename(fn ($record) => 'document-'.$record->id.'.pdf')
                ->visible(function ($record) {
                    $panelId = filament()->getCurrentPanel()?->getId();
                    if (! $panelId) {
                        return true;
                    }

                    if (! $record->customForm) {
                        return true;
                    }

                    return $record->customForm->hasPermissionInPanel($panelId, 'ViewAny:CustomFormEntry');
                });
        }

        return $actions;
    }

    protected static function applyQueryConstraints(Builder $query, ?string $formId): Builder
    {
        return $query->with(['creator', 'customForm'])
            ->when($formId, fn ($q, $id) => $q->where('custom_form_id', $id));
    }
}
